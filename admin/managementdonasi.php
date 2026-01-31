<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Variabel untuk pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$whereClause = '';
$params = [];

if ($statusFilter !== '') {
    $whereClause = " WHERE status = ?";
    $params[] = $statusFilter;
}

// Pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    if ($whereClause === '') {
        $whereClause = " WHERE (nama_donatur LIKE ? OR token LIKE ? OR email LIKE ?)";
    } else {
        $whereClause .= " AND (nama_donatur LIKE ? OR token LIKE ? OR email LIKE ?)";
    }
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Hitung total data untuk pagination
$countQuery = "SELECT COUNT(*) FROM donasi" . $whereClause;
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalData = $countStmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

// Ambil data donasi
// Ambil data donasi
$query = "SELECT d.*, pd.judul as program_judul 
          FROM donasi d 
          LEFT JOIN program_donasi pd ON d.program_id = pd.id " . 
          $whereClause . 
          " ORDER BY d.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$donasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proses perubahan status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $donasiId = $_POST['donasi_id'];
    $newStatus = $_POST['status'];
    
    $updateStmt = $conn->prepare("UPDATE donasi SET status = ?, updated_at = NOW() WHERE id = ?");
    if ($updateStmt->execute([$newStatus, $donasiId])) {
        $successMessage = "Status donasi berhasil diperbarui!";
        
        // Refresh data
        $stmt->execute($params);
        $donasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $errorMessage = "Gagal memperbarui status donasi.";
    }
}

// Hapus donasi
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $donasiId = $_GET['id'];
    
    $deleteStmt = $conn->prepare("DELETE FROM donasi WHERE id = ?");
    if ($deleteStmt->execute([$donasiId])) {
        $successMessage = "Donasi berhasil dihapus!";
        
        // Redirect agar refresh data
        header("Location: managementdonasi.php");
        exit();
    } else {
        $errorMessage = "Gagal menghapus donasi.";
    }
}

// Format nomimal dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Status badge color
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'success':
            return 'bg-success';
        case 'failed':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Hitung total donasi berdasarkan status
$totalQueryAll = "SELECT COUNT(*) as total, SUM(nominal) as total_nominal FROM donasi";
$totalStmtAll = $conn->query($totalQueryAll);
$totalAll = $totalStmtAll->fetch(PDO::FETCH_ASSOC);

$totalQuerySuccess = "SELECT COUNT(*) as total, SUM(nominal) as total_nominal FROM donasi WHERE status = 'success'";
$totalStmtSuccess = $conn->query($totalQuerySuccess);
$totalSuccess = $totalStmtSuccess->fetch(PDO::FETCH_ASSOC);

$totalQueryPending = "SELECT COUNT(*) as total, SUM(nominal) as total_nominal FROM donasi WHERE status = 'pending'";
$totalStmtPending = $conn->query($totalQueryPending);
$totalPending = $totalStmtPending->fetch(PDO::FETCH_ASSOC);

