<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

// Initialize variables
$stats = [
    'total_lembaga' => 0,
    'total_santri' => 0,
    'total_pengajuan_yatim' => 0,
    'total_approved' => 0
];
$pendaftar = [];

try {
    // Query untuk data statistik
    $queryStatistik = "
        SELECT 
            COUNT(DISTINCT id) as total_lembaga,
            SUM(jumlah_santri) as total_santri,
            COUNT(CASE WHEN santri_yatim IS NOT NULL THEN 1 END) as total_pengajuan_yatim,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as total_approved
        FROM ifthar";

    $stmtStats = $conn->prepare($queryStatistik);
    $stmtStats->execute();
    $fetchedStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($fetchedStats) {
        $stats = $fetchedStats;
    }

    // Query untuk data pendaftar
    $query = "SELECT * FROM ifthar ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pendaftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error in pendaftar_ifthar.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftar Ifthar 1000 Santri</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .badge-status {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
/* Tambahkan di bagian style yang sudah ada */
.btn-group {
    display: flex;
    gap: 2px;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.badge {
    padding: 0.5em 0.8em;
    font-weight: 500;
}

/* Hover effect untuk tombol */
.btn-group .btn:hover {
    opacity: 0.8;
}

/* Tooltip styling */
[title] {
    position: relative;
    cursor: pointer;
}
    </style>
</head>
<body class="bg-light">
    
    <?php require_once 'includes/header.php'; ?>

    <div class="container-fluid mt-4 px-4">
        <h2 class="text-center mb-4">Dashboard Ifthar 1000 Santri</h2>
        
        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-buildings stats-icon"></i>
                        <h5 class="card-title">Total Lembaga</h5>
                        <h2 class="mb-0"><?= $stats['total_lembaga'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-people stats-icon"></i>
                        <h5 class="card-title">Total Santri</h5>
                        <h2 class="mb-0"><?= number_format($stats['total_santri']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
    <a href="pengajuan_yatim.php" class="text-decoration-none">
        <div class="card stats-card h-100 bg-info text-white" style="cursor: pointer;">
            <div class="card-body text-center">
                <i class="bi bi-heart stats-icon"></i>
                <h5 class="card-title">Pengajuan Yatim</h5>
                <h2 class="mb-0"><?= $stats['total_pengajuan_yatim'] ?></h2>
                <div class="mt-2 small">
                    <span class="d-inline-block">Lihat Detail <i class="bi bi-arrow-right"></i></span>
                </div>
            </div>
        </div>
    </a>
</div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-check-circle stats-icon"></i>
                        <h5 class="card-title">Approved</h5>
                        <h2 class="mb-0"><?= $stats['total_approved'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Data Pendaftar Ifthar</h5>
                    <div>
                        <a href="export-ifthar.php" class="btn btn-success btn-sm">
                            <i class="bi bi-file-excel me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pendaftarTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Lembaga</th>
                                <th>Penanggung Jawab</th>
                                <th>No. WA</th>
                                <th>Jumlah Santri</th>
                                <th>Status</th>
                                <th width="100">Aksi</th>
                            </tr>
                        </thead>
                        <!-- Pada bagian tabel body -->
<tbody>
    <?php foreach ($pendaftar as $index => $data): ?>
    <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($data['asal_lembaga']) ?></td>
        <td><?= htmlspecialchars($data['nama_lengkap']) ?></td>
        <td>
            <a href="https://wa.me/<?= $data['no_hp'] ?>" target="_blank">
                <?= $data['no_hp'] ?>
            </a>
        </td>
        <td><?= $data['jumlah_santri'] ?></td>
        <td>
            <?php
            $statusClass = '';
            $statusText = '';
            switch($data['status']) {
                case 'approved':
                    $statusClass = 'success';
                    $statusText = 'Disetujui';
                    break;
                case 'rejected':
                    $statusClass = 'danger';
                    $statusText = 'Ditolak';
                    break;
                default:
                    $statusClass = 'warning';
                    $statusText = 'Pending';
            }
            ?>
            <span class="badge bg-<?= $statusClass ?>">
                <?= $statusText ?>
            </span>
        </td>
        <td>
            <div class="btn-group">
                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $data['id'] ?>" title="Detail">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="updateStatus(<?= $data['id'] ?>, 'approved')" title="Setujui">
                    <i class="bi bi-check-circle"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="updateStatus(<?= $data['id'] ?>, 'rejected')" title="Tolak">
                    <i class="bi bi-x-circle"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteRegistration(<?= $data['id'] ?>)" title="Hapus">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail -->
    <?php foreach ($pendaftar as $data): ?>
    <div class="modal fade" id="detailModal<?= $data['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Detail <?= htmlspecialchars($data['asal_lembaga']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Informasi Lembaga</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="40%">Email</td>
                                    <td><?= htmlspecialchars($data['email']) ?></td>
                                </tr>
                                <tr>
                                    <td>Penanggung Jawab</td>
                                    <td><?= htmlspecialchars($data['nama_lengkap']) ?></td>
                                </tr>
                                <tr>
                                    <td>Jumlah Santri</td>
                                    <td><?= $data['jumlah_santri'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Pengajuan Santri Yatim</h6>
                            <p><?= $data['santri_yatim'] ? nl2br(htmlspecialchars($data['santri_yatim'])) : 'Tidak ada pengajuan' ?></p>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-primary">Status dan Aksi</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success btn-sm" onclick="updateStatus(<?= $data['id'] ?>, 'approved')">
                                    <i class="bi bi-check-circle"></i> Approve
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="updateStatus(<?= $data['id'] ?>, 'rejected')">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#pendaftarTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                },
                pageLength: 25,
                order: [[0, 'asc']]
            });
        });

        function getBadgeColor(status) {
            switch(status) {
                case 'approved': return 'success';
                case 'rejected': return 'danger';
                default: return 'warning';
            }
        }

        function updateStatus(id, status) {
            if(confirm('Apakah Anda yakin ingin mengubah status pendaftaran ini?')) {
                $.ajax({
                    url: 'update_ifthar_status.php',
                    type: 'POST',
                    data: {
                        id: id,
                        status: status
                    },
                    success: function(response) {
                        alert('Status berhasil diperbarui');
                        location.reload();
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat memperbarui status');
                    }
                });
            }
        }

        function deleteRegistration(id) {
            if(confirm('Apakah Anda yakin ingin menghapus data pendaftaran ini?')) {
                $.ajax({
                    url: 'delete_ifthar.php',
                    type: 'POST',
                    data: {id: id},
                    success: function(response) {
                        alert('Data berhasil dihapus');
                        location.reload();
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat menghapus data');
                    }
                });
            }
        }
    </script>
</body>
</html>