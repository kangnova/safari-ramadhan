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
            return 'bg-warning';
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
    <title>Laporan Keuangan - Donasi</title>
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
        
        .summary-card {
            border-left: 4px solid;
        }
        
        .card-income {
            border-left-color: #28a745;
        }
        
        .card-expense {
            border-left-color: #dc3545;
        }
        
        .card-balance {
            border-left-color: #007bff;
        }
        
        .print-button {
            display: inline-block;
        }
        
        @media print {
            .sidebar, .print-button, .form-filter, .no-print {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
            }
            
            @page {
                size: A4;
                margin: 10mm;
            }
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Laporan Keuangan</h2>
                <div class="print-button">
                    <button onclick="window.print();" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                </div>
            </div>
            
            <!-- Print Header (Only visible when printing) -->
            <div class="print-header d-none d-print-block">
                <h1>Laporan Keuangan Ifthar Ramadhan</h1>
                <p>Periode: <?= date('d M Y', strtotime($startDate)) ?> - <?= date('d M Y', strtotime($endDate)) ?></p>
            </div>
            
            <!-- Filter Period Form -->
            <div class="card mb-4 form-filter">
                <div class="card-body">
                    <form method="GET" action="laporan.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $startDate ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Tanggal Akhir</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $endDate ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card summary-card card-income h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Donasi Masuk</h5>
                            <p class="card-text fs-4 text-success"><?= formatRupiah($totalDonasiSuccess) ?></p>
                            <p class="card-text">
                                <span class="badge bg-success"><?= $donasiByStatus['success']['jumlah'] ?? 0 ?> Donasi</span>
                                <span class="text-muted">(Sukses)</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card card-expense h-100">
                        <div class="card-body">
                            <h5 class="card-title">Total Pengeluaran</h5>
                            <p class="card-text fs-4 text-danger"><?= formatRupiah($totalPengeluaran) ?></p>
                            <p class="card-text">
                                <span class="badge bg-danger"><?= count($pengeluaranData) ?> Transaksi</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card card-balance h-100">
                        <div class="card-body">
                            <h5 class="card-title">Saldo Periode Ini</h5>
                            <p class="card-text fs-4 <?= $saldo >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= formatRupiah(abs($saldo)) ?>
                                <?= $saldo < 0 ? '(Defisit)' : '' ?>
                            </p>
                            <p class="card-text text-muted">
                                Periode: <?= date('d M Y', strtotime($startDate)) ?> s/d <?= date('d M Y', strtotime($endDate)) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pie Charts -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ringkasan Donasi</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="donasiChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Perbandingan Donasi vs. Pengeluaran</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="perbandinganChart" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detail Tables -->
            <div class="row">
                <!-- Donasi Table -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Rincian Donasi</h5>
                            <div class="no-print">
                                <a href="export_donasi.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Export Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Donatur</th>
                                            <th>Nominal</th>
                                            <th>Metode</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($donasiData) > 0): ?>
                                            <?php foreach ($donasiData as $donasi): ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($donasi['created_at'])) ?></td>
                                                    <td><?= $donasi['is_anonim'] ? '<em>Anonim</em>' : htmlspecialchars($donasi['nama_donatur']) ?></td>
                                                    <td><?= formatRupiah($donasi['nominal']) ?></td>
                                                    <td><?= $donasi['metode_pembayaran'] ?? '-' ?></td>
                                                    <td>
                                                        <span class="badge <?= getStatusBadgeClass($donasi['status']) ?>">
                                                            <?= ucfirst($donasi['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">Tidak ada data donasi pada periode ini</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pengeluaran Table -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Rincian Pengeluaran</h5>
                            <div class="no-print">
                                <a href="export_pengeluaran.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Export Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Keterangan</th>
                                            <th>Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($pengeluaranData) > 0): ?>
                                            <?php foreach ($pengeluaranData as $pengeluaran): ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($pengeluaran['tanggal'])) ?></td>
                                                    <td><?= htmlspecialchars($pengeluaran['keterangan']) ?></td>
                                                    <td><?= formatRupiah($pengeluaran['jumlah']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Tidak ada data pengeluaran pada periode ini</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Signatures (Only visible when printing) -->
            <div class="row d-none d-print-block mt-5">
                <div class="col-md-6 offset-md-6 text-center">
                    <p>............., <?= date('d M Y') ?></p>
                    <p>Penanggung Jawab</p>
                    <br><br><br>
                    <p>_________________________</p>
                    <p>Administrator</p>
                </div>
            </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Donasi Chart
            const donasiCtx = document.getElementById('donasiChart').getContext('2d');
            const donasiChart = new Chart(donasiCtx, {
                type: 'pie',
                data: {
                    labels: ['Sukses', 'Pending', 'Gagal'],
                    datasets: [{
                        data: [
                            <?= $donasiByStatus['success']['jumlah'] ?? 0 ?>,
                            <?= $donasiByStatus['pending']['jumlah'] ?? 0 ?>,
                            <?= $donasiByStatus['failed']['jumlah'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgb(40, 167, 69)',
                            'rgb(255, 193, 7)',
                            'rgb(220, 53, 69)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    if (label) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round(value / total * 100);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                    return null;
                                }
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Perbandingan Chart
            const perbandinganCtx = document.getElementById('perbandinganChart').getContext('2d');
            const perbandinganChart = new Chart(perbandinganCtx, {
                type: 'pie',
                data: {
                    labels: ['Total Donasi', 'Total Pengeluaran'],
                    datasets: [{
                        data: [
                            <?= $totalDonasiSuccess ?>,
                            <?= $totalPengeluaran ?>
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgb(40, 167, 69)',
                            'rgb(220, 53, 69)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    if (label) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = Math.round(value / total * 100);
                                        return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                                    }
                                    return null;
                                }
                            }
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>