<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Koneksi
require_once '../koneksi.php';
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM pesan_kontak WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Pesan berhasil dihapus.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus pesan.";
    }
    header("Location: pesan.php");
    exit();
}

// Handle Mark as Read
if (isset($_GET['read'])) {
    $id = $_GET['read'];
    try {
        $stmt = $conn->prepare("UPDATE pesan_kontak SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {}
    header("Location: pesan.php");
    exit();
}

// Ambil Data Pesan
try {
    $query = "SELECT pk.*, l.nama_lembaga 
              FROM pesan_kontak pk 
              JOIN lembaga l ON pk.lembaga_id = l.id 
              ORDER BY pk.created_at DESC";
    $stmt = $conn->query($query);
    $pesan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Masuk - Admin Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
</head>
<body>
    
    <?php require_once 'includes/header.php'; ?>
    
    <div class="container my-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Pesan Masuk</h1>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Lembaga</th>
                                <th>Subjek</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach($pesan_list as $row): ?>
                            <tr class="<?= $row['status'] == 'unread' ? 'table-active fw-bold' : '' ?>">
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_lembaga']) ?></td>
                                <td><?= htmlspecialchars($row['subjek']) ?></td>
                                <td>
                                    <?php if($row['status'] == 'unread'): ?>
                                        <span class="badge bg-danger">Belum Dibaca</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Dibaca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalPesan<?= $row['id'] ?>" onclick="markAsRead(<?= $row['id'] ?>)">
                                        <i class="bx bx-show"></i> Baca
                                    </button>
                                    <a href="pesan.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus pesan ini?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal Detail Pesan -->
                            <div class="modal fade" id="modalPesan<?= $row['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Pesan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body fw-normal">
                                            <p><strong>Dari:</strong> <?= htmlspecialchars($row['nama_lembaga']) ?></p>
                                            <p><strong>Waktu:</strong> <?= date('d F Y H:i', strtotime($row['created_at'])) ?></p>
                                            <hr>
                                            <p><strong>Subjek:</strong> <?= htmlspecialchars($row['subjek']) ?></p>
                                            <div class="bg-light p-3 rounded">
                                                <?= nl2br(htmlspecialchars($row['pesan'])) ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            <!-- Optional: Link to directly open WA for reply if we had phone number in query -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                "order": [[ 4, "desc" ], [ 1, "desc" ]] // Sort by status then date
            });
        });

        // Simple AJAX to mark as read when modal opens (optional enhancement)
        // For now relying on direct link or manual refresh, but let's add simple fetch
        function markAsRead(id) {
            fetch('pesan.php?read=' + id);
        }
    </script>
</body>
</html>
