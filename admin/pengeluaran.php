<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Cek jika tabel pengeluaran belum ada, buat tabelnya
try {
    $checkTableQuery = "SHOW TABLES LIKE 'pengeluaran'";
    $tableExists = $conn->query($checkTableQuery)->rowCount() > 0;
    
    if (!$tableExists) {
        $createTableSQL = "CREATE TABLE `pengeluaran` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tanggal` date NOT NULL,
            `jumlah` decimal(15,2) NOT NULL,
            `keterangan` text NOT NULL,
            `bukti` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $conn->exec($createTableSQL);
    }
} catch (PDOException $e) {
    // Log error (tidak menampilkan ke user untuk keamanan)
    error_log("Error creating table: " . $e->getMessage());
}

// Variabel untuk pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filter tanggal
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$whereClause = '';
$params = [];

if ($startDate && $endDate) {
    $whereClause = " WHERE tanggal BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
} elseif ($startDate) {
    $whereClause = " WHERE tanggal >= ?";
    $params[] = $startDate;
} elseif ($endDate) {
    $whereClause = " WHERE tanggal <= ?";
    $params[] = $endDate;
}

// Pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    if ($whereClause === '') {
        $whereClause = " WHERE keterangan LIKE ?";
    } else {
        $whereClause .= " AND keterangan LIKE ?";
    }
    $searchParam = "%$search%";
    $params[] = $searchParam;
}

// Hitung total data untuk pagination
$countQuery = "SELECT COUNT(*) FROM pengeluaran" . $whereClause;
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($params);
$totalData = $countStmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

// Ambil data pengeluaran
$query = "SELECT * FROM pengeluaran" . $whereClause . " ORDER BY tanggal DESC, id DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total pengeluaran
$totalQuery = "SELECT SUM(jumlah) as total_pengeluaran FROM pengeluaran" . $whereClause;
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->execute($params);
$totalPengeluaran = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_pengeluaran'] ?? 0;

// Tambah data pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pengeluaran'])) {
    $tanggal = $_POST['tanggal'];
    $jumlah = str_replace(['Rp', '.', ' '], '', $_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    
    // Upload bukti pengeluaran jika ada
    $buktiFilename = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['bukti']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Validasi tipe file
        if (in_array(strtolower($filetype), $allowed)) {
            // Buat nama file unik
            $buktiFilename = 'bukti_pengeluaran_' . date('YmdHis') . '_' . uniqid() . '.' . $filetype;
            
            // Buat direktori img/bukti_pengeluaran jika belum ada
            $uploadDir = '../img/bukti_pengeluaran';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $errorMessage = "Gagal membuat direktori upload. Silakan hubungi administrator.";
                }
            }
            
            $target = $uploadDir . '/' . $buktiFilename;
            
            // Upload file
            if (!move_uploaded_file($_FILES['bukti']['tmp_name'], $target)) {
                $errorMessage = "Gagal mengupload bukti pengeluaran. Silakan coba lagi.";
            }
        } else {
            $errorMessage = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.";
        }
    }
    
    // Tambahkan data ke database jika tidak ada error
    if (!isset($errorMessage)) {
        $insertStmt = $conn->prepare("INSERT INTO pengeluaran (tanggal, jumlah, keterangan, bukti) VALUES (?, ?, ?, ?)");
        if ($insertStmt->execute([$tanggal, $jumlah, $keterangan, $buktiFilename])) {
            $successMessage = "Data pengeluaran berhasil ditambahkan!";
            
            // Refresh data
            $stmt->execute($params);
            $pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Refresh total
            $totalStmt->execute($params);
            $totalPengeluaran = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_pengeluaran'] ?? 0;
        } else {
            $errorMessage = "Gagal menambahkan data pengeluaran.";
        }
    }
}

