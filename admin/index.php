<?php
session_start();
require_once '../koneksi.php';

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Initialize variables with default values
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$totalPrograms = 0;
$latestPrograms = [];
$totalLembaga = 0;
$lembagaPerKecamatan = [];
$latestPendaftarSafari = [];
$totalIfthar = 0;
$iftharPerKecamatan = [];
$latestPendaftarIfthar = [];
$totalDutaGNB = 0;
$totalPengisi = 0;
$totalJadwal = 0;
$topPengisi = [];

// Fetch summary data
try {
    // Count total programs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM program WHERE YEAR(tgl_update) = :tahun");
    $stmt->execute(['tahun' => $tahun]);
    $totalPrograms = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT * FROM program WHERE YEAR(tgl_update) = :tahun ORDER BY tgl_update DESC LIMIT 5");
    $stmt->execute(['tahun' => $tahun]);
    $latestPrograms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Quota from Settings
    $stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_quota'");
    $stmtQ->execute();
    $quotaSafari = (int)$stmtQ->fetchColumn();
    if($quotaSafari == 0) $quotaSafari = 170; // Hard fallback

    $stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ifthar_quota'");
    $stmtQ->execute();
    $iftharQuota = (int)$stmtQ->fetchColumn();
    if($iftharQuota == 0) $iftharQuota = 200; // Hard fallback

    $stmt = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE YEAR(created_at) = :tahun");
    $stmt->execute(['tahun' => $tahun]);
    $totalLembaga = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT kecamatan, COUNT(*) as total FROM lembaga WHERE YEAR(created_at) = :tahun GROUP BY kecamatan");
    $stmt->execute(['tahun' => $tahun]);
    $lembagaPerKecamatan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT * FROM lembaga WHERE YEAR(created_at) = :tahun ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['tahun' => $tahun]);
    $latestPendaftarSafari = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM ifthar WHERE YEAR(created_at) = :tahun");
    $stmt->execute(['tahun' => $tahun]);
    $totalIfthar = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT kecamatan, COUNT(*) as total FROM ifthar WHERE YEAR(created_at) = :tahun GROUP BY kecamatan");
    $stmt->execute(['tahun' => $tahun]);
    $iftharPerKecamatan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("SELECT * FROM ifthar WHERE YEAR(created_at) = :tahun ORDER BY created_at DESC LIMIT 5");
    $stmt->execute(['tahun' => $tahun]);
    $latestPendaftarIfthar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Duta GNB join with Lembaga to filter by year
    $stmt = $conn->prepare("SELECT COUNT(*) FROM persetujuan_lembaga pl JOIN lembaga l ON pl.lembaga_id = l.id WHERE pl.duta_gnb = 1 AND YEAR(l.created_at) = :tahun");
    $stmt->execute(['tahun' => $tahun]);
    $totalDutaGNB = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM pengisi");
    $stmt->execute();
    $totalPengisi = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal_safari WHERE YEAR(tanggal) = :tahun");
    $stmt->execute(['tahun' => $tahun]);
    $totalJadwal = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT 
        js.pengisi,
        COUNT(*) as jumlah_jadwal,
        COUNT(CASE WHEN js.status = 'terlaksana' THEN 1 END) as jadwal_terlaksana,
        COUNT(CASE WHEN js.status = 'pending' THEN 1 END) as jadwal_pending
        FROM jadwal_safari js
        WHERE YEAR(js.tanggal) = :tahun
        GROUP BY js.pengisi 
        ORDER BY jumlah_jadwal DESC 
        LIMIT 5");
    $stmt->execute(['tahun' => $tahun]);
    $topPengisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log error significantly to avoid cluttering the UI, or set a flagged error message
    // Note: Variables have defaults, so the page will load even if DB fails.
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Welcome Message -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Selamat Datang di Dashboard Admin <?= htmlspecialchars($tahun) ?></h1>
                <p class="text-muted">Kelola program Safari Ramadhan dengan mudah dan efisien.</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Program Card -->
            <div class="col-md-4">
                <div class="card text-white bg-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Program</h6>
                                <h2 class="my-2"><?= $totalPrograms ?></h2>
                                <p class="card-text mb-0">Program terdaftar</p>
                            </div>
                            <i class="bi bi-calendar-event fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-primary border-light">
                        <a href="program.php" class="text-white text-decoration-none">
                            Lihat detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Safari Ramadhan Card -->
            <div class="col-md-4">
                <div class="card text-white bg-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Pendaftar Safari</h6>
                                <h2 class="my-2"><?= $totalLembaga ?></h2>
                                <p class="card-text mb-0">Sisa kuota: <?= max(0, $quotaSafari - $totalLembaga) ?> dari <?= $quotaSafari ?></p>
                            </div>
                            <i class="bi bi-people fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-success border-light">
                        <a href="pendaftar.php" class="text-white text-decoration-none">
                            Lihat detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ifthar Card -->
            <div class="col-md-4">
                <div class="card text-white bg-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Pendaftar Ifthar</h6>
                                <h2 class="my-2"><?= $totalIfthar ?></h2>
                                <p class="card-text mb-0">Sisa kuota: <?= $iftharQuota - $totalIfthar ?> dari <?= $iftharQuota ?></p>
                            </div>
                            <i class="bi bi-people-fill fs-1"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-info border-light">
                        <a href="pendaftar_ifthar.php" class="text-white text-decoration-none">
                            Lihat detail <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Duta GNB Card -->
    <div class="col-md-4">
        <div class="card text-white bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Duta GNB</h6>
                        <h2 class="my-2"><?= $totalDutaGNB ?></h2>
                        <p class="card-text mb-0">Pendaftar bersedia</p>
                    </div>
                    <i class="bi bi-star fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-warning border-light">
                <a href="duta_gnb.php" class="text-white text-decoration-none">
                    Lihat detail <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Pengisi Card -->
    <div class="col-md-4">
        <div class="card text-white bg-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
    <h6 class="card-title mb-0">Pengisi Safari</h6>
    <h2 class="my-2"><?= $totalPengisi ?></h2>
    <p class="card-text mb-0">Total <?= $totalJadwal ?> Jadwal</p>
