<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

// Determine Archive Type: 'ifthar', 'safari', 'duta', 'jadwal'
$type = isset($_GET['type']) && in_array($_GET['type'], ['safari', 'duta', 'jadwal']) ? $_GET['type'] : 'ifthar';

// Table for fetching years depends on type
$yearTable = 'ifthar';
if ($type === 'safari' || $type === 'duta') {
    $yearTable = 'lembaga';
} elseif ($type === 'jadwal') {
    $yearTable = 'jadwal_safari';
    $yearCol = 'tanggal'; // For jadwal, date column is 'tanggal'
}

$typeName = 'Ifthar 1000 Santri';
if ($type === 'safari') $typeName = 'Safari Ramadhan';
if ($type === 'duta') $typeName = 'Duta GNB';
if ($type === 'jadwal') $typeName = 'Jadwal Safari';

// Get available years for sidebar based on type
try {
    $col = isset($yearCol) ? $yearCol : 'created_at';
    $stmtYears = $conn->query("SELECT DISTINCT YEAR($col) as tahun FROM $yearTable ORDER BY tahun DESC");
    $available_years = $stmtYears->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $available_years = [];
    error_log("Error fetching years: " . $e->getMessage());
}

// Initialize variables
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : (count($available_years) > 0 ? $available_years[0] : date('Y'));

$stats = [];
$dataArsip = [];

