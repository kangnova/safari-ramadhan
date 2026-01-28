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
    <title>Dashboard Keuangan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
            height: 100%;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .card-body {
            position: relative;
            z-index: 1;
            padding: 1.5rem;
        }

        .stat-card .icon-bg {
            position: absolute;
            right: -10px;
            bottom: -10px;
            font-size: 5rem;
            opacity: 0.15;
            z-index: 0;
            color: white;
        }

        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); color: white; }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); color: white; }
        .bg-gradient-danger { background: linear-gradient(45deg, #e74a3b, #be2617); color: white; }
        .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); color: white; }

        .chart-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            height: 100%;
        }

        .recent-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .table-recent tr td {
            vertical-align: middle;
            padding: 0.75rem 1rem;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            border-radius: 50rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h3 class="mb-0 fw-bold text-primary">Dashboard Keuangan</h3>
                <p class="text-muted mb-0 mt-1">Ringkasan donasi dan pengeluaran operasional.</p>
            </div>
            <div>
                <a href="laporan.php" class="btn btn-outline-primary me-2"><i class="fas fa-file-alt me-2"></i>Laporan Lengkap</a>
                <button onclick="window.print()" class="btn btn-secondary"><i class="fas fa-print"></i></button>
            </div>
        </div>
            
        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <!-- Total Donasi -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-primary">
                    <div class="card-body">
                        <i class="fas fa-hand-holding-heart icon-bg"></i>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Donasi Masuk</h6>
                        <div class="h3 mb-0 fw-bold"><?= formatRupiah($donasiStats['total_nominal'] ?? 0) ?></div>
                        <div class="small text-white-50 mt-2">
                            <i class="fas fa-user me-1"></i> <?= $donasiStats['total_donasi'] ?? 0 ?> Donatur Terdaftar
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donasi Sukses -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-success">
                    <div class="card-body">
                        <i class="fas fa-check-circle icon-bg"></i>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Donasi Terverifikasi</h6>
                        <div class="h3 mb-0 fw-bold"><?= formatRupiah($donasiStats['total_success'] ?? 0) ?></div>
                        <div class="small text-white-50 mt-2">
                            <i class="fas fa-check me-1"></i> <?= $donasiStats['count_success'] ?? 0 ?> Transaksi Sukses
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Pengeluaran -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-danger">
                    <div class="card-body">
                        <i class="fas fa-shopping-bag icon-bg"></i>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Total Pengeluaran</h6>
                        <div class="h3 mb-0 fw-bold"><?= formatRupiah($pengeluaranStats['total_jumlah'] ?? 0) ?></div>
                        <div class="small text-white-50 mt-2">
                            <i class="fas fa-receipt me-1"></i> <?= $pengeluaranStats['total_pengeluaran'] ?? 0 ?> Transaksi Keluar
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saldo -->
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card bg-gradient-info">
                    <div class="card-body">
                        <i class="fas fa-wallet icon-bg"></i>
                        <h6 class="text-white-50 text-uppercase fw-bold mb-2">Saldo Bersih</h6>
                        <div class="h3 mb-0 fw-bold"><?= formatRupiah($saldo) ?></div>
                        <div class="small text-white-50 mt-2">
                            <i class="fas fa-info-circle me-1"></i> Real-time Balance
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card chart-card">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i>Tren Donasi Sukses (6 Bulan)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="donasiChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card chart-card">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 fw-bold text-danger"><i class="fas fa-chart-bar me-2"></i>Tren Pengeluaran (6 Bulan)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="pengeluaranChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity Row -->
        <div class="row g-4">
            <!-- Recent Donations -->
            <div class="col-lg-6">
                <div class="card recent-card h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">Donasi Terbaru</h6>
                        <a href="managementdonasi.php" class="btn btn-sm btn-primary rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-recent mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Donatur</th>
                                        <th>Tanggal</th>
                                        <th class="text-end pe-4">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($donasiTerbaru) > 0): ?>
                                        <?php foreach ($donasiTerbaru as $donasi): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark">
                                                        <?= $donasi['is_anonim'] ? '<span class="fst-italic text-muted">Hamba Allah</span>' : htmlspecialchars($donasi['nama_donatur']) ?>
                                                    </div>
                                                    <span class="badge status-badge <?= $donasi['status'] === 'success' ? 'bg-success' : ($donasi['status'] === 'pending' ? 'bg-warning' : 'bg-danger') ?>">
                                                        <?= ucfirst($donasi['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= date('d M Y', strtotime($donasi['created_at'])) ?><br>
                                                    <?= date('H:i', strtotime($donasi['created_at'])) ?>
                                                </td>
                                                <td class="text-end pe-4 fw-bold text-success">
                                                    + <?= formatRupiah($donasi['nominal']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">Belum ada donasi masuk.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="col-lg-6">
                <div class="card recent-card h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-danger">Pengeluaran Terbaru</h6>
                        <a href="pengeluaran.php" class="btn btn-sm btn-danger rounded-pill px-3">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-recent mb-0 align-middle">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Keterangan</th>
                                        <th>Tanggal</th>
                                        <th class="text-end pe-4">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($pengeluaranTerbaru) > 0): ?>
                                        <?php foreach ($pengeluaranTerbaru as $pengeluaran): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-medium text-dark text-truncate" style="max-width: 200px;">
                                                        <?= htmlspecialchars($pengeluaran['keterangan']) ?>
                                                    </div>
                                                </td>
                                                <td class="small text-muted">
                                                    <?= date('d M Y', strtotime($pengeluaran['tanggal'])) ?>
                                                </td>
                                                <td class="text-end pe-4 fw-bold text-danger">
                                                    - <?= formatRupiah($pengeluaran['jumlah']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">Belum ada pengeluaran.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Defaults
            Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
            Chart.defaults.color = '#858796';

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
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 3,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            };
            
            const donasiChart = new Chart(donasiCtx, {
                type: 'line',
                data: donasiData,
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            },
                            ticks: {
                                padding: 10,
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
                    backgroundColor: 'rgba(231, 74, 59, 1)',
                    hoverBackgroundColor: 'rgba(190, 38, 23, 1)',
                    borderColor: 'rgba(231, 74, 59, 1)',
                    borderWidth: 1,
                    barPercentage: 0.5
                }]
            };
            
            const pengeluaranChart = new Chart(pengeluaranCtx, {
                type: 'bar',
                data: pengeluaranData,
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: "rgb(234, 236, 244)",
                                zeroLineColor: "rgb(234, 236, 244)",
                                drawBorder: false,
                                borderDash: [2],
                                zeroLineBorderDash: [2]
                            },
                            ticks: {
                                padding: 10,
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