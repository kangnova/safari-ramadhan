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

// Proses upload gambar
if (isset($_POST['submit'])) {
    $namaBank = $_POST['nama_bank'];
    $nomorRekening = $_POST['nomor_rekening'];
    $atasNama = $_POST['atas_nama'];
    $kategori = $_POST['kategori'];
    $urutan = $_POST['urutan'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Upload gambar
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
                // Simpan ke database
                $imagePath = "img/bank/" . $newFileName;
                
                $sql = "INSERT INTO logo_bank (nama_bank, nomor_rekening, atas_nama, kategori, urutan, is_active, gambar, tanggal_update) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$namaBank, $nomorRekening, $atasNama, $kategori, $urutan, $isActive, $imagePath])) {
                    $message = "Metode pembayaran berhasil ditambahkan.";
                    $messageType = "success";
                } else {
                    $message = "Terjadi kesalahan, silakan coba lagi.";
                    $messageType = "danger";
                }
            } else {
                $message = "Maaf, terjadi kesalahan saat upload gambar.";
                $messageType = "danger";
            }
        } else {
            $message = "Hanya file JPG, JPEG, PNG, & GIF yang diperbolehkan.";
            $messageType = "danger";
        }
    } else {
        $message = "Pilih gambar untuk diupload.";
        $messageType = "danger";
    }
}

// Hapus logo
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Ambil path gambar sebelum dihapus
    $stmt = $conn->prepare("SELECT gambar FROM logo_bank WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Hapus file gambar jika ada
        $imagePath = "../" . $image['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Hapus dari database
        $stmt = $conn->prepare("DELETE FROM logo_bank WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Data berhasil dihapus.";
            $messageType = "success";
        } else {
            $message = "Terjadi kesalahan saat menghapus data.";
            $messageType = "danger";
        }
    }
}

// Ambil semua logo bank
$stmt = $conn->query("SELECT * FROM logo_bank ORDER BY kategori ASC, urutan ASC, nama_bank ASC");
$logos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Metode Pembayaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

        
        .preview-image {
            max-width: 100px;
            max-height: 60px;
            object-fit: contain;
        }
        
        .logo-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s;
        }

        .logo-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .badge-bank { background-color: #0d6efd; }
        .badge-ewallet { background-color: #198754; }
        .badge-qris { background-color: #fd7e14; }

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Manajemen Metode Pembayaran</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah Logo Bank -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Metode Pembayaran Baru</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nama_bank" class="form-label">Nama Bank / E-Wallet</label>
                                <input type="text" class="form-control" id="nama_bank" name="nama_bank" required placeholder="Contoh: Bank Mandiri">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="ewallet">E-Wallet</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="urutan" class="form-label">Urutan Tampil</label>
                                <input type="number" class="form-control" id="urutan" name="urutan" value="0" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nomor_rekening" class="form-label">Nomor Rekening</label>
                                <input type="text" class="form-control" id="nomor_rekening" name="nomor_rekening" placeholder="Contoh: 1234567890">
                                <div class="form-text">Kosongkan untuk QRIS</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="atas_nama" class="form-label">Atas Nama</label>
                                <input type="text" class="form-control" id="atas_nama" name="atas_nama" placeholder="Contoh: Yayasan Safari Ramadhan">
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3">
                                <label for="gambar" class="form-label">Logo / Gambar QRIS</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" required accept="image/*">
                                <div class="form-text">Format: JPG, PNG. Ukuran disarankan: Bank (200x60px), QRIS (300x300px)</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Status Aktif</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Daftar Logo Bank -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <?php if (count($logos) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Urutan</th>
                                        <th>Logo</th>
                                        <th>Nama Bank</th>
                                        <th>Detail Rekening</th>
                                        <th>Kategori</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logos as $logo): ?>
                                    <tr>
                                        <td><?php echo $logo['urutan']; ?></td>
                                        <td>
                                            <img src="../<?php echo htmlspecialchars($logo['gambar']); ?>" alt="<?php echo htmlspecialchars($logo['nama_bank']); ?>" class="preview-image rounded">
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($logo['nama_bank']); ?></td>
                                        <td>
                                            <?php if($logo['kategori'] != 'qris'): ?>
                                                <div class="small">
                                                    <div><strong>No:</strong> <?php echo htmlspecialchars($logo['nomor_rekening']); ?></div>
                                                    <div class="text-muted"><strong>A.N:</strong> <?php echo htmlspecialchars($logo['atas_nama']); ?></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">QR Code Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badgeClass = 'bg-secondary';
                                            if ($logo['kategori'] == 'bank') $badgeClass = 'bg-primary';
                                            elseif ($logo['kategori'] == 'ewallet') $badgeClass = 'bg-success';
                                            elseif ($logo['kategori'] == 'qris') $badgeClass = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo strtoupper($logo['kategori']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($logo['is_active']): ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Non-Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_logo.php?id=<?php echo $logo['id']; ?>" class="btn btn-outline-primary btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?php echo $logo['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0 text-center p-5">
                        <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                        Belum ada metode pembayaran yang ditambahkan.
                    </div>
                    <?php endif; ?>
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