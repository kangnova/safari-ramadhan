<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Ambil data berita
try {
    $query = "SELECT * FROM berita ORDER BY tgl_posting DESC";
    $stmt = $conn->query($query);
    $beritas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kesalahan database: " . $e->getMessage();
}

function createSlug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Berita & Kegiatan</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBeritaModal">
                <i class="bi bi-plus-lg"></i> Tambah Berita
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

        <!-- Tabel Berita -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Judul</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beritas as $index => $berita): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <img src="../img/berita/<?= htmlspecialchars($berita['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($berita['judul']) ?>" 
                                         style="max-width: 100px;">
                                </td>
                                <td><?= htmlspecialchars($berita['judul']) ?></td>
                                <td><?= date('d/m/Y', strtotime($berita['tgl_posting'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $berita['status'] === 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($berita['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info mb-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $berita['id_berita'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger mb-1"
                                            onclick="deleteBerita(<?= $berita['id_berita'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Berita -->
    <div class="modal fade" id="addBeritaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_berita.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten</label>
                            <textarea class="form-control summernote" name="konten"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Posting</label>
                            <input type="date" class="form-control" name="tgl_posting" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
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

    <!-- Modal Edit untuk setiap berita -->
    <?php foreach ($beritas as $berita): ?>
    <div class="modal fade" id="editModal<?= $berita['id_berita'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_berita.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_berita" value="<?= $berita['id_berita'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" 
                                   value="<?= htmlspecialchars($berita['judul']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konten</label>
                            <textarea class="form-control summernote" name="konten"><?= htmlspecialchars($berita['konten']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Saat Ini</label>
                            <img src="../img/berita/<?= $berita['gambar'] ?>" 
                                 class="d-block mb-2" style="max-width: 200px;">
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Posting</label>
                            <input type="date" class="form-control" name="tgl_posting" 
                                   value="<?= date('Y-m-d', strtotime($berita['tgl_posting'])) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="draft" <?= $berita['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $berita['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
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

    <!-- jQuery & Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Summernote JS -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize Summernote
        $('.summernote').summernote({
            placeholder: 'Tulis konten berita di sini...',
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });

    function deleteBerita(id) {
        if(confirm('Apakah anda yakin ingin menghapus berita ini?')) {
            window.location.href = 'delete_berita.php?id=' + id;
        }
    }
    </script>
</body>
</html>