<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Pesan feedback
$message = '';
$messageType = '';

// Cek apakah ID ada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: logo_bank.php');
    exit();
}

$id = $_GET['id'];

// Ambil data logo bank
$stmt = $conn->prepare("SELECT * FROM logo_bank WHERE id = ?");
$stmt->execute([$id]);
$logo = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika logo tidak ditemukan
if (!$logo) {
    header('Location: logo_bank.php');
    exit();
}

// Proses update
if (isset($_POST['update'])) {
    $namaBank = $_POST['nama_bank'];
    $nomorRekening = $_POST['nomor_rekening'];
    $atasNama = $_POST['atas_nama'];
    $kategori = $_POST['kategori'];
    $urutan = $_POST['urutan'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $imagePath = $logo['gambar']; // Default menggunakan gambar lama
    
    // Cek apakah ada upload gambar baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $targetDir = "../img/bank/";
        
        // Pastikan direktori ada
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate nama file unik untuk menghindari overwrite
        $fileName = basename($_FILES["gambar"]["name"]);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'logo_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
        $targetFilePath = $targetDir . $newFileName;
        
        // Format gambar yang diperbolehkan
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array(strtolower($fileExtension), $allowTypes)) {
            // Upload file ke server
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $targetFilePath)) {
                // Hapus file lama
                $oldImagePath = "../" . $logo['gambar'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
                
                // Update path gambar
                $imagePath = "img/bank/" . $newFileName;
            } else {
                $message = "Maaf, terjadi kesalahan saat upload gambar.";
                $messageType = "danger";
            }
        } else {
            $message = "Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan.";
            $messageType = "danger";
        }
    }
    
    // Update database jika tidak ada error
    if (empty($message)) {
        $sql = "UPDATE logo_bank SET nama_bank = ?, nomor_rekening = ?, atas_nama = ?, kategori = ?, urutan = ?, is_active = ?, gambar = ?, tanggal_update = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$namaBank, $nomorRekening, $atasNama, $kategori, $urutan, $isActive, $imagePath, $id])) {
            $message = "Data berhasil diperbarui.";
            $messageType = "success";
            
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM logo_bank WHERE id = ?");
            $stmt->execute([$id]);
            $logo = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Terjadi kesalahan, silakan coba lagi.";
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Metode Pembayaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .current-logo {
            max-width: 200px;
            max-height: 150px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Edit Metode Pembayaran</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nama_bank" class="form-label">Nama Bank / E-Wallet</label>
                                <input type="text" class="form-control" id="nama_bank" name="nama_bank" value="<?php echo htmlspecialchars($logo['nama_bank']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="bank" <?php echo $logo['kategori'] == 'bank' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="ewallet" <?php echo $logo['kategori'] == 'ewallet' ? 'selected' : ''; ?>>E-Wallet</option>
                                    <option value="qris" <?php echo $logo['kategori'] == 'qris' ? 'selected' : ''; ?>>QRIS</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="urutan" class="form-label">Urutan Tampil</label>
                                <input type="number" class="form-control" id="urutan" name="urutan" value="<?php echo htmlspecialchars($logo['urutan']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                <input type="text" class="form-control" id="nomor_rekening" name="nomor_rekening" value="<?php echo htmlspecialchars($logo['nomor_rekening']); ?>" placeholder="Contoh: 1234567890">
                                <div class="form-text">Kosongkan untuk QRIS</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="atas_nama" class="form-label">Atas Nama</label>
                                <input type="text" class="form-control" id="atas_nama" name="atas_nama" value="<?php echo htmlspecialchars($logo['atas_nama']); ?>" placeholder="Contoh: Yayasan Safari Ramadhan">
                            </div>
                        </div>

                        <div class="row mb-4 align-items-center">
                            <div class="col-md-6 mb-3">
                                <label for="gambar" class="form-label">Ganti Logo / Gambar</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                <div class="form-text">Biarkan kosong jika tidak ingin mengubah, JPG/PNG</div>
                            </div>
                            <div class="col-md-3 mb-3 text-center">
                                <label class="form-label d-block">Logo Saat Ini</label>
                                <img src="../<?php echo htmlspecialchars($logo['gambar']); ?>" alt="<?php echo htmlspecialchars($logo['nama_bank']); ?>" class="current-logo img-thumbnail">
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="form-check form-switch p-3 border rounded">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $logo['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold" for="is_active">Status Aktif</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12 text-end">
                                <a href="logo_bank.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Perbarui Data
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview gambar sebelum upload
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('gambar');
            if (imageInput) {
                imageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.createElement('img');
                            preview.src = e.target.result;
                            preview.className = 'img-thumbnail mt-2';
                            preview.style.maxWidth = '200px';
                            preview.style.maxHeight = '150px';
                            
                            const previewContainer = imageInput.parentElement;
                            const oldPreview = previewContainer.querySelector('.img-thumbnail');
                            if (oldPreview) {
                                previewContainer.removeChild(oldPreview);
                            }
                            previewContainer.appendChild(preview);
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html>