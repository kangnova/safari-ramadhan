<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Ambil filter periode
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default ke awal bulan ini
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Default ke akhir bulan ini

// --- DATA DONASI ---
// Ambil data donasi berdasarkan periode
$donasiQuery = "SELECT * FROM donasi 
                WHERE created_at BETWEEN ? AND ? 
                ORDER BY created_at ASC";
$donasiStmt = $conn->prepare($donasiQuery);
$donasiStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$donasiData = $donasiStmt->fetchAll(PDO::FETCH_ASSOC);

// Ringkasan donasi berdasarkan status
$donasiSummaryQuery = "SELECT 
                        status,
                        COUNT(*) as jumlah_donasi,
                        SUM(nominal) as total_nominal 
                      FROM donasi 
                      WHERE created_at BETWEEN ? AND ? 
                      GROUP BY status";
$donasiSummaryStmt = $conn->prepare($donasiSummaryQuery);
$donasiSummaryStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
$donasiSummary = $donasiSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

// Olah data ringkasan donasi menjadi array asosiatif berdasarkan status
$donasiByStatus = [];
$totalDonasiSuccess = 0;
foreach ($donasiSummary as $summary) {
    $donasiByStatus[$summary['status']] = [
        'jumlah' => $summary['jumlah_donasi'],
        'nominal' => $summary['total_nominal']
    ];
    
    if ($summary['status'] === 'success') {
        $totalDonasiSuccess = $summary['total_nominal'];
    }
}

// --- DATA PENGELUARAN ---
// Cek jika tabel pengeluaran ada
$checkTableQuery = "SHOW TABLES LIKE 'pengeluaran'";
$tableExists = $conn->query($checkTableQuery)->rowCount() > 0;

$pengeluaranData = [];
$totalPengeluaran = 0;

if ($tableExists) {
    // Ambil data pengeluaran berdasarkan periode
    $pengeluaranQuery = "SELECT * FROM pengeluaran 
                        WHERE tanggal BETWEEN ? AND ? 
                        ORDER BY tanggal ASC";
    $pengeluaranStmt = $conn->prepare($pengeluaranQuery);
    $pengeluaranStmt->execute([$startDate, $endDate]);
    $pengeluaranData = $pengeluaranStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung total pengeluaran
    $totalPengeluaranQuery = "SELECT SUM(jumlah) as total FROM pengeluaran WHERE tanggal BETWEEN ? AND ?";
    $totalPengeluaranStmt = $conn->prepare($totalPengeluaranQuery);
    $totalPengeluaranStmt->execute([$startDate, $endDate]);
    $totalPengeluaran = $totalPengeluaranStmt->fetchColumn() ?: 0;
}

// Hitung saldo = total donasi sukses - total pengeluaran
$saldo = $totalDonasiSuccess - $totalPengeluaran;