$totalQueryFailed = "SELECT COUNT(*) as total, SUM(nominal) as total_nominal FROM donasi WHERE status = 'failed'";
$totalStmtFailed = $conn->query($totalQueryFailed);
$totalFailed = $totalStmtFailed->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Donasi - Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manajemen Donasi</h2>
                <a href="export_donasi.php" class="btn btn-primary">
                    <i class="fas fa-download"></i> Export Data
                </a>
            </div>
            
            <?php if (isset($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $successMessage ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $errorMessage ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Dashboard Stats -->
            <div class="row dashboard-stats">
                <div class="col-md-3">
                    <div class="card text-bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Donasi</h5>
                            <p class="card-text fs-4"><?= $totalAll['total'] ?? 0 ?></p>
                            <p class="card-text"><?= formatRupiah($totalAll['total_nominal'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Donasi Sukses</h5>
                            <p class="card-text fs-4"><?= $totalSuccess['total'] ?? 0 ?></p>
                            <p class="card-text"><?= formatRupiah($totalSuccess['total_nominal'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Donasi Pending</h5>
                            <p class="card-text fs-4"><?= $totalPending['total'] ?? 0 ?></p>
                            <p class="card-text"><?= formatRupiah($totalPending['total_nominal'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Donasi Gagal</h5>
                            <p class="card-text fs-4"><?= $totalFailed['total'] ?? 0 ?></p>
                            <p class="card-text"><?= formatRupiah($totalFailed['total_nominal'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="managementdonasi.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Filter Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="success" <?= $statusFilter === 'success' ? 'selected' : '' ?>>Success</option>
                                <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="search" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan nama, token, atau email" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Donasi Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Token</th>
                                    <th>Nama Donatur</th>
                                    <th>Program</th>
                                    <th>Nominal</th>
                                    <th>Metode</th>
                                    <th>Status</th>
                                    <th>Bukti Transfer</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($donasi) > 0): ?>
                                    <?php foreach ($donasi as $d): ?>
                                        <tr>
                                            <td><?= $d['id'] ?></td>
                                            <td><?= $d['token'] ?></td>
                                            <td><?= $d['is_anonim'] ? '<em>Anonim</em>' : htmlspecialchars($d['nama_donatur']) ?></td>
                                            <td><?= htmlspecialchars($d['program_judul'] ?? 'Umum') ?></td>
                                            <td><?= formatRupiah($d['nominal']) ?></td>
                                            <td><?= $d['metode_pembayaran'] ?? '-' ?></td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($d['status']) ?>">
                                                    <?= ucfirst($d['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($d['bukti_transfer'])): ?>
                                                    <a href="../img/bukti_transfer/<?= $d['bukti_transfer'] ?>" target="_blank">
                                                        <img src="../img/bukti_transfer/<?= $d['bukti_transfer'] ?>" alt="Bukti Transfer" class="img-thumbnail">
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada bukti</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($d['created_at'])) ?></td>
                                            <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $d['id'] ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php
                                                    // Prepare WA Message
                                                    $waPhone = $d['whatsapp'];
                                                    if (substr($waPhone, 0, 1) == '0') $waPhone = '62' . substr($waPhone, 1);
                                                    
                                                    $pesanWA = "Assalamu'alaikum *" . $d['nama_donatur'] . "*,\n\n";
                                                    $pesanWA .= "Terima kasih atas donasi Anda sebesar *" . formatRupiah($d['nominal']) . "* untuk program *Ifthar Ramadhan*.\n\n";
                                                    $pesanWA .= "Donasi Anda telah kami terima dengan baik.\n";
                                                    $pesanWA .= "Semoga menjadi amal jariyah yang barokah.\n\n";
                                                    $pesanWA .= "_Panitia Ifthar Ramadhan_";
                                                    
                                                    $linkWA = "https://wa.me/$waPhone?text=" . urlencode($pesanWA);
                                                    ?>
                                                    
                                                    <a href="<?= $linkWA ?>" target="_blank" class="btn btn-sm btn-success" title="Kirim WA">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>

                                                    <?php if ($d['status'] === 'success'): ?>
                                                    <a href="cetak_kwitansi.php?id=<?= $d['id'] ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Cetak Kwitansi">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <?php endif; ?>

                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?= $d['id'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="managementdonasi.php?action=delete&id=<?= $d['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus donasi ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                                
                                                <!-- Detail Modal -->
                                                <div class="modal fade" id="detailModal<?= $d['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $d['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="detailModalLabel<?= $d['id'] ?>">Detail Donasi</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <dl class="row">
                                                                    <dt class="col-sm-4">ID</dt>
                                                                    <dd class="col-sm-8"><?= $d['id'] ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Token</dt>
                                                                    <dd class="col-sm-8"><?= $d['token'] ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Nama</dt>
                                                                    <dd class="col-sm-8"><?= $d['is_anonim'] ? '<em>Anonim</em>' : htmlspecialchars($d['nama_donatur']) ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Email</dt>
                                                                    <dd class="col-sm-8"><?= htmlspecialchars($d['email']) ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">WhatsApp</dt>
                                                                    <dd class="col-sm-8"><?= htmlspecialchars($d['whatsapp']) ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Program</dt>
                                                                    <dd class="col-sm-8"><?= htmlspecialchars($d['program_judul'] ?? 'Umum') ?></dd>

                                                                    <dt class="col-sm-4">Nominal</dt>
                                                                    <dd class="col-sm-8"><?= formatRupiah($d['nominal']) ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Metode</dt>
                                                                    <dd class="col-sm-8"><?= $d['metode_pembayaran'] ?? '-' ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Status</dt>
                                                                    <dd class="col-sm-8">
                                                                        <span class="badge <?= getStatusBadgeClass($d['status']) ?>">
                                                                            <?= ucfirst($d['status']) ?>
                                                                        </span>
                                                                    </dd>

                                                                    <dt class="col-sm-4">Pesan / Doa</dt>
                                                                    <dd class="col-sm-8 fst-italic">
                                                                        <?= !empty($d['pesan']) ? nl2br(htmlspecialchars($d['pesan'])) : '-' ?>
                                                                    </dd>
                                                                    
                                                                    <dt class="col-sm-4">Tanggal</dt>
                                                                    <dd class="col-sm-8"><?= date('d M Y H:i', strtotime($d['created_at'])) ?></dd>
                                                                    
                                                                    <dt class="col-sm-4">Diperbarui</dt>
                                                                    <dd class="col-sm-8"><?= date('d M Y H:i', strtotime($d['updated_at'])) ?></dd>
                                                                    
                                                                    <?php if (!empty($d['bukti_transfer'])): ?>
                                                                        <dt class="col-sm-4">Bukti Transfer</dt>
                                                                        <dd class="col-sm-8">
                                                                            <img src="../img/bukti_transfer/<?= $d['bukti_transfer'] ?>" alt="Bukti Transfer" class="img-fluid">
                                                                        </dd>
                                                                    <?php endif; ?>
                                                                </dl>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Update Status Modal -->
                                                <div class="modal fade" id="updateModal<?= $d['id'] ?>" tabindex="-1" aria-labelledby="updateModalLabel<?= $d['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="updateModalLabel<?= $d['id'] ?>">Update Status Donasi</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="managementdonasi.php">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="donasi_id" value="<?= $d['id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label for="status<?= $d['id'] ?>" class="form-label">Status</label>
                                                                        <select name="status" id="status<?= $d['id'] ?>" class="form-select">
                                                                            <option value="pending" <?= $d['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                            <option value="success" <?= $d['status'] === 'success' ? 'selected' : '' ?>>Success</option>
                                                                            <option value="failed" <?= $d['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="update_status" class="btn btn-primary">Simpan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data donasi</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>