</div>
                    <i class="bi bi-person-video3 fs-1"></i>
                </div>
            </div>
            <div class="card-footer bg-danger border-light">
                <a href="pengisi.php" class="text-white text-decoration-none">
                    Lihat detail <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

        <!-- Pendaftar Tables -->
        <div class="row mb-4">
            <!-- Safari Ramadhan Pendaftar -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Pendaftar Terbaru Safari Ramadhan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Lembaga</th>
                                        <th>Kecamatan</th>
                                        <th>Jumlah Santri</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestPendaftarSafari as $pendaftar): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pendaftar['nama_lembaga']) ?></td>
                                        <td><?= ucwords(str_replace('_', ' ', $pendaftar['kecamatan'])) ?></td>
                                        <td><?= $pendaftar['jumlah_santri'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($pendaftar['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ifthar Pendaftar -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Pendaftar Terbaru Ifthar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Lembaga</th>
                                        <th>Kecamatan</th>
                                        <th>Jumlah Santri</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestPendaftarIfthar as $pendaftar): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pendaftar['nama_lembaga']) ?></td>
                                        <td><?= ucwords(str_replace('_', ' ', $pendaftar['kecamatan'])) ?></td>
                                        <td><?= $pendaftar['jumlah_santri'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($pendaftar['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="row mb-4">
            <!-- Safari Statistics -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Statistik per Kecamatan Safari Ramadhan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-hover">
    <thead>
        <tr>
            <th>No</th>
            <th>Kecamatan</th>
            <th class="text-end">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1; // Inisialisasi variabel counter
        foreach ($lembagaPerKecamatan as $lembaga): 
        ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= ucwords(str_replace('_', ' ', $lembaga['kecamatan'])) ?></td>
            <td class="text-end"><?= $lembaga['total'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ifthar Statistics -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Statistik per Kecamatan Ifthar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kecamatan</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($iftharPerKecamatan as $ifthar): ?>
                                    <tr>
                                        <td><?= ucwords(str_replace('_', ' ', $ifthar['kecamatan'])) ?></td>
                                        <td class="text-end"><?= $ifthar['total'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="row">
            <!-- Program Terbaru -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Program Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    <?php foreach ($latestPrograms as $program): ?>
                                    <tr>
                                        <td width="60">
                                            <img src="../img/program/<?= htmlspecialchars($program['gambar']) ?>" 
                                                 alt="<?= htmlspecialchars($program['nama_program']) ?>" 
                                                 class="img-thumbnail" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($program['nama_program']) ?></strong><br>
                                            <small class="text-muted">
                                                Update: <?= date('d/m/Y H:i', strtotime($program['tgl_update'])) ?>
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <a href="program.php" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Lihat
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="program.php" class="btn btn-primary">
                            Lihat Semua Program
                        </a>
                    </div>
                </div>

                <!-- Top Pengisi -->
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="card-title mb-0">Top Pengisi Safari Ramadhan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama Pengisi</th>
                                        <th class="text-center">Total Jadwal</th>
                                        <th class="text-center">Terlaksana</th>
                                        <th class="text-center">Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topPengisi as $pengisi): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pengisi['pengisi']) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?= $pengisi['jumlah_jadwal'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><?= $pengisi['jadwal_terlaksana'] ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning"><?= $pengisi['jadwal_pending'] ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="pengisi.php" class="btn btn-danger">
                            Lihat Semua Pengisi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Menu Cepat -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Menu Cepat</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="tambah_program.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-plus-circle me-2"></i>
                                Tambah Program Baru
                            </a>
                            <a href="pendaftar.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-people me-2"></i>
                                Kelola Pendaftar Safari
                            </a>
                            <a href="pendaftar_ifthar.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-people-fill me-2"></i>
                                Kelola Pendaftar Ifthar
                            </a>
                            <a href="pengisi.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-video3 me-2"></i>
                                Kelola Pengisi
                            </a>
                            <a href="jadwal.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-calendar-check me-2"></i>
                                Kelola Jadwal
                            </a>
                            <a href="profil.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-gear me-2"></i>
                                Pengaturan Profil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>