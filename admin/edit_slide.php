<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Inisialisasi pesan
$message = '';
$messageType = '';

// Cek ID slider
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: slide.php');
    exit;
}

$id = $_GET['id'];

// Ambil data slider berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM slider_images WHERE id = ?");
$stmt->execute([$id]);
$slider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slider) {
    header('Location: slide.php');
    exit;
}

// Proses update slider
if (isset($_POST['update'])) {
    $title = $_POST['title'];
    $altText = $_POST['alt_text'];
    $campaignId = !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : null;
    $sortOrder = $_POST['sort_order'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Cek apakah ada gambar baru yang diupload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Upload gambar baru
        $targetDir = "../img/donasi/";
        
        // Generate nama file unik untuk menghindari overwrite
        $fileName = basename($_FILES["image"]["name"]);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'slide_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
        $targetFilePath = $targetDir . $newFileName;
        
        // Format gambar yang diperbolehkan
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array(strtolower($fileExtension), $allowTypes)) {
            // Hapus gambar lama
            $oldImagePath = "../" . $slider['image_path'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            
            // Upload file baru ke server
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                $imagePath = "img/donasi/" . $newFileName;
                
                // Update dengan gambar baru
                $stmt = $conn->prepare("UPDATE slider_images SET title = ?, image_path = ?, alt_text = ?, campaign_id = ?, sort_order = ?, is_active = ? WHERE id = ?");
                if ($stmt->execute([$title, $imagePath, $altText, $campaignId, $sortOrder, $isActive, $id])) {
                    $message = "Slider berhasil diperbarui dengan gambar baru.";
                    $messageType = "success";
                    
                    // Refresh data
                    $stmt = $conn->prepare("SELECT * FROM slider_images WHERE id = ?");
                    $stmt->execute([$id]);
                    $slider = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $message = "Terjadi kesalahan saat memperbarui slider.";
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
        // Update tanpa mengubah gambar
        $stmt = $conn->prepare("UPDATE slider_images SET title = ?, alt_text = ?, campaign_id = ?, sort_order = ?, is_active = ? WHERE id = ?");
        if ($stmt->execute([$title, $altText, $campaignId, $sortOrder, $isActive, $id])) {
            $message = "Slider berhasil diperbarui.";
            $messageType = "success";
            
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM slider_images WHERE id = ?");
            $stmt->execute([$id]);
            $slider = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Terjadi kesalahan saat memperbarui slider.";
            $messageType = "danger";
        }
    }
}

// Ambil daftar kampanye (jika ada)
$campaigns = [];
try {
    $stmt = $conn->query("SELECT id, title FROM campaigns ORDER BY title ASC");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika tabel campaigns belum ada, tidak perlu menampilkan error
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Slider - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
        }
        

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit Slider</h2>
                <a href="slide.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Gambar Slider</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Judul</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($slider['title']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="alt_text" class="form-label">Teks Alternatif</label>
                                <input type="text" class="form-control" id="alt_text" name="alt_text" value="<?php echo htmlspecialchars($slider['alt_text'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="current_image" class="form-label">Gambar Saat Ini</label>
                                <div class="border p-2 rounded">
                                    <img src="../<?php echo htmlspecialchars($slider['image_path']); ?>" alt="<?php echo htmlspecialchars($slider['alt_text'] ?? ''); ?>" class="preview-image">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label">Ganti Gambar (opsional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">Disarankan ukuran gambar 1200 x 600 pixel. Biarkan kosong jika tidak ingin mengubah gambar.</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php if (count($campaigns) > 0): ?>
                            <div class="col-md-4 mb-3">
                                <label for="campaign_id" class="form-label">Kampanye</label>
                                <select class="form-select" id="campaign_id" name="campaign_id">
                                    <option value="">-- Pilih Kampanye --</option>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo $campaign['id']; ?>" <?php echo ($campaign['id'] == $slider['campaign_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($campaign['title']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-4 mb-3">
                                <label for="sort_order" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo $slider['sort_order']; ?>" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo $slider['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Aktif
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                            <a href="slide.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Preview gambar yang akan diupload
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.querySelector('.preview-image');
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>