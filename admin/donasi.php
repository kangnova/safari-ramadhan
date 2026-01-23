<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Statistik Donasi
$totalDonasiQuery = "SELECT COUNT(*) as total_donasi, 
                           SUM(nominal) as total_nominal,
                           SUM(CASE WHEN status = 'success' THEN nominal ELSE 0 END) as total_success,
                           COUNT(CASE WHEN status = 'success' THEN 1 END) as count_success,
                           SUM(CASE WHEN status = 'pending' THEN nominal ELSE 0 END) as total_pending,
                           COUNT(CASE WHEN status = 'pending' THEN 1 END) as count_pending
                    FROM donasi";
$donasiStats = $conn->query($totalDonasiQuery)->fetch(PDO::FETCH_ASSOC);

// Statistik Pengeluaran
$checkTableQuery = "SHOW TABLES LIKE 'pengeluaran'";
$tableExists = $conn->query($checkTableQuery)->rowCount() > 0;

if ($tableExists) {
    $totalPengeluaranQuery = "SELECT COUNT(*) as total_pengeluaran, SUM(jumlah) as total_jumlah FROM pengeluaran";
    $pengeluaranStats = $conn->query($totalPengeluaranQuery)->fetch(PDO::FETCH_ASSOC);
} else {
    $pengeluaranStats = [
        'total_pengeluaran' => 0,
        'total_jumlah' => 0
    ];
}

// Hitung saldo = total donasi sukses - total pengeluaran
$saldo = ($donasiStats['total_success'] ?? 0) - ($pengeluaranStats['total_jumlah'] ?? 0);

// Data untuk grafik (donasi per bulan)
$donasiPerBulanQuery = "SELECT 
                         DATE_FORMAT(created_at, '%Y-%m') as bulan,
                         SUM(CASE WHEN status = 'success' THEN nominal ELSE 0 END) as total_donasi
                       FROM donasi
                       WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                       ORDER BY bulan";
$donasiPerBulan = $conn->query($donasiPerBulanQuery)->fetchAll(PDO::FETCH_ASSOC);

// Data untuk grafik (pengeluaran per bulan)
if ($tableExists) {
    $pengeluaranPerBulanQuery = "SELECT 
                                DATE_FORMAT(tanggal, '%Y-%m') as bulan,
                                SUM(jumlah) as total_pengeluaran
                              FROM pengeluaran
                              WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                              GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
                              ORDER BY bulan";
    $pengeluaranPerBulan = $conn->query($pengeluaranPerBulanQuery)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pengeluaranPerBulan = [];
}

// Donasi terbaru
$donasiTerbaruQuery = "SELECT * FROM donasi ORDER BY created_at DESC LIMIT 5";
$donasiTerbaru = $conn->query($donasiTerbaruQuery)->fetchAll(PDO::FETCH_ASSOC);

// Pengeluaran terbaru
if ($tableExists) {
    $pengeluaranTerbaruQuery = "SELECT * FROM pengeluaran ORDER BY tanggal DESC, created_at DESC LIMIT 5";
    $pengeluaranTerbaru = $conn->query($pengeluaranTerbaruQuery)->fetchAll(PDO::FETCH_ASSOC);
} else {
    $pengeluaranTerbaru = [];
}

