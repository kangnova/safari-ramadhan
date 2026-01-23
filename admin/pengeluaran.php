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
    <title>Manajemen Pengeluaran - Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manajemen Pengeluaran</h2>
                <div>
                    <a href="export_pengeluaran.php<?= isset($_GET['start_date']) ? '?start_date=' . $_GET['start_date'] : '' ?><?= isset($_GET['end_date']) ? '&end_date=' . $_GET['end_date'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" class="btn btn-primary me-2">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus"></i> Tambah Pengeluaran
                    </button>
                </div>
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
            
            <!-- Summary Card -->
            <div class="card mb-4 summary-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="card-title">Total Pengeluaran</h4>
                            <?php if ($startDate || $endDate): ?>
                                <p class="text-muted">
                                    Periode: 
                                    <?= $startDate ? date('d M Y', strtotime($startDate)) : 'Awal' ?> 
                                    - 
                                    <?= $endDate ? date('d M Y', strtotime($endDate)) : 'Sekarang' ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <h3 class="text-success"><?= formatRupiah($totalPengeluaran) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="pengeluaran.php" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $startDate ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $endDate ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Cari berdasarkan keterangan" value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Pengeluaran Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Jumlah</th>
                                    <th>Keterangan</th>
                                    <th>Bukti</th>
                                    <th>Ditambahkan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pengeluaran) > 0): ?>
                                    <?php foreach ($pengeluaran as $p): ?>
                                        <tr>
                                            <td><?= $p['id'] ?></td>
                                            <td><?= date('d M Y', strtotime($p['tanggal'])) ?></td>
                                            <td><?= formatRupiah($p['jumlah']) ?></td>
                                            <td><?= htmlspecialchars($p['keterangan']) ?></td>
                                            <td>
                                                <?php if (!empty($p['bukti'])): ?>
                                                    <a href="../img/bukti_pengeluaran/<?= $p['bukti'] ?>" target="_blank">
                                                        <img src="../img/bukti_pengeluaran/<?= $p['bukti'] ?>" alt="Bukti Pengeluaran" class="img-thumbnail">
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada bukti</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="pengeluaran.php?action=delete&id=<?= $p['id'] ?><?= isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data pengeluaran ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                                
                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $p['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editModalLabel<?= $p['id'] ?>">Edit Pengeluaran</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST" action="pengeluaran.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" enctype="multipart/form-data">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label for="tanggal<?= $p['id'] ?>" class="form-label">Tanggal</label>
                                                                        <input type="date" name="tanggal" id="tanggal<?= $p['id'] ?>" class="form-control" value="<?= $p['tanggal'] ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="jumlah<?= $p['id'] ?>" class="form-label">Jumlah (Rp)</label>
                                                                        <input type="text" name="jumlah" id="jumlah<?= $p['id'] ?>" class="form-control money-format" value="<?= number_format($p['jumlah'], 0, ',', '.') ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="keterangan<?= $p['id'] ?>" class="form-label">Keterangan</label>
                                                                        <textarea name="keterangan" id="keterangan<?= $p['id'] ?>" class="form-control" rows="3" required><?= htmlspecialchars($p['keterangan']) ?></textarea>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="bukti<?= $p['id'] ?>" class="form-label">Bukti Pengeluaran</label>
                                                                        <?php if (!empty($p['bukti'])): ?>
                                                                            <div class="mb-2">
                                                                                <img src="../img/bukti_pengeluaran/<?= $p['bukti'] ?>" alt="Bukti Pengeluaran" class="img-fluid mb-2" style="max-height: 200px;">
                                                                                <div class="form-text">Upload file baru untuk mengganti bukti ini.</div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <input type="file" name="bukti" id="bukti<?= $p['id'] ?>" class="form-control" accept="image/jpeg,image/png,application/pdf">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
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
                                        <td colspan="7" class="text-center">Tidak ada data pengeluaran</td>
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
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $startDate ? '&start_date=' . $startDate : '' ?><?= $endDate ? '&end_date=' . $endDate : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" aria-label="Next">
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

    
    <!-- Add Pengeluaran Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="pengeluaran.php<?= isset($_GET['page']) ? '?page=' . $_GET['page'] : '' ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah (Rp)</label>
                            <input type="text" name="jumlah" id="jumlah" class="form-control money-format" placeholder="Contoh: 100.000" required>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea name="keterangan" id="keterangan" class="form-control" rows="3" placeholder="Jelaskan penggunaan dana..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="bukti" class="form-label">Bukti Pengeluaran</label>
                            <input type="file" name="bukti" id="bukti" class="form-control" accept="image/jpeg,image/png,application/pdf">
                            <div class="form-text">Upload nota/kwitansi sebagai bukti pengeluaran (opsional).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_pengeluaran" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Alert auto-close
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Format currency input
            const formatMoney = function(input) {
                // Remove non-digit characters
                let value = input.value.replace(/\D/g, '');
                
                // Format with thousand separator
                if (value) {
                    value = parseInt(value).toLocaleString('id-ID');
                }
                
                input.value = value;
            };
            
            // Apply currency format to all money format inputs
            document.querySelectorAll('.money-format').forEach(function(input) {
                // Format initial value
                formatMoney(input);
                
                // Format on input
                input.addEventListener('input', function() {
                    formatMoney(this);
                });
            });
        });
    </script>
</body>
</html>