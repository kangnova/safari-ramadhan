<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Ambil data sponsor
try {
    $query = "SELECT * FROM sponsor ORDER BY urutan, tgl_update DESC";
    $stmt = $conn->query($query);
    $sponsors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kesalahan database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sponsor - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/modular/sortable.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Kelola Sponsor</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSponsorModal">
                <i class="bi bi-plus-lg"></i> Tambah Sponsor
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

        <!-- Daftar Sponsor -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px">Urutan</th>
                                <th>Logo</th>
                                <th>Nama Sponsor</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="sponsorList">
                            <?php foreach ($sponsors as $sponsor): ?>
                            <tr data-id="<?= $sponsor['id_sponsor'] ?>">
                                <td>
                                    <i class="bi bi-grip-vertical handle" style="cursor: move"></i>
                                </td>
                                <td>
                                    <img src="../img/sponsor/<?= htmlspecialchars($sponsor['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>" 
                                         style="max-height: 50px;">
                                </td>
                                <td><?= htmlspecialchars($sponsor['nama_sponsor']) ?></td>
                                <td>
                                    <?php if (!empty($sponsor['url'])): ?>
                                        <a href="<?= htmlspecialchars($sponsor['url']) ?>" target="_blank">
                                            <?= htmlspecialchars($sponsor['url']) ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $sponsor['status'] === 'aktif' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($sponsor['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info mb-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $sponsor['id_sponsor'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger mb-1"
                                            onclick="deleteSponsor(<?= $sponsor['id_sponsor'] ?>)">
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

    <!-- Modal Tambah Sponsor -->
    <div class="modal fade" id="addSponsorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Sponsor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="save_sponsor.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Sponsor</label>
                            <input type="text" class="form-control" name="nama_sponsor" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Website (Opsional)</label>
                            <input type="url" class="form-control" name="url" placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="gambar" 
                                   accept="image/*" required>
                            <small class="text-muted">Ukuran yang disarankan: 200x100 pixel</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
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

    <!-- Modal Edit untuk setiap sponsor -->
    <?php foreach ($sponsors as $sponsor): ?>
    <div class="modal fade" id="editModal<?= $sponsor['id_sponsor'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Sponsor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_sponsor.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_sponsor" value="<?= $sponsor['id_sponsor'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Sponsor</label>
                            <input type="text" class="form-control" name="nama_sponsor" 
                                   value="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Website (Opsional)</label>
                            <input type="url" class="form-control" name="url" 
                                   value="<?= htmlspecialchars($sponsor['url']) ?>" 
                                   placeholder="https://">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo Saat Ini</label>
                            <img src="../img/sponsor/<?= $sponsor['gambar'] ?>" 
                                 class="d-block mb-2" style="max-height: 100px;">
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah logo</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="aktif" <?= $sponsor['status'] === 'aktif' ? 'selected' : '' ?>>
                                    Aktif
                                </option>
                                <option value="nonaktif" <?= $sponsor['status'] === 'nonaktif' ? 'selected' : '' ?>>
                                    Nonaktif
                                </option>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Initialize drag and drop sorting
        new Sortable(document.getElementById('sponsorList'), {
            handle: '.handle',
            animation: 150,
            onEnd: function(evt) {
                // Get new order
                const items = evt.to.children;
                const newOrder = [];
                for (let i = 0; i < items.length; i++) {
                    newOrder.push({
                        id: items[i].dataset.id,
                        order: i + 1
                    });
                }

                // Save new order to database
                fetch('update_sponsor_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(newOrder)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Optional: show success message
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });

        function deleteSponsor(id) {
            if(confirm('Apakah anda yakin ingin menghapus sponsor ini?')) {
                window.location.href = 'delete_sponsor.php?id=' + id;
            }
        }
    </script>
</body>
</html>