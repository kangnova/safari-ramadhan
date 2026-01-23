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
                
                $sql = "INSERT INTO logo_bank (nama_bank, gambar, tanggal_update) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$namaBank, $imagePath])) {
                    $message = "Logo bank berhasil ditambahkan.";
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
            $message = "Logo bank berhasil dihapus.";
            $messageType = "success";
        } else {
            $message = "Terjadi kesalahan saat menghapus logo.";
            $messageType = "danger";
        }
    }
}

// Ambil semua logo bank
$stmt = $conn->query("SELECT * FROM logo_bank ORDER BY nama_bank ASC");
$logos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Logo Bank - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

        
        .preview-image {
            max-width: 150px;
            max-height: 100px;
            object-fit: contain;
        }
        
        .logo-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Manajemen Logo Bank</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah Logo Bank -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tambah Logo Bank Baru</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nama_bank" class="form-label">Nama Bank</label>
                                <input type="text" class="form-control" id="nama_bank" name="nama_bank" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gambar" class="form-label">Logo Bank</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" required accept="image/*">
                                <div class="form-text">Disarankan ukuran gambar 300 x 100 pixel dengan background transparan</div>
                            </div>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Logo Bank
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Daftar Logo Bank -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Logo Bank</h5>
                </div>
                <div class="card-body">
                    <?php if (count($logos) > 0): ?>
                        <div class="row">
                            <?php foreach ($logos as $logo): ?>
                            <div class="col-md-4 mb-4">
                                <div class="logo-item">
                                    <div class="text-center mb-3">
                                        <img src="../<?php echo htmlspecialchars($logo['gambar']); ?>" alt="<?php echo htmlspecialchars($logo['nama_bank']); ?>" class="preview-image">
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($logo['nama_bank']); ?></h5>
                                        <div>
                                            <a href="edit_logo.php?id=<?php echo $logo['id']; ?>" class="btn btn-primary btn-sm me-2" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?delete=<?php echo $logo['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus logo bank ini?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <small class="text-muted">Diperbarui: <?php echo date('d/m/Y H:i', strtotime($logo['tanggal_update'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        Belum ada logo bank. Silakan tambahkan logo baru.
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