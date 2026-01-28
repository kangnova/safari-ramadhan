<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables
$programs = [];

try {
    // Query untuk mengambil data program
    $query = "SELECT * FROM program ORDER BY urutan ASC, tgl_update DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error quietly or log it
    error_log("Error fetching programs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Program Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
<?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Program</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                <i class="bi bi-plus-lg"></i> Tambah Program
            </button>
        </div>

        <!-- Tabel Program -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th>Nama Program</th>
                                <th>Gambar</th>
                                <th>Deskripsi</th>
                                <th>Update Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $index => $program): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= $program['urutan'] ?></td>
                                <td>
                                    <?php if ($program['status'] === 'published'): ?>
                                        <span class="badge bg-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($program['nama_program']) ?></td>
                                <td>
                                    <img src="../img/program/<?= $program['gambar'] ?>" 
                                         alt="<?= $program['nama_program'] ?>" 
                                         style="max-width: 100px;">
                                </td>
                                <td><?= nl2br(htmlspecialchars(substr($program['deskripsi'], 0, 100))) ?>...</td>
                                <td><?= date('d/m/Y H:i', strtotime($program['tgl_update'])) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info mb-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $program['id_program'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger mb-1"
                                            onclick="deleteProgram(<?= $program['id_program'] ?>)">
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

    <!-- Modal Tambah Program -->
    <div class="modal fade" id="addProgramModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_program.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Program</label>
                            <input type="text" class="form-control" name="nama_program" required>
                        </div>
                         <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Urutan</label>
                                <input type="number" class="form-control" name="urutan" value="0" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manfaat Kegiatan</label>
                            <textarea class="form-control" name="manfaat_kegiatan" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*" required>
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

    <!-- Modal Edit untuk setiap program -->
    <?php foreach ($programs as $program): ?>
    <div class="modal fade" id="editModal<?= $program['id_program'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_program.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_program" value="<?= $program['id_program'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Program</label>
                            <input type="text" class="form-control" name="nama_program" 
                                   value="<?= htmlspecialchars($program['nama_program']) ?>" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Urutan</label>
                                <input type="number" class="form-control" name="urutan" 
                                       value="<?= $program['urutan'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-control" name="status">
                                    <option value="published" <?= $program['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="draft" <?= $program['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required><?= htmlspecialchars($program['deskripsi']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Manfaat Kegiatan</label>
                            <textarea class="form-control" name="manfaat_kegiatan" rows="4" required><?= htmlspecialchars($program['manfaat_kegiatan']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Saat Ini</label>
                            <img src="../img/program/<?= $program['gambar'] ?>" alt="Current Image" class="d-block mb-2" style="max-width: 200px;">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProgram(id) {
            if(confirm('Apakah anda yakin ingin menghapus program ini?')) {
                window.location.href = 'delete_program.php?id=' + id;
            }
        }
    </script>
</body>
</html>