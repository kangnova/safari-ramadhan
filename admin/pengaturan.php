<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settings = [
            'safari_form_status' => $_POST['safari_form_status'],
            'safari_form_message' => $_POST['safari_form_message'],
            'safari_quota' => $_POST['safari_quota'],
            'ifthar_quota' => $_POST['ifthar_quota'],
            'safari_program_status' => $_POST['safari_program_status'],
            'safari_program_ended_message' => $_POST['safari_program_ended_message']
        ];

        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("UPDATE settings SET setting_value = :value WHERE setting_key = :key");
            $stmt->execute(['value' => $value, 'key' => $key]);
        }

        // Handle File Upload
        if (isset($_FILES['mou_file']) && $_FILES['mou_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileTmpPath = $_FILES['mou_file']['tmp_name'];
            $fileType = $_FILES['mou_file']['type'];
            
            if ($fileType !== 'application/pdf') {
                throw new Exception("Hanya file PDF yang diperbolehkan untuk MOU.");
            }

            $dest_path = $uploadDir . 'mou_safari.pdf';
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $_SESSION['success_file'] = "File MOU berhasil diupload.";
            } else {
                throw new Exception("Gagal mengupload file MOU.");
            }
        }

        // Handle MOU Image Page 1 Upload
        if (isset($_FILES['mou_img_1']) && $_FILES['mou_img_1']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileTmpPath = $_FILES['mou_img_1']['tmp_name'];
            $fileType = mime_content_type($fileTmpPath);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Hanya file gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan untuk MOU Image 1.");
            }
            
            $dest_path = $uploadDir . 'mou_page_1.jpg';
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $_SESSION['success_mou_1'] = "MOU Halaman 1 berhasil diupload.";
            } else {
                throw new Exception("Gagal mengupload MOU Halaman 1.");
            }
        }

        // Handle MOU Image Page 2 Upload
        if (isset($_FILES['mou_img_2']) && $_FILES['mou_img_2']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileTmpPath = $_FILES['mou_img_2']['tmp_name'];
            $fileType = mime_content_type($fileTmpPath);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Hanya file gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan untuk MOU Image 2.");
            }
            
            $dest_path = $uploadDir . 'mou_page_2.jpg';
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $_SESSION['success_mou_2'] = "MOU Halaman 2 berhasil diupload.";
            } else {
                throw new Exception("Gagal mengupload MOU Halaman 2.");
            }
        }

        // Handle Logo Upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/img/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileTmpPath = $_FILES['logo_file']['tmp_name'];
            $fileType = mime_content_type($fileTmpPath);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Hanya file gambar (JPG, PNG, GIF, WEBP) yang diperbolehkan.");
            }

            // Always overwrite logo.png (or logo.jpg if we want to support multiple, but fixed name is easier for frontend)
            // For simplicity and to match frontend references, we force convert or just save as logo.png? 
            // Better: just save as logo.png. If user uploads jpg, browsers usually handle misnamed extension, 
            // but ideally we should convert. For now, let's just save to logo.png to ensure standard path.
            // A simple move_uploaded_file is risky if type mismatch, but usually works for simple use case.
            // Let's stick to logo.png for consistency.
            
            $dest_path = $uploadDir . 'logo.png';
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                $_SESSION['success_logo'] = "Logo berhasil diperbarui.";
            } else {
                throw new Exception("Gagal mengupload logo.");
            }
        }

        $_SESSION['success'] = "Pengaturan berhasil diperbarui!";
        header('Location: pengaturan.php');
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Fetch Current Settings
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
    $currentSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    die("Error fetching settings: " . $e->getMessage());
}

