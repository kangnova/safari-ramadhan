<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Ambil data kategori
try {
    $query = "SELECT * FROM gallery_kategori ORDER BY nama_kategori";
    $stmt = $conn->query($query);
    $kategoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data gallery dengan nama kategori dan slug
$query = "SELECT g.*, k.nama_kategori, k.slug 
          FROM gallery g 
          JOIN gallery_kategori k ON g.id_kategori = k.id_kategori 
          ORDER BY g.tgl_update DESC";
          
    $stmt = $conn->query($query);
    $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kesalahan database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Gallery - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <!-- Navbar -->
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Gallery</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGalleryModal">
                <i class="bi bi-plus-lg"></i> Tambah Foto
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Kategori -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary active" data-filter="all">Semua</button>
                    <?php foreach ($kategoris as $kategori): ?>
                        <button type="button" class="btn btn-outline-primary" 
                                data-filter="<?= $kategori['slug'] ?>">
                            <?= htmlspecialchars($kategori['nama_kategori']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="row g-4">
            <?php foreach ($galleries as $gallery): ?>
                <div class="col-md-4 gallery-item" data-category="<?= $gallery['slug'] ?>">
                    <div class="card h-100">
                        <img src="../img/gallery/<?= htmlspecialchars($gallery['gambar']) ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($gallery['judul']) ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($gallery['judul']) ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    Kategori: <?= htmlspecialchars($gallery['nama_kategori']) ?>
                                </small>
                            </p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                    data-bs-target="#editModal<?= $gallery['id_gallery'] ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" 
                                    onclick="deleteGallery(<?= $gallery['id_gallery'] ?>)">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Tambah Gallery -->
    <div class="modal fade" id="addGalleryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Foto Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_gallery.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="id_kategori" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($kategoris as $kategori): ?>
                                    <option value="<?= $kategori['id_kategori'] ?>">
                                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="gambar" 
                                   accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit untuk setiap gallery -->
    <?php foreach ($galleries as $gallery): ?>
    <div class="modal fade" id="editModal<?= $gallery['id_gallery'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Foto Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_gallery.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_gallery" value="<?= $gallery['id_gallery'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" 
                                   value="<?= htmlspecialchars($gallery['judul']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="id_kategori" required>
                                <?php foreach ($kategoris as $kategori): ?>
                                    <option value="<?= $kategori['id_kategori'] ?>"
                                            <?= ($kategori['id_kategori'] == $gallery['id_kategori']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Saat Ini</label>
                            <img src="../img/gallery/<?= $gallery['gambar'] ?>" 
                                 class="d-block mb-2" style="max-width: 200px;">
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter gallery
        document.querySelectorAll('[data-filter]').forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                document.querySelectorAll('[data-filter]').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                
                // Filter items
                document.querySelectorAll('.gallery-item').forEach(item => {
                    if (filter === 'all' || item.dataset.category === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        function deleteGallery(id) {
            if(confirm('Apakah anda yakin ingin menghapus foto ini?')) {
                window.location.href = 'delete_gallery.php?id=' + id;
            }
        }
    </script>
</body>
</html>