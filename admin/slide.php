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
    $title = $_POST['title'];
    $altText = $_POST['alt_text'];
    $campaignId = !empty($_POST['campaign_id']) ? $_POST['campaign_id'] : null;
    $sortOrder = $_POST['sort_order'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Upload gambar
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../img/donasi/";
        
        // Pastikan direktori ada
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate nama file unik untuk menghindari overwrite
        $fileName = basename($_FILES["image"]["name"]);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'slide_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExtension;
        $targetFilePath = $targetDir . $newFileName;
        
        // Format gambar yang diperbolehkan
        $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array(strtolower($fileExtension), $allowTypes)) {
            // Upload file ke server
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                // Simpan ke database
                $imagePath = "img/donasi/" . $newFileName;
                
                $sql = "INSERT INTO slider_images (title, image_path, alt_text, campaign_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$title, $imagePath, $altText, $campaignId, $sortOrder, $isActive])) {
                    $message = "Gambar slider berhasil ditambahkan.";
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

// Hapus gambar
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Ambil path gambar sebelum dihapus
    $stmt = $conn->prepare("SELECT image_path FROM slider_images WHERE id = ?");
    $stmt->execute([$id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($image) {
        // Hapus file gambar jika ada
        $imagePath = "../" . $image['image_path'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Hapus dari database
        $stmt = $conn->prepare("DELETE FROM slider_images WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Gambar slider berhasil dihapus.";
            $messageType = "success";
        } else {
            $message = "Terjadi kesalahan saat menghapus gambar.";
            $messageType = "danger";
        }
    }
}

// Update status aktif
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = $_GET['toggle'];
    
    $stmt = $conn->prepare("UPDATE slider_images SET is_active = NOT is_active WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Status slider berhasil diperbarui.";
        $messageType = "success";
    } else {
        $message = "Terjadi kesalahan saat memperbarui status.";
        $messageType = "danger";
    }
}

// Update urutan
if (isset($_POST['update_order'])) {
    foreach ($_POST['order'] as $id => $order) {
        $stmt = $conn->prepare("UPDATE slider_images SET sort_order = ? WHERE id = ?");
        $stmt->execute([$order, $id]);
    }
    $message = "Urutan slider berhasil diperbarui.";
    $messageType = "success";
}

// Ambil semua gambar slider
$stmt = $conn->query("SELECT * FROM slider_images ORDER BY sort_order ASC");
$sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil daftar kampanye (jika ada)
$campaigns = [];
try {
    $stmt = $conn->query("SELECT id, title FROM campaigns ORDER BY title ASC");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Jika tabel campaigns belum ada, tidak perlu menampilkan error
}

// Function untuk format rupiah (konsistensi dengan template)
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Slider - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Manajemen Slider</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah Slider -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tambah Gambar Slider Baru</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Judul</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="alt_text" class="form-label">Teks Alternatif</label>
                                <input type="text" class="form-control" id="alt_text" name="alt_text">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="image" class="form-label">Gambar</label>
                                <input type="file" class="form-control" id="image" name="image" required accept="image/*">
                                <div class="form-text">Disarankan ukuran gambar 1200 x 600 pixel</div>
                            </div>
                            <?php if (count($campaigns) > 0): ?>
                            <div class="col-md-6 mb-3">
                                <label for="campaign_id" class="form-label">Kampanye</label>
                                <select class="form-select" id="campaign_id" name="campaign_id">
                                    <option value="">-- Pilih Kampanye --</option>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Aktif
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Slider
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Daftar Slider -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Daftar Gambar Slider</h5>
                </div>
                <div class="card-body">
                    <?php if (count($sliders) > 0): ?>
                    <form action="" method="post">
                        <div id="slider-list" class="mb-3">
                            <?php foreach ($sliders as $slider): ?>
                            <div class="slider-item d-flex align-items-center" data-id="<?php echo $slider['id']; ?>">
                                <div class="drag-handle me-2">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                <div class="me-3">
                                    <img src="../<?php echo htmlspecialchars($slider['image_path']); ?>" alt="<?php echo htmlspecialchars($slider['alt_text']); ?>" class="preview-image">
                                </div>
                                <div class="flex-grow-1">
                                    <h5><?php echo htmlspecialchars($slider['title']); ?></h5>
                                    <p class="mb-1">Alt: <?php echo htmlspecialchars($slider['alt_text']); ?></p>
                                    <input type="hidden" name="order[<?php echo $slider['id']; ?>]" value="<?php echo $slider['sort_order']; ?>" class="order-input">
                                </div>
                                <div class="ms-auto d-flex">
                                    <a href="?toggle=<?php echo $slider['id']; ?>" class="btn btn-<?php echo $slider['is_active'] ? 'success' : 'secondary'; ?> btn-sm me-2" title="<?php echo $slider['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                        <i class="fas fa-<?php echo $slider['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                    </a>
                                    <a href="edit_slide.php?id=<?php echo $slider['id']; ?>" class="btn btn-primary btn-sm me-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $slider['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus slider ini?')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="update_order" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Simpan Urutan
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        Belum ada gambar slider. Silakan tambahkan gambar baru.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inisialisasi Sortable untuk drag & drop
        document.addEventListener('DOMContentLoaded', function() {
            const sliderList = document.getElementById('slider-list');
            if (sliderList) {
                new Sortable(sliderList, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function() {
                        // Update nilai input urutan setelah drag & drop
                        const items = sliderList.querySelectorAll('.slider-item');
                        items.forEach((item, index) => {
                            const id = item.getAttribute('data-id');
                            const input = item.querySelector('.order-input');
                            input.value = index;
                        });
                    }
                });
            }
            
            // Preview gambar sebelum upload
            const imageInput = document.getElementById('image');
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