// Format angka dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Status badge color
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'success':
            return 'bg-success';
        case 'failed':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - Admin</title>
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

        .summary-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            overflow: hidden;
        }
        
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
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
        }

        .bg-income { background: linear-gradient(45deg, #198754, #20c997); color: white; }
        .bg-expense { background: linear-gradient(45deg, #dc3545, #f06548); color: white; }
        .bg-balance { background: linear-gradient(45deg, #0d6efd, #0dcaf0); color: white; }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 20px;
            transition: all 0.2s;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 3px solid #0d6efd;
            background: none;
        }
        
        .nav-tabs .nav-link:hover {
            color: #0d6efd;
        }

        @media print {
            .page-header, .btn, .no-print, .nav-tabs {
                display: none !important;
            }
            .tab-content > .tab-pane {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
                margin-bottom: 30px;
            }
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
            .summary-card {
                color: black !important;
                background: white !important;
                border: 1px solid #000 !important;
            }
            .icon-bg { display: none; }
        }
    </style>
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
        
        <!-- Page Header -->
        <div class="page-header no-print">
            <div>
                <h3 class="mb-0 fw-bold text-primary">Laporan Keuangan</h3>
                <p class="text-muted mb-0 mt-1">
                    Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel">
                    <i class="fas fa-filter me-2"></i>Filter Tanggal
                </button>
                <button onclick="window.print();" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>Cetak Laporan
                </button>
            </div>
        </div>

        <!-- Filter Panel (Collapsible) -->
        <div class="collapse mb-4 no-print" id="filterPanel">
            <div class="card card-body shadow-sm border-0">
                <h6 class="mb-3">Filter Periode Laporan</h6>
                <form method="GET" action="laporan.php" class="row g-3">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label text-muted small">Tanggal Mulai</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar"></i></span>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $startDate ?>">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label text-muted small">Tanggal Akhir</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-calendar"></i></span>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $endDate ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Print Header (Only visible when printing) -->
        <div class="d-none d-print-block text-center mb-4">
            <h2>Laporan Keuangan Safari Ramadhan</h2>
            <p>Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></p>
            <hr>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <!-- Pemasukan -->
            <div class="col-md-4">
                <div class="card summary-card bg-income h-100">
                    <div class="card-body">
                        <i class="fas fa-arrow-down icon-bg"></i>
                        <h6 class="card-title text-white-50">Total Pemasukan (Donasi Sukses)</h6>
                        <h3 class="fw-bold mb-0"><?= formatRupiah($totalDonasiSuccess) ?></h3>
                        <div class="mt-3 small text-white-50">
                            <i class="fas fa-check-circle me-1"></i> <?= $donasiByStatus['success']['jumlah'] ?? 0 ?> Transaksi Berhasil
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pengeluaran -->
            <div class="col-md-4">
                <div class="card summary-card bg-expense h-100">
                    <div class="card-body">
                        <i class="fas fa-arrow-up icon-bg"></i>
                        <h6 class="card-title text-white-50">Total Pengeluaran</h6>
                        <h3 class="fw-bold mb-0"><?= formatRupiah($totalPengeluaran) ?></h3>
                        <div class="mt-3 small text-white-50">
                            <i class="fas fa-receipt me-1"></i> <?= count($pengeluaranData) ?> Catatan Pengeluaran
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Saldo -->
            <div class="col-md-4">
                <div class="card summary-card bg-balance h-100">
                    <div class="card-body">
                        <i class="fas fa-wallet icon-bg"></i>
                        <h6 class="card-title text-white-50">Saldo Periode Ini</h6>
                        <h3 class="fw-bold mb-0"><?= formatRupiah($saldo) ?></h3>
                        <div class="mt-3 small text-white-50">
                            <?= $saldo < 0 ? '<i class="fas fa-exclamation-triangle me-1"></i> Defisit Anggaran' : '<i class="fas fa-coins me-1"></i> Surplus Anggaran' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-5 page-break-avoid">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Status Transaksi Donasi</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="donasiChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-balance-scale me-2 text-primary"></i>Pemasukan vs Pengeluaran</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="perbandinganChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Tables with Tabs -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 px-3">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-arrow-down text-success me-2"></i>Rincian Pemasukan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="expense-tab" data-bs-toggle="tab" data-bs-target="#expense" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-arrow-up text-danger me-2"></i>Rincian Pengeluaran
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="reportTabsContent">
                    
                    <!-- INCOME TAB -->
                    <div class="tab-pane fade show active" id="income" role="tabpanel">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center no-print">
                            <span class="text-muted small">Menampilkan semua donasi dalam periode terpilih.</span>
                            <a href="export_donasi.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Tanggal</th>
                                        <th>Donatur</th>
                                        <th>Nominal</th>
                                        <th>Metode</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($donasiData) > 0): ?>
                                        <?php foreach ($donasiData as $donasi): ?>
                                            <tr>
                                                <td class="ps-4"><?= date('d/m/Y H:i', strtotime($donasi['created_at'])) ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= $donasi['is_anonim'] ? '<em>Hamba Allah</em>' : htmlspecialchars($donasi['nama_donatur']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($donasi['email'] ?? '-') ?></div>
                                                </td>
                                                <td class="fw-bold text-success"><?= formatRupiah($donasi['nominal']) ?></td>
                                                <td><span class="badge bg-light text-dark border"><?= $donasi['metode_pembayaran'] ?? 'Manual' ?></span></td>
                                                <td class="text-center">
                                                    <span class="badge rounded-pill <?= getStatusBadgeClass($donasi['status']) ?>">
                                                        <?= ucfirst($donasi['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">Belum ada data donasi pada periode ini</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- EXPENSE TAB -->
                    <div class="tab-pane fade" id="expense" role="tabpanel">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center no-print">
                            <span class="text-muted small">Menampilkan semua pengeluaran dalam periode terpilih.</span>
                            <a href="export_pengeluaran.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-file-excel me-2"></i>Export Excel
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Tanggal</th>
                                        <th>Keterangan Pengeluaran</th>
                                        <th>Jumlah Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($pengeluaranData) > 0): ?>
                                        <?php foreach ($pengeluaranData as $pengeluaran): ?>
                                            <tr>
                                                <td class="ps-4"><?= date('d/m/Y', strtotime($pengeluaran['tanggal'])) ?></td>
                                                <td><?= htmlspecialchars($pengeluaran['keterangan']) ?></td>
                                                <td class="fw-bold text-danger"><?= formatRupiah($pengeluaran['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-5 text-muted">Belum ada data pengeluaran pada periode ini</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Signature for Print -->
        <div class="row d-none d-print-block mt-5 pt-5">
            <div class="col-6 text-center offset-6">
                <p>............., <?= date('d M Y') ?></p>
                <p class="mb-5">Mengetahui,</p>
                <br>
                <p class="fw-bold text-decoration-underline">Administrator</p>
            </div>
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Chart Configuration
            const chartConfig = {
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom' }
                }
            };

            // Donasi Chart
            const donasiCtx = document.getElementById('donasiChart');
            if(donasiCtx) {
                new Chart(donasiCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Sukses', 'Pending', 'Gagal'],
                        datasets: [{
                            data: [
                                <?= $donasiByStatus['success']['jumlah'] ?? 0 ?>,
                                <?= $donasiByStatus['pending']['jumlah'] ?? 0 ?>,
                                <?= $donasiByStatus['failed']['jumlah'] ?? 0 ?>
                            ],
                            backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                            borderWidth: 0
                        }]
                    },
                    options: chartConfig
                });
            }
            
            // Perbandingan Chart
            const bandinganCtx = document.getElementById('perbandinganChart');
            if(bandinganCtx) {
                new Chart(bandinganCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Pemasukan', 'Pengeluaran'],
                        datasets: [{
                            data: [<?= $totalDonasiSuccess ?>, <?= $totalPengeluaran ?>],
                            backgroundColor: ['#0d6efd', '#dc3545'],
                            borderWidth: 0
                        }]
                    },
                    options: chartConfig
                });
            }
        });
    </script>
</body>
</html>