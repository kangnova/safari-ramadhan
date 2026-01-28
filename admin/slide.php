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
                
                try {
                    $sql = "INSERT INTO slider_images (title, image_path, alt_text, campaign_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt->execute([$title, $imagePath, $altText, $campaignId, $sortOrder, $isActive])) {
                        $message = "Gambar slider berhasil ditambahkan.";
                        $messageType = "success";
                    } else {
                        $message = "Terjadi kesalahan database, silakan coba lagi.";
                        $messageType = "danger";
                    }
                } catch (PDOException $e) {
                    $message = "Error: " . $e->getMessage();
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
    if (isset($_POST['order']) && is_array($_POST['order'])) {
        foreach ($_POST['order'] as $id => $order) {
            $stmt = $conn->prepare("UPDATE slider_images SET sort_order = ? WHERE id = ?");
            $stmt->execute([$order, $id]);
        }
        $message = "Urutan slider berhasil diperbarui.";
        $messageType = "success";
    }
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
    <style>
        .drag-handle {
            cursor: move;
            color: #ccc;
        }
        .drag-handle:hover {
            color: #333;
        }
        .preview-image {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manajemen Slider</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                <i class="fas fa-plus me-2"></i>Tambah Slider Baru
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Daftar Slider -->
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-secondary"><i class="fas fa-list me-2"></i>Daftar Gambar Slider</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($sliders) > 0): ?>
                <form action="" method="post" id="orderForm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th width="120">Gambar</th>
                                    <th>Judul & Alt Text</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="slider-list">
                                <?php foreach ($sliders as $index => $slider): ?>
                                <tr data-id="<?php echo $slider['id']; ?>">
                                    <td class="text-center">
                                        <div class="drag-handle p-2" title="Geser untuk mengubah urutan">
                                            <i class="fas fa-grip-vertical fa-lg"></i>
                                            <input type="hidden" name="order[<?php echo $slider['id']; ?>]" value="<?php echo $slider['sort_order']; ?>" class="order-input">
                                        </div>
                                    </td>
                                    <td>
                                        <img src="../<?php echo htmlspecialchars($slider['image_path']); ?>" alt="<?php echo htmlspecialchars($slider['alt_text']); ?>" class="preview-image">
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($slider['title']); ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 300px;">Alt: <?php echo htmlspecialchars($slider['alt_text']); ?></div>
                                        <?php if ($slider['campaign_id']): ?>
                                            <?php 
                                            // Find campaign title
                                            $campTitle = '';
                                            foreach($campaigns as $c) {
                                                if($c['id'] == $slider['campaign_id']) { $campTitle = $c['title']; break; }
                                            }
                                            if($campTitle): ?>
                                            <span class="badge bg-info text-dark mt-1"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($campTitle); ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="?toggle=<?php echo $slider['id']; ?>" class="badge rounded-pill text-decoration-none bg-<?php echo $slider['is_active'] ? 'success' : 'secondary'; ?>" title="Klik untuk mengubah status">
                                            <?php echo $slider['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_slide.php?id=<?php echo $slider['id']; ?>" class="btn btn-outline-primary btn-sm action-btn me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $slider['id']; ?>" class="btn btn-outline-danger btn-sm action-btn" onclick="return confirm('Yakin ingin menghapus slider ini?')" title="Hapus">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-light border-top">
                        <button type="submit" name="update_order" class="btn btn-success btn-sm">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan Urutan
                        </button>
                        <small class="text-muted ms-2"><i>*Geser baris menggunakan icon grip, lalu klik simpan.</i></small>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3"><i class="far fa-images fa-3x"></i></div>
                    <h5>Belum ada slider</h5>
                    <p class="text-muted">Silakan tambahkan gambar slider baru untuk ditampilkan di halaman depan.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                        <i class="fas fa-plus me-2"></i>Tambah Slider
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Slider -->
    <div class="modal fade" id="addSlideModal" tabindex="-1" aria-labelledby="addSlideModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSlideModalLabel">Tambah Gambar Slider Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Judul <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required placeholder="Judul slider...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="alt_text" class="form-label">Teks Alternatif (Alt Text)</label>
                                <input type="text" class="form-control" id="alt_text" name="alt_text" placeholder="Deskripsi singkat gambar...">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">File Gambar <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="image" name="image" required accept="image/*">
                            <div class="form-text small">Format: JPG, PNG. Disarankan ukuran 1200 x 600 pixel.</div>
                            <div id="image-preview-container" class="mt-2 d-none">
                                <img id="image-preview" src="#" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <?php if (count($campaigns) > 0): ?>
                            <div class="col-md-6 mb-3">
                                <label for="campaign_id" class="form-label">Hubungkan ke Kampanye (Opsional)</label>
                                <select class="form-select" id="campaign_id" name="campaign_id">
                                    <option value="">-- Tidak Ada --</option>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Urutan Awal</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                            </div>
                        </div>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Aktifkan Slider Langsung</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Slider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inisialisasi Sortable untuk drag & drop pada tabel
        document.addEventListener('DOMContentLoaded', function() {
            const sliderList = document.getElementById('slider-list');
            if (sliderList) {
                new Sortable(sliderList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'table-secondary',
                    onEnd: function() {
                        // Update nilai input urutan setelah drag & drop
                        const items = sliderList.querySelectorAll('tr');
                        items.forEach((item, index) => {
                            const input = item.querySelector('.order-input');
                            if(input) input.value = index;
                        });
                    }
                });
            }
            
            // Preview gambar sebelum upload
            const imageInput = document.getElementById('image');
            const previewContainer = document.getElementById('image-preview-container');
            const previewImage = document.getElementById('image-preview');
            
            if (imageInput && previewImage) {
                imageInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            previewContainer.classList.remove('d-none');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        previewImage.src = '#';
                        previewContainer.classList.add('d-none');
                    }
                });
            }
        });
    </script>
</body>
</html>