// Format angka dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Untuk memformat bulan dalam bahasa Indonesia
function formatBulan($bulan) {
    $date = new DateTime($bulan . '-01');
    return $date->format('M Y');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Donasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding: 20px 0;
            background-color: #343a40;
            color: white;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 10px 20px;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .dashboard-stats .card {
            transition: transform 0.3s;
        }
        
        .dashboard-stats .card:hover {
            transform: translateY(-5px);
        }
        
        .card-saldo {
            border-left: 4px solid #28a745;
        }
        
        .list-group-item {
            border-left: 0;
            border-right: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
            <h2 class="mb-4">Dashboard</h2>
            
            <!-- Stats -->
            <div class="row dashboard-stats mb-4">
                <div class="col-md-3">
                    <div class="card text-bg-primary h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Donasi</h5>
                            <p class="card-text fs-4"><?= $donasiStats['total_donasi'] ?? 0 ?> Donatur</p>
                            <p class="card-text"><?= formatRupiah($donasiStats['total_nominal'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-success h-100">
                        <div class="card-body">
                            <h5 class="card-title">Donasi Sukses</h5>
                            <p class="card-text fs-4"><?= $donasiStats['count_success'] ?? 0 ?> Donatur</p>
                            <p class="card-text"><?= formatRupiah($donasiStats['total_success'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-bg-warning h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Pengeluaran</h5>
                            <p class="card-text fs-4"><?= $pengeluaranStats['total_pengeluaran'] ?? 0 ?> Transaksi</p>
                            <p class="card-text"><?= formatRupiah($pengeluaranStats['total_jumlah'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 card-saldo">
                        <div class="card-body">
                            <h5 class="card-title">Saldo Saat Ini</h5>
                            <p class="card-text fs-4 <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatRupiah(abs($saldo)) ?>
                            </p>
                            <p class="card-text small text-muted">
                                Donasi Sukses - Total Pengeluaran
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Tren Donasi (6 Bulan Terakhir)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="donasiChart" width="400" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Tren Pengeluaran (6 Bulan Terakhir)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="pengeluaranChart" width="400" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Donasi Terbaru</h5>
                            <a href="donasi.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (count($donasiTerbaru) > 0): ?>
                                    <?php foreach ($donasiTerbaru as $donasi): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold">
                                                    <?= $donasi['is_anonim'] ? 'Anonim' : htmlspecialchars($donasi['nama_donatur']) ?>
                                                    <span class="badge <?= $donasi['status'] === 'success' ? 'bg-success' : ($donasi['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= ucfirst($donasi['status']) ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('d M Y H:i', strtotime($donasi['created_at'])) ?>
                                                </small>
                                            </div>
                                            <span class="fw-bold"><?= formatRupiah($donasi['nominal']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center">Belum ada data donasi</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Pengeluaran Terbaru</h5>
                            <a href="pengeluaran.php" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php if (count($pengeluaranTerbaru) > 0): ?>
                                    <?php foreach ($pengeluaranTerbaru as $pengeluaran): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars(substr($pengeluaran['keterangan'], 0, 40)) ?><?= strlen($pengeluaran['keterangan']) > 40 ? '...' : '' ?></div>
                                                <small class="text-muted">
                                                    <?= date('d M Y', strtotime($pengeluaran['tanggal'])) ?>
                                                </small>
                                            </div>
                                            <span class="fw-bold"><?= formatRupiah($pengeluaran['jumlah']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item text-center">Belum ada data pengeluaran</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Donasi Chart
            const donasiCtx = document.getElementById('donasiChart').getContext('2d');
            const donasiData = {
                labels: [
                    <?php 
                    // Dapatkan 6 bulan terakhir jika data kurang
                    $months = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $month = date('Y-m', strtotime("-$i months"));
                        $months[] = $month;
                    }
                    
                    // Buat array asosiatif untuk data donasi
                    $donasiMap = [];
                    foreach ($donasiPerBulan as $item) {
                        $donasiMap[$item['bulan']] = $item['total_donasi'];
                    }
                    
                    // Output label bulan
                    $labels = [];
                    foreach ($months as $month) {
                        $labels[] = '"' . formatBulan($month) . '"';
                    }
                    echo implode(',', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'Total Donasi (Rp)',
                    data: [
                        <?php
                        // Output data donasi
                        $values = [];
                        foreach ($months as $month) {
                            $values[] = isset($donasiMap[$month]) ? $donasiMap[$month] : 0;
                        }
                        echo implode(',', $values);
                        ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            };
            
            const donasiChart = new Chart(donasiCtx, {
                type: 'bar',
                data: donasiData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                            }
                        }
                    }
                }
            });
            
            // Pengeluaran Chart
            const pengeluaranCtx = document.getElementById('pengeluaranChart').getContext('2d');
            const pengeluaranData = {
                labels: [
                    <?php 
                    // Output label bulan yang sama
                    echo implode(',', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'Total Pengeluaran (Rp)',
                    data: [
                        <?php
                        // Buat array asosiatif untuk data pengeluaran
                        $pengeluaranMap = [];
                        foreach ($pengeluaranPerBulan as $item) {
                            $pengeluaranMap[$item['bulan']] = $item['total_pengeluaran'];
                        }
                        
                        // Output data pengeluaran
                        $values = [];
                        foreach ($months as $month) {
                            $values[] = isset($pengeluaranMap[$month]) ? $pengeluaranMap[$month] : 0;
                        }
                        echo implode(',', $values);
                        ?>
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            };
            
            const pengeluaranChart = new Chart(pengeluaranCtx, {
                type: 'bar',
                data: pengeluaranData,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>