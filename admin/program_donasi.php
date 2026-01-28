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
    // Query untuk mengambil data program donasi
    $query = "SELECT * FROM program_donasi ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching program_donasi: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Program Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">
    
    <!-- Navbar -->
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Program Donasi</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                <i class="bi bi-plus-lg"></i> Tambah Program Donasi
            </button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Tabel Program Donasi -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="programTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul Program</th>
                                <th>Target</th>
                                <th>Gambar</th>
                                <th>Periode</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programs as $index => $program): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($program['judul']) ?></strong><br>
                                    <small class="text-muted"><?= substr(htmlspecialchars($program['deskripsi']), 0, 50) ?>...</small>
                                </td>
                                <td>Rp <?= number_format($program['target_nominal'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if (!empty($program['gambar_utama'])): ?>
                                        <img src="../<?= $program['gambar_utama'] ?>" class="rounded" style="height: 50px; width: auto;">
                                    <?php else: ?>
                                        <span class="text-muted">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        Mulai: <?= $program['tanggal_mulai'] ? date('d/m/Y', strtotime($program['tanggal_mulai'])) : '-' ?><br>
                                        Selesai: <?= $program['tanggal_selesai'] ? date('d/m/Y', strtotime($program['tanggal_selesai'])) : '-' ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($program['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($program['status'] === 'inactive'): ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white mb-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $program['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger mb-1"
                                            onclick="deleteProgram(<?= $program['id'] ?>)">
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
                    <h5 class="modal-title">Tambah Program Donasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_program_donasi.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Program</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Nominal (Rp)</label>
                            <input type="number" class="form-control" name="target_nominal" required min="0">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="tanggal_mulai" required value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" name="tanggal_selesai" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Utama</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG. Maks 2MB.</small>
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
    <div class="modal fade" id="editModal<?= $program['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Program Donasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_program_donasi.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $program['id'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Program</label>
                            <input type="text" class="form-control" name="judul" value="<?= htmlspecialchars($program['judul']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Nominal (Rp)</label>
                            <input type="number" class="form-control" name="target_nominal" value="<?= $program['target_nominal'] ?>" required min="0">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="tanggal_mulai" value="<?= $program['tanggal_mulai'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" name="tanggal_selesai" value="<?= $program['tanggal_selesai'] ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status">
                                <option value="active" <?= $program['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $program['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="completed" <?= $program['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" rows="4" required><?= htmlspecialchars($program['deskripsi']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar Utama</label>
                            <?php if(!empty($program['gambar_utama'])): ?>
                                <img src="../<?= $program['gambar_utama'] ?>" class="d-block mb-2 rounded" style="max-width: 150px;">
                            <?php endif; ?>
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar.</small>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#programTable').DataTable();
        });

        function deleteProgram(id) {
            if(confirm('Apakah anda yakin ingin menghapus program donasi ini?')) {
                window.location.href = 'delete_program_donasi.php?id=' + id;
            }
        }
    </script>
</body>
</html>