// Default values if not in DB
$safariStatus = $currentSettings['safari_form_status'] ?? 'open';
$safariMessage = $currentSettings['safari_form_message'] ?? '';
$safariQuota = $currentSettings['safari_quota'] ?? 170;
$iftharQuota = $currentSettings['ifthar_quota'] ?? 200;
$programStatus = $currentSettings['safari_program_status'] ?? 'active';
$programEndedMessage = $currentSettings['safari_program_ended_message'] ?? '';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0"><i class="bi bi-gear-fill me-2"></i>Pengaturan Sistem</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <!-- UPLOAD MOU -->
                            <div class="mb-5">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Manajemen File MOU</h6>
                                <div class="mb-3">
                                    <label for="mouFile" class="form-label fw-bold">Upload File MOU (PDF)</label>
                                    <input class="form-control" type="file" id="mouFile" name="mou_file" accept=".pdf">
                                    <div class="form-text">File ini yang akan didownload oleh peserta saat mendaftar. Format wajib PDF.</div>
                                    <?php if(file_exists('../assets/docs/mou_safari.pdf')): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> File MOU tersedia</span>
                                            <a href="../assets/docs/mou_safari.pdf" target="_blank" class="btn btn-sm btn-outline-primary ms-2">Lihat File Saat Ini</a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Belum ada file MOU</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- UPLOAD MOU IMAGES -->
                            <div class="mb-5">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Manajemen Gambar MOU (Tampil di Popup)</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="mouImg1" class="form-label fw-bold">Upload Halaman 1</label>
                                        <div class="mb-2">
                                            <img src="../assets/img/mou_page_1.jpg?v=<?= time() ?>" alt="MOU Page 1" class="img-thumbnail" style="height: 150px; object-fit: contain;">
                                        </div>
                                        <input class="form-control" type="file" id="mouImg1" name="mou_img_1" accept="image/*">
                                        <div class="form-text">Gambar untuk halaman pertama MOU.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="mouImg2" class="form-label fw-bold">Upload Halaman 2</label>
                                        <div class="mb-2">
                                            <img src="../assets/img/mou_page_2.jpg?v=<?= time() ?>" alt="MOU Page 2" class="img-thumbnail" style="height: 150px; object-fit: contain;">
                                        </div>
                                        <input class="form-control" type="file" id="mouImg2" name="mou_img_2" accept="image/*">
                                        <div class="form-text">Gambar untuk halaman kedua MOU.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- UPLOAD LOGO -->
                            <div class="mb-5">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Manajemen Logo Yayasan</h6>
                                <div class="mb-3">
                                    <label for="logoFile" class="form-label fw-bold">Upload Logo Baru</label>
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center mb-2 mb-md-0">
                                            <img src="../assets/img/logo.png?v=<?= time() ?>" alt="Current Logo" class="img-thumbnail" style="max-height: 80px;">
                                        </div>
                                        <div class="col-md-10">
                                            <input class="form-control" type="file" id="logoFile" name="logo_file" accept="image/*">
                                            <div class="form-text">Format: PNG/JPG. Disarankan background transparan & rasio persegi/lingkaran.</div>
                                            <div class="form-text text-warning"><i class="bi bi-info-circle"></i> Logo akan langsung berubah di Website & Kop Surat. Pastikan ukuran proporsional.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PENGATURAN STATUS STARTUP PROGRAM (END OF SEASON) -->
                            <div class="mb-5">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Status Utama Program (End of Season)</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label d-block fw-bold">Status Program Safari Ramadhan</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="safari_program_status" id="programActive" value="active" <?= $programStatus == 'active' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-success" for="programActive"><i class="bi bi-play-circle-fill"></i> Program Berjalan</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="safari_program_status" id="programEnded" value="ended" <?= $programStatus == 'ended' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-danger" for="programEnded"><i class="bi bi-stop-circle-fill"></i> Program Selesai</label>
                                    </div>
                                    <div class="form-text">Jika "Program Selesai", halaman utama hanya akan menampilkan informasi program tanpa fitur pendaftaran, login, dll.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="programEndedMessage" class="form-label fw-bold">Pesan Penutupan Program</label>
                                    <textarea class="form-control" id="programEndedMessage" name="safari_program_ended_message" rows="4"><?= htmlspecialchars($programEndedMessage) ?></textarea>
                                    <div class="form-text">Pesan yang muncul di banner utama ketika program selesai.</div>
                                </div>
                            </div>

                            <!-- PENGATURAN FORM SAFARI -->
                            <div class="mb-5">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Status Pendaftaran Safari Ramadhan</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label d-block fw-bold">Status Form</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="safari_form_status" id="statusOpen" value="open" <?= $safariStatus == 'open' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-success" for="statusOpen"><i class="bi bi-unlock-fill"></i> Buka Pendaftaran</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="safari_form_status" id="statusClosed" value="closed" <?= $safariStatus == 'closed' ? 'checked' : '' ?>>
                                        <label class="form-check-label text-danger" for="statusClosed"><i class="bi bi-lock-fill"></i> Tutup Pendaftaran</label>
                                    </div>
                                    <div class="form-text">Jika ditutup, formulir tidak akan bisa diakses oleh publik.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="safariMessage" class="form-label fw-bold">Pesan Penutupan</label>
                                    <textarea class="form-control" id="safariMessage" name="safari_form_message" rows="3"><?= htmlspecialchars($safariMessage) ?></textarea>
                                    <div class="form-text">Pesan yang muncul ketika pendaftaran ditutup.</div>
                                </div>
                            </div>

                            <!-- PENGATURAN KUOTA -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3 text-primary">Pengaturan Kuota</h6>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="safariQuota" class="form-label fw-bold">Kuota Safari Ramadhan</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="safariQuota" name="safari_quota" value="<?= htmlspecialchars($safariQuota) ?>" required>
                                            <span class="input-group-text">Lembaga</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="iftharQuota" class="form-label fw-bold">Kuota Ifthar Akbar</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="iftharQuota" name="ifthar_quota" value="<?= htmlspecialchars($iftharQuota) ?>" required>
                                            <span class="input-group-text">Paket</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Simpan Pengaturan</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