// Edit data pengeluaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pengeluaran'])) {
    $id = $_POST['id'];
    $tanggal = $_POST['tanggal'];
    $jumlah = str_replace(['Rp', '.', ' '], '', $_POST['jumlah']);
    $keterangan = $_POST['keterangan'];
    
    // Ambil data pengeluaran yang akan diedit
    $getStmt = $conn->prepare("SELECT bukti FROM pengeluaran WHERE id = ?");
    $getStmt->execute([$id]);
    $currentData = $getStmt->fetch(PDO::FETCH_ASSOC);
    $buktiFilename = $currentData['bukti'];
    
    // Upload bukti pengeluaran baru jika ada
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['bukti']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Validasi tipe file
        if (in_array(strtolower($filetype), $allowed)) {
            // Buat nama file unik
            $newBuktiFilename = 'bukti_pengeluaran_' . date('YmdHis') . '_' . uniqid() . '.' . $filetype;
            
            // Buat direktori img/bukti_pengeluaran jika belum ada
            $uploadDir = '../img/bukti_pengeluaran';
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $errorMessage = "Gagal membuat direktori upload. Silakan hubungi administrator.";
                }
            }
            
            $target = $uploadDir . '/' . $newBuktiFilename;
            
            // Upload file
            if (move_uploaded_file($_FILES['bukti']['tmp_name'], $target)) {
                // Hapus file lama jika ada
                if ($buktiFilename && file_exists($uploadDir . '/' . $buktiFilename)) {
                    unlink($uploadDir . '/' . $buktiFilename);
                }
                
                $buktiFilename = $newBuktiFilename;
            } else {
                $errorMessage = "Gagal mengupload bukti pengeluaran. Silakan coba lagi.";
            }
        } else {
            $errorMessage = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.";
        }
    }
    
    // Update data di database jika tidak ada error
    if (!isset($errorMessage)) {
        $updateStmt = $conn->prepare("UPDATE pengeluaran SET tanggal = ?, jumlah = ?, keterangan = ?, bukti = ? WHERE id = ?");
        if ($updateStmt->execute([$tanggal, $jumlah, $keterangan, $buktiFilename, $id])) {
            $successMessage = "Data pengeluaran berhasil diperbarui!";
            
            // Refresh data
            $stmt->execute($params);
            $pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Refresh total
            $totalStmt->execute($params);
            $totalPengeluaran = $totalStmt->fetch(PDO::FETCH_ASSOC)['total_pengeluaran'] ?? 0;
        } else {
            $errorMessage = "Gagal memperbarui data pengeluaran.";
        }
    }
}