try {
    if ($type === 'safari') {
        // --- LOGIC FOR SAFARI ---
        
        $queryStatistik = "
            SELECT 
                COUNT(DISTINCT l.id) as total_lembaga,
                SUM(l.jumlah_santri) as total_santri,
                COUNT(DISTINCT l.kecamatan) as total_kecamatan,
                SUM(CASE WHEN pl.duta_gnb = 1 THEN 1 ELSE 0 END) as total_duta
            FROM lembaga l
            LEFT JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
            WHERE YEAR(l.created_at) = :tahun";
        
        $stmtStats = $conn->prepare($queryStatistik);
        $stmtStats->execute(['tahun' => $tahun]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        $query = "
            SELECT 
                l.*,
                GROUP_CONCAT(DISTINCT ha.hari) as hari_aktif,
                GROUP_CONCAT(DISTINCT md.materi) as materi_dipilih,
                pl.frekuensi_kunjungan,
                pl.duta_gnb,
                pl.manfaat
            FROM lembaga l
            LEFT JOIN hari_aktif ha ON l.id = ha.lembaga_id
            LEFT JOIN materi_dipilih md ON l.id = md.lembaga_id
            LEFT JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
            WHERE YEAR(l.created_at) = :tahun
            GROUP BY l.id
            ORDER BY l.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute(['tahun' => $tahun]);
        $dataArsip = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'duta') {
        // --- LOGIC FOR DUTA GNB ---
        
        $queryStatistik = "
            SELECT 
                COUNT(DISTINCT l.id) as total_duta,
                SUM(l.jumlah_santri) as total_santri
            FROM lembaga l
            JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
            WHERE pl.duta_gnb = 1 AND YEAR(l.created_at) = :tahun";
        
        $stmtStats = $conn->prepare($queryStatistik);
        $stmtStats->execute(['tahun' => $tahun]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        $query = "
            SELECT 
                l.*, 
                pl.frekuensi_kunjungan
            FROM lembaga l 
            INNER JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id 
            WHERE pl.duta_gnb = 1 AND YEAR(l.created_at) = :tahun
            ORDER BY l.created_at DESC";
            
        $stmt = $conn->prepare($query);
        $stmt->execute(['tahun' => $tahun]);
        $dataArsip = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($type === 'jadwal') {
        // --- LOGIC FOR JADWAL ---
        
        $queryStatistik = "
            SELECT 
                COUNT(*) as total_jadwal,
                COUNT(CASE WHEN status = 'terlaksana' THEN 1 END) as total_terlaksana,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as total_pending
            FROM jadwal_safari
            WHERE YEAR(tanggal) = :tahun";
            
        $stmtStats = $conn->prepare($queryStatistik);
        $stmtStats->execute(['tahun' => $tahun]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
        
        $query = "
            SELECT js.*, l.nama_lembaga, l.alamat, l.kecamatan, l.penanggung_jawab, l.no_wa, l.jumlah_santri 
            FROM jadwal_safari js
            JOIN lembaga l ON js.lembaga_id = l.id
            WHERE YEAR(js.tanggal) = :tahun
            ORDER BY js.tanggal ASC, js.jam ASC";
            
        $stmt = $conn->prepare($query);
        $stmt->execute(['tahun' => $tahun]);
        $dataArsip = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // --- LOGIC FOR IFTHAR ---

        $queryStatistik = "
            SELECT 
                COUNT(DISTINCT id) as total_lembaga,
                SUM(jumlah_santri) as total_santri,
                COUNT(CASE WHEN santri_yatim IS NOT NULL THEN 1 END) as total_pengajuan_yatim,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as total_approved
            FROM ifthar
            WHERE YEAR(created_at) = :tahun";

        $stmtStats = $conn->prepare($queryStatistik);
        $stmtStats->execute(['tahun' => $tahun]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        $query = "SELECT * FROM ifthar WHERE YEAR(created_at) = :tahun ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute(['tahun' => $tahun]);
        $dataArsip = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Database Error in arsip.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Data <?= $typeName ?> - Tahun <?= htmlspecialchars($tahun) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
        }
        .stats-card:hover { transform: translateY(-5px); }
        .stats-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
    </style>
</head>
<body class="bg-light">
    
    <?php require_once 'includes/header.php'; ?>

    <div class="container-fluid mt-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-archive-fill me-2"></i>Arsip Pendaftar</h2>
        </div>

        <!-- Type Selection Tabs -->
        <ul class="nav nav-pills mb-4 gap-2">
            <li class="nav-item">
                <a class="nav-link <?= $type === 'ifthar' ? 'active' : 'bg-white text-dark' ?>" 
                   href="?type=ifthar&tahun=<?= $tahun ?>">
                   <i class="bi bi-person-plus-fill me-1"></i> Ifthar 1000 Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $type === 'safari' ? 'active' : 'bg-white text-dark' ?>" 
                   href="?type=safari&tahun=<?= $tahun ?>">
                   <i class="bi bi-person-lines-fill me-1"></i> Safari Ramadhan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $type === 'duta' ? 'active' : 'bg-white text-dark' ?>" 
                   href="?type=duta&tahun=<?= $tahun ?>">
                   <i class="bi bi-star-fill me-1"></i> Duta GNB
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $type === 'jadwal' ? 'active' : 'bg-white text-dark' ?>" 
                   href="?type=jadwal&tahun=<?= $tahun ?>">
                   <i class="bi bi-calendar-check-fill me-1"></i> Jadwal Safari
                </a>
            </li>
        </ul>

        <div class="row">
            <!-- Sidebar / Year Category -->
            <div class="col-md-2 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-range me-2"></i>Tahun</h5>
                    </div>
                    <div class="card-body p-2">
                        <div class="list-group list-group-flush">
                            <?php if (empty($available_years)): ?>
                                <div class="text-center py-3 text-muted">Belum ada data</div>
                            <?php else: ?>
                                <?php foreach ($available_years as $year): ?>
                                    <a href="?type=<?= $type ?>&tahun=<?= $year ?>" 
                                       class="list-group-item list-group-item-action <?= $year == $tahun ? 'active' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span><?= $year ?></span>
                                            <i class="bi bi-chevron-right small"></i>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                
                <div class="alert alert-secondary border-0 d-flex align-items-center mb-4">
                    <i class="bi bi-folder2-open fs-4 me-3"></i>
                    <div>
                        <div class="text-uppercase small fw-bold text-muted">Arsip</div>
                        <h4 class="mb-0"><?= $typeName ?> - <?= htmlspecialchars($tahun) ?></h4>
                    </div>
                </div>

                <!-- Statistik -->
                <div class="row mb-4">
                    <?php if ($type === 'safari'): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-buildings stats-icon"></i>
                                    <h5 class="card-title">Total Lembaga</h5>
                                    <h2 class="mb-0"><?= $stats['total_lembaga'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-people stats-icon"></i>
                                    <h5 class="card-title">Total Santri</h5>
                                    <h2 class="mb-0"><?= number_format($stats['total_santri'] ?? 0) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-geo-alt stats-icon"></i>
                                    <h5 class="card-title">Kecamatan</h5>
                                    <h2 class="mb-0"><?= $stats['total_kecamatan'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-star stats-icon"></i>
                                    <h5 class="card-title">Duta GNB</h5>
                                    <h2 class="mb-0"><?= $stats['total_duta'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($type === 'duta'): ?>
                         <div class="col-md-6 mb-3">
                            <div class="card stats-card h-100 bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-star-fill stats-icon"></i>
                                    <h5 class="card-title">Total Duta GNB</h5>
                                    <h2 class="mb-0"><?= $stats['total_duta'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card stats-card h-100 bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-people stats-icon"></i>
                                    <h5 class="card-title">Total Santri</h5>
                                    <h2 class="mb-0"><?= number_format($stats['total_santri'] ?? 0) ?></h2>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($type === 'jadwal'): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card h-100 bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-calendar-event stats-icon"></i>
                                    <h5 class="card-title">Total Jadwal</h5>
                                    <h2 class="mb-0"><?= $stats['total_jadwal'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card h-100 bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-check-all stats-icon"></i>
                                    <h5 class="card-title">Terlaksana</h5>
                                    <h2 class="mb-0"><?= $stats['total_terlaksana'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card stats-card h-100 bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-clock-history stats-icon"></i>
                                    <h5 class="card-title">Pending</h5>
                                    <h2 class="mb-0"><?= $stats['total_pending'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                    <?php else: // IFTHAR ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-buildings stats-icon"></i>
                                    <h5 class="card-title">Total Lembaga</h5>
                                    <h2 class="mb-0"><?= $stats['total_lembaga'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-people stats-icon"></i>
                                    <h5 class="card-title">Total Santri</h5>
                                    <h2 class="mb-0"><?= number_format($stats['total_santri'] ?? 0) ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-heart stats-icon"></i>
                                    <h5 class="card-title">Pengajuan Yatim</h5>
                                    <h2 class="mb-0"><?= $stats['total_pengajuan_yatim'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card h-100 bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="bi bi-check-circle stats-icon"></i>
                                    <h5 class="card-title">Approved</h5>
                                    <h2 class="mb-0"><?= $stats['total_approved'] ?? 0 ?></h2>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tabel Data -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Data Arsip (<?= count($dataArsip) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="arsipTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        
                                        <?php if ($type === 'jadwal'): ?>
                                            <th>Hari/Tgl</th>
                                            <th>Jam</th>
                                            <th>Lembaga</th>
                                            <th>Kecamatan</th>
                                            <th>Pengisi</th>
                                            <th>Status</th>
                                        <?php else: ?>
                                            <th>Lembaga</th>
                                            
                                            <?php if ($type === 'safari' || $type === 'duta'): ?>
                                                <th>Alamat</th>
                                                <th>Kecamatan</th>
                                            <?php else: ?>
                                                <th>Penanggung Jawab</th>
                                            <?php endif; ?>
                                            
                                            <?php if ($type === 'ifthar'): ?>
                                                <th>No. WA</th>
                                            <?php endif; ?>
                                            
                                            <th>Santri</th>
                                            
                                            <?php if ($type === 'safari' || $type === 'duta'): ?>
                                                <th>PJ</th>
                                            <?php else: ?>
                                                <th>Status</th>
                                            <?php endif; ?>
                                            
                                            <?php if ($type === 'duta'): ?>
                                                <th>Frekuensi</th>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <th width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dataArsip as $index => $data): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        
                                        <?php if ($type === 'jadwal'): ?>
                                            <!-- JADWAL ROWS -->
                                            <?php 
                                            $days = ['Sunday' => 'Minggu','Monday' => 'Senin','Tuesday' => 'Selasa',
                                                     'Wednesday' => 'Rabu','Thursday' => 'Kamis','Friday' => 'Jumat','Saturday' => 'Sabtu'];
                                            $dayName = $days[date('l', strtotime($data['tanggal']))];
                                            ?>
                                            <td>
                                                <?= $dayName ?>,<br>
                                                <?= date('d/m/Y', strtotime($data['tanggal'])) ?>
                                            </td>
                                            <td><?= $data['jam'] ?></td>
                                            <td><?= htmlspecialchars($data['nama_lembaga']) ?></td>
                                            <td><?= ucfirst($data['kecamatan']) ?></td>
                                            <td><?= htmlspecialchars($data['pengisi']) ?></td>
                                            <td>
                                                <?php 
                                                $badge = $data['status'] == 'terlaksana' ? 'success' : ($data['status'] == 'pending' ? 'warning' : 'danger');
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($data['status']) ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#detailModalJadwal<?= $data['id'] ?>">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                <button class="btn btn-sm btn-danger text-white" onclick="deleteArsip('jadwal', <?= $data['id'] ?>)">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>

                                        <?php elseif ($type === 'safari' || $type === 'duta'): ?>
                                            <!-- Safari/Duta Rows -->
                                            <td><?= htmlspecialchars($data['nama_lembaga']) ?></td>
                                            <td><?= htmlspecialchars($data['alamat']) ?></td>
                                            <td><?= ucfirst($data['kecamatan']) ?></td>
                                            <td><?= $data['jumlah_santri'] ?></td>
                                            <td><?= htmlspecialchars($data['penanggung_jawab']) ?></td>
                                            <?php if ($type === 'duta'): ?>
                                                <td><?= $data['frekuensi_kunjungan'] ?>x</td>
                                            <?php endif; ?>
                                            <td>
                                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#detailModalSafari<?= $data['id'] ?>">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                <button class="btn btn-sm btn-danger text-white" onclick="deleteArsip('<?= $type ?>', <?= $data['id'] ?>)">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>

                                        <?php else: ?>
                                            <!-- Ifthar Rows -->
                                            <td><?= htmlspecialchars($data['asal_lembaga']) ?></td>
                                            <td><?= htmlspecialchars($data['nama_lengkap']) ?></td>
                                            <td>
                                                <a href="https://wa.me/<?= $data['no_hp'] ?>" target="_blank" class="text-decoration-none">
                                                    <?= $data['no_hp'] ?>
                                                </a>
                                            </td>
                                            <td><?= $data['jumlah_santri'] ?></td>
                                            <td>
                                                <?php
                                                $statusClass = $data['status'] === 'approved' ? 'success' : ($data['status'] === 'rejected' ? 'danger' : 'warning');
                                                $statusText = $data['status'] === 'approved' ? 'Disetujui' : ($data['status'] === 'rejected' ? 'Ditolak' : 'Pending');
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#detailModalIfthar<?= $data['id'] ?>">
                                                    <i class="bi bi-eye"></i> Detail
                                                </button>
                                                <button class="btn btn-sm btn-danger text-white" onclick="deleteArsip('ifthar', <?= $data['id'] ?>)">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </td>
                                        <?php endif; ?>

                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach ($dataArsip as $data): ?>
        <?php if ($type === 'jadwal'): ?>
            <!-- MODAL JADWAL -->
            <div class="modal fade" id="detailModalJadwal<?= $data['id'] ?>" tabindex="-1">
                 <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Detail Jadwal</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Lembaga:</strong> <?= $data['nama_lembaga'] ?></p>
                            <p><strong>Kecamatan:</strong> <?= ucwords(str_replace('_', ' ', $data['kecamatan'])) ?></p>
                            <p><strong>Alamat:</strong> <?= $data['alamat'] ?></p>
                            <p><strong>Jumlah Santri:</strong> <?= $data['jumlah_santri'] ?></p>
                            <p><strong>Penanggung Jawab:</strong> <?= $data['penanggung_jawab'] ?></p>
                            <p><strong>No. WhatsApp:</strong> <?= $data['no_wa'] ?></p>
                            <p><strong>Jadwal:</strong> <?= date('d/m/Y', strtotime($data['tanggal'])) ?> Pukul <?= $data['jam'] ?></p>
                            <p><strong>Pengisi:</strong> <?= $data['pengisi'] ?></p>
                            <p><strong>Status:</strong> <?= ucfirst($data['status']) ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($type === 'safari' || $type === 'duta'): ?>
            <!-- MODAL SAFARI/DUTA -->
            <div class="modal fade" id="detailModalSafari<?= $data['id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Detail <?= htmlspecialchars($data['nama_lembaga']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-primary">Informasi Lembaga</h6>
                                    <table class="table table-sm">
                                        <tr><td width="40%">Email</td><td><?= htmlspecialchars($data['email']) ?></td></tr>
                                        <tr><td>Alamat</td><td><?= htmlspecialchars($data['alamat']) ?></td></tr>
                                        <tr><td>Kecamatan</td><td><?= ucfirst($data['kecamatan']) ?></td></tr>
                                        <tr><td>Hari Aktif</td><td><?= str_replace(',', ', ', $data['hari_aktif'] ?? '-') ?></td></tr>
                                        <tr><td>Jam Aktif</td><td><?= $data['jam_aktif'] ?></td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-primary">Informasi Program</h6>
                                    <table class="table table-sm">
                                        <tr><td width="40%">Materi</td><td><?= str_replace(',', ', ', $data['materi_dipilih'] ?? '-') ?></td></tr>
                                        <tr><td>Frekuensi</td><td><?= $data['frekuensi_kunjungan'] ?? '-' ?> kali</td></tr>
                                        <tr><td>Duta GNB</td><td><?= ($data['duta_gnb'] ?? 0) ? 'Bersedia' : 'Belum Bersedia' ?></td></tr>
                                        <tr><td>Manfaat</td><td><?= ucfirst($data['manfaat'] ?? '-') ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- MODAL IFTHAR -->
            <div class="modal fade" id="detailModalIfthar<?= $data['id'] ?>" tabindex="-1">
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
                                        <tr><td width="40%">Email</td><td><?= htmlspecialchars($data['email']) ?></td></tr>
                                        <tr><td>Penanggung Jawab</td><td><?= htmlspecialchars($data['nama_lengkap']) ?></td></tr>
                                        <tr><td>Jumlah Santri</td><td><?= $data['jumlah_santri'] ?></td></tr>
                                        <tr><td>Tanggal Daftar</td><td><?= date('d M Y H:i', strtotime($data['created_at'])) ?></td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary">Pengajuan Santri Yatim</h6>
                                    <p class="bg-light p-2 rounded"><?= $data['santri_yatim'] ? nl2br(htmlspecialchars($data['santri_yatim'])) : 'Tidak ada pengajuan' ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#arsipTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
                },
                pageLength: 25,
                order: [[0, 'asc']]
            });
        });

        function deleteArsip(type, id) {
            if (!confirm('Apakah Anda yakin ingin menghapus data arsip ini?')) {
                return;
            }

            if (type === 'jadwal') {
                // For Jadwal, use direct redirect with return URL
                window.location.href = 'delete_jadwal.php?id=' + id + '&redirect=' + encodeURIComponent(window.location.href);
            } else {
                // For others (Ifthar, Safari, Duta), use AJAX
                let endpoint = '';
                if (type === 'ifthar') {
                    endpoint = 'delete_ifthar.php';
                } else if (type === 'safari' || type === 'duta') {
                    endpoint = 'delete_lembaga.php';
                } else {
                    alert('Tipe arsip tidak dikenal!');
                    return;
                }

                // Create form data
                let formData = new FormData();
                formData.append('id', id);

                fetch(endpoint, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Data berhasil dihapus');
                        location.reload();
                    } else {
                        alert('Gagal menghapus: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menghapus data.');
                });
            }
        }
    </script>
</body>
</html>
