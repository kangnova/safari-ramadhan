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
    header('Location: manajemen_logo.php');
    exit();
}

$id = $_GET['id'];

// Ambil data logo bank
$stmt = $conn->prepare("SELECT * FROM logo_bank WHERE id = ?");
$stmt->execute([$id]);
$logo = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika logo tidak ditemukan
if (!$logo) {
    header('Location: manajemen_logo.php');
    exit();
}

// Proses update
if (isset($_POST['update'])) {
    $namaBank = $_POST['nama_bank'];
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
        $sql = "UPDATE logo_bank SET nama_bank = ?, gambar = ?, tanggal_update = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$namaBank, $imagePath, $id])) {
            $message = "Logo bank berhasil diperbarui.";
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
    <title>Edit Logo Bank - Admin</title>
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
            <h2 class="mb-4">Edit Logo Bank</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Logo Bank</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="nama_bank" class="form-label">Nama Bank</label>
                                <input type="text" class="form-control" id="nama_bank" name="nama_bank" value="<?php echo htmlspecialchars($logo['nama_bank']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="gambar" class="form-label">Logo Bank</label>
                                <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                <div class="form-text">Biarkan kosong jika tidak ingin mengubah logo</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Logo Saat Ini</label>
                                <div>
                                    <img src="../<?php echo htmlspecialchars($logo['gambar']); ?>" alt="<?php echo htmlspecialchars($logo['nama_bank']); ?>" class="current-logo img-thumbnail">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <a href="manajemen_logo.php" class="btn btn-secondary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Perbarui Logo
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