// Hapus pengeluaran
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Ambil data bukti file sebelum dihapus
    $getStmt = $conn->prepare("SELECT bukti FROM pengeluaran WHERE id = ?");
    $getStmt->execute([$id]);
    $dataToDelete = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    $deleteStmt = $conn->prepare("DELETE FROM pengeluaran WHERE id = ?");
    if ($deleteStmt->execute([$id])) {
        // Hapus file bukti jika ada
        if (!empty($dataToDelete['bukti'])) {
            $filePath = '../img/bukti_pengeluaran/' . $dataToDelete['bukti'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $successMessage = "Data pengeluaran berhasil dihapus!";
        
        // Redirect agar refresh data
        header("Location: pengeluaran.php" . (isset($_GET['page']) ? "?page=" . $_GET['page'] : ""));
        exit();
    } else {
        $errorMessage = "Gagal menghapus data pengeluaran.";
    }
}

// Format angka dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengeluaran - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .summary-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
        }

        .summary-card .card-body {
            position: relative;
            z-index: 1;
        }

        .summary-card .icon-bg {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.1;
            z-index: 0;
            color: white;
        }

        .bg-expense { 
            background: linear-gradient(45deg, #dc3545, #f06548); 
            color: white; 
        }

        .table-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .avatar-placeholder {
            width: 40px;
            height: 40px;
            background-color: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>
    
    <div class="container my-4">
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h3 class="mb-0 fw-bold text-primary">Manajemen Pengeluaran</h3>
                <p class="text-muted mb-0 mt-1">Kelola data pengeluaran operasional.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="export_pengeluaran.php<?= isset($_GET['start_date']) ? '?start_date=' . $_GET['start_date'] : '' ?><?= isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="btn btn-outline-success">
                    <i class="fas fa-file-excel me-2"></i>Export
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Tambah Baru
                </button>
            </div>
        </div>
            
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $successMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= $errorMessage ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4 mb-4">
            <!-- Summary Card -->
            <div class="col-md-4">
                <div class="card summary-card bg-expense h-100">
                    <div class="card-body p-4">
                        <i class="fas fa-money-bill-wave icon-bg"></i>
                        <h6 class="text-white-50 text-uppercase fw-semibold mb-2">Total Pengeluaran</h6>
                        <h2 class="fw-bold mb-0"><?= formatRupiah($totalPengeluaran) ?></h2>
                        <div class="small text-white-50 mt-2">
                            <?php if ($startDate || $endDate): ?>
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?= $startDate ? date('d M Y', strtotime($startDate)) : 'Awal' ?> 
                                s/d 
                                <?= $endDate ? date('d M Y', strtotime($endDate)) : 'Sekarang' ?>
                            <?php else: ?>
                                <i class="fas fa-globe me-1"></i> Semua Periode
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3"><i class="fas fa-search me-2"></i>Filter & Pencarian</h6>
                        <form method="GET" action="pengeluaran.php" class="row g-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1">Dari Tanggal</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $startDate ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted mb-1">Sampai Tanggal</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $endDate ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted mb-1">Cari Keterangan</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Biaya konsumsi..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit">Cari</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pengeluaran Table -->
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" width="5%">#ID</th>
                                <th width="15%">Tanggal</th>
                                <th width="20%">Jumlah (Rp)</th>
                                <th>Keterangan</th>
                                <th width="10%">Bukti</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pengeluaran) > 0): ?>
                                <?php foreach ($pengeluaran as $p): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small">#<?= $p['id'] ?></td>
                                        <td>
                                            <div class="fw-medium text-dark"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
                                            <div class="small text-muted"><i class="far fa-clock me-1"></i><?= date('H:i', strtotime($p['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-danger"><?= formatRupiah($p['jumlah']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($p['keterangan']) ?></td>
                                        <td>
                                            <?php if (!empty($p['bukti'])): ?>
                                                <a href="../img/bukti_pengeluaran/<?= $p['bukti'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-paperclip me-1"></i>Lihat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-light text-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="pengeluaran.php?action=delete&id=<?= $p['id'] ?><?= isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Hapus data pengeluaran ini?')" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Pengeluaran</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST" action="pengeluaran.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" enctype="multipart/form-data">
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Tanggal</label>
                                                                    <input type="date" name="tanggal" class="form-control" value="<?= $p['tanggal'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Jumlah (Rp)</label>
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">Rp</span>
                                                                        <input type="text" name="jumlah" class="form-control money-format fw-bold" value="<?= number_format($p['jumlah'], 0, ',', '.') ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Keterangan</label>
                                                                    <textarea name="keterangan" class="form-control" rows="3" required><?= htmlspecialchars($p['keterangan']) ?></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label text-muted small">Bukti (Opsional)</label>
                                                                    <?php if (!empty($p['bukti'])): ?>
                                                                        <div class="mb-2 p-2 bg-light border rounded">
                                                                            <small class="text-success"><i class="fas fa-check me-1"></i>File saat ini: <?= $p['bukti'] ?></small>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="edit_pengeluaran" class="btn btn-primary">Simpan Perubahan</button>
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
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 text-light"></i><br>
                                        Belum ada data pengeluaran.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-end p-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Add Pengeluaran Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengeluaran Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="pengeluaran.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label text-muted small">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Jumlah (Rp)</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="jumlah" class="form-control money-format fw-bold" placeholder="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3" placeholder="Untuk keperluan apa..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted small">Bukti (Opsional)</label>
                            <input type="file" name="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_pengeluaran" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Alert auto-close
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 4000);
            });
            
            // Format currency input
            const formatMoney = function(input) {
                let value = input.value.replace(/\D/g, '');
                if (value) {
                    value = parseInt(value).toLocaleString('id-ID');
                }
                input.value = value;
            };
            
            document.querySelectorAll('.money-format').forEach(function(input) {
                formatMoney(input);
                input.addEventListener('input', function() {
                    formatMoney(this);
                });
            });
        });
    </script>
</body>
</html>