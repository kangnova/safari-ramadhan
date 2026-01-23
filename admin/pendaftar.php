<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

// Initialize variables
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$kecamatan = isset($_GET['kecamatan']) ? $_GET['kecamatan'] : '';

// Handle Quota Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quota'])) {
    $newQuota = (int)$_POST['quota_safari'];
    try {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = 'safari_quota'");
        $stmt->execute(['val' => $newQuota]);
        echo "<script>alert('Kuota berhasil diperbarui!'); window.location.href='pendaftar.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Gagal memperbarui kuota: " . $e->getMessage() . "');</script>";
    }
}

$stats = [
    'total_lembaga' => 0,
    'total_santri' => 0,
    'total_kecamatan' => 0,
    'total_duta' => 0
];
$statistikMateri = [];
$pendaftar = [];
$listKecamatan = [];
$quotaSafari = 170; // Default

try {
    // Get Quota
    $stmtQuota = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_quota'");
    $stmtQuota->execute();
    $quotaSafari = (int)$stmtQuota->fetchColumn();

    // Ambil daftar unik kecamatan untuk filter dropdown
    $stmtKec = $conn->prepare("SELECT DISTINCT kecamatan FROM lembaga WHERE kecamatan IS NOT NULL AND kecamatan != '' ORDER BY kecamatan ASC");
    $stmtKec->execute();
    $listKecamatan = $stmtKec->fetchAll(PDO::FETCH_COLUMN);

    // Query untuk data statistik
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
    $fetchedStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($fetchedStats) {
        $stats = $fetchedStats;
    }

    // Query untuk statistik materi
    $queryMateri = "
        SELECT md.materi, COUNT(*) as jumlah 
        FROM materi_dipilih md
        JOIN lembaga l ON md.lembaga_id = l.id
        WHERE YEAR(l.created_at) = :tahun
        GROUP BY md.materi";
    $stmtMateri = $conn->prepare($queryMateri);
    $stmtMateri->execute(['tahun' => $tahun]);
    $statistikMateri = $stmtMateri->fetchAll(PDO::FETCH_ASSOC);

    // Format data statistik materi
    $materiStats = [
        'Berkisah Islami' => 0,
        'Motivasi & Muhasabah' => 0,
        'Kajian Buka Bersama' => 0,
        'Tahfiz Surat Al-Mulk' => 0
    ];

    foreach ($statistikMateri as $stat) {
        $materiStats[$stat['materi']] = $stat['jumlah'];
    }

    // Query untuk data pendaftar (Filtered)
    $query = "
        SELECT 
            l.*,
            GROUP_CONCAT(DISTINCT ha.hari) as hari_aktif,
            GROUP_CONCAT(DISTINCT md.materi) as materi_dipilih,
            pl.frekuensi_kunjungan,
            pl.persetujuan_ketentuan,
            pl.duta_gnb,
            pl.kesediaan_infaq,
            pl.manfaat,
            pl.pemahaman_kerjasama
        FROM lembaga l
        LEFT JOIN hari_aktif ha ON l.id = ha.lembaga_id
        LEFT JOIN materi_dipilih md ON l.id = md.lembaga_id
        LEFT JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
        WHERE YEAR(l.created_at) = :tahun";
    
    // Add Kecamatan Filter
    $params = ['tahun' => $tahun];
    if ($kecamatan) {
        $query .= " AND l.kecamatan = :kecamatan";
        $params['kecamatan'] = $kecamatan;
    }

    $query .= "
        GROUP BY l.id
        ORDER BY l.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $pendaftar = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error in pendaftar.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftar Safari Ramadhan</title>
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
        .card-pendaftar {
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9rem;
        }
        .info-value {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        .badge-custom {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        @media (max-width: 767px) {
            .stats-row .col-md-3 {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body class="bg-light">
    
    <?php require_once 'includes/header.php'; ?>

    <div class="container-fluid mt-4 px-4">
        <h2 class="text-center mb-4">Dashboard Safari Ramadhan <?= htmlspecialchars($tahun) ?></h2>
        
        <!-- Manage Quota -->
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-gear-fill"></i> Pengaturan Kuota</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Batas Maksimal Pendaftar:</label>
                        <input type="number" name="quota_safari" class="form-control" value="<?= $quotaSafari ?>" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="update_quota" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Simpan
                        </button>
                    </div>
                    <div class="col-md-7 text-end">
                        <div class="d-inline-block bg-light p-2 rounded border">
                            <small class="text-muted d-block">Status Saat Ini</small>
                            <span class="fw-bold text-success">Terisi: <?= $stats['total_lembaga'] ?></span> / 
                            <span class="fw-bold text-danger">Batas: <?= $quotaSafari ?></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filter Kecamatan -->
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="GET" class="row align-items-center justify-content-center">
                    <input type="hidden" name="tahun" value="<?= htmlspecialchars($tahun) ?>">
                    <div class="col-auto">
                        <label class="fw-bold">Filter Wilayah:</label>
                    </div>
                    <div class="col-auto">
                        <select name="kecamatan" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Semua Kecamatan --</option>
                            <?php foreach ($listKecamatan as $kec): ?>
                                <option value="<?= htmlspecialchars($kec) ?>" <?= htmlspecialchars($kecamatan) == $kec ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kec) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($kecamatan): ?>
            <!-- Statistik Per Kecamatan -->
            <div class="alert alert-info text-center mb-4">
                <h4><i class="bi bi-geo-alt-fill"></i> Data Kecamatan <?= htmlspecialchars($kecamatan) ?></h4>
                <div class="row justify-content-center mt-3">
                    <div class="col-md-3">
                        <div class="card bg-white text-dark shadow-sm">
                            <div class="card-body">
                                <h6>Total Lembaga</h6>
                                <h3><?= count($pendaftar) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-white text-dark shadow-sm">
                            <div class="card-body">
                                <h6>Total Santri</h6>
                                <h3><?= number_format(array_sum(array_column($pendaftar, 'jumlah_santri'))) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Statistik Umum (Tampil jika tidak ada filter) -->
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
                    <div class="card stats-card h-100 bg-info text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-geo-alt stats-icon"></i>
                            <h5 class="card-title">Kecamatan</h5>
                            <h2 class="mb-0"><?= $stats['total_kecamatan'] ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-star stats-icon"></i>
                            <h5 class="card-title">Duta GNB</h5>
                            <h2 class="mb-0"><?= $stats['total_duta'] ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistik Materi -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 bg-primary bg-opacity-75 text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-book stats-icon"></i>
                            <h5 class="card-title">Berkisah Islami</h5>
                            <h2 class="mb-0"><?= $materiStats['Berkisah Islami'] ?></h2>
                            <small>Permintaan</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 bg-success bg-opacity-75 text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-lightbulb stats-icon"></i>
                            <h5 class="card-title">Motivasi</h5>
                            <h2 class="mb-0"><?= $materiStats['Motivasi & Muhasabah'] ?></h2>
                            <small>Permintaan</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 bg-info bg-opacity-75 text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-people stats-icon"></i>
                            <h5 class="card-title">Kajian</h5>
                            <h2 class="mb-0"><?= $materiStats['Kajian Buka Bersama'] ?></h2>
                            <small>Permintaan</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stats-card h-100 bg-warning bg-opacity-75 text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-journal-text stats-icon"></i>
                            <h5 class="card-title">Tahfiz Al-Mulk</h5>
                            <h2 class="mb-0"><?= $materiStats['Tahfiz Surat Al-Mulk'] ?></h2>
                            <small>Permintaan</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel Data -->
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?= $kecamatan ? 'Daftar Lembaga di ' . htmlspecialchars($kecamatan) : 'Data Semua Pendaftar' ?>
                    </h5>
                    <div>
                        <a href="export-excel.php<?= $kecamatan ? '?kecamatan='.urlencode($kecamatan) : '' ?>" class="btn btn-success btn-sm">
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
                                <th>Alamat</th>
                                <th>Kecamatan</th>
                                <th>Santri</th>
                                <th>PJ</th>
                                <th>No. WA</th>
                                <th width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendaftar as $index => $data): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($data['nama_lembaga']) ?></td>
                                <td><?= htmlspecialchars($data['alamat']) ?></td>
                                <td><?= ucfirst($data['kecamatan']) ?></td>
                                <td><?= $data['jumlah_santri'] ?></td>
                                <td><?= htmlspecialchars($data['penanggung_jawab']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= $data['no_wa'] ?>" target="_blank">
                                        <?= $data['no_wa'] ?>
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $data['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteLembaga(<?= $data['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                    <h5 class="modal-title">Detail <?= htmlspecialchars($data['nama_lembaga']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Informasi Lembaga</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="40%">Email</td>
                                    <td><?= htmlspecialchars($data['email']) ?></td>
                                </tr>
                                <tr>
                                    <td>Alamat</td>
                                    <td><?= htmlspecialchars($data['alamat']) ?></td>
                                </tr>
                                <tr>
                                    <td>Hari Aktif</td>
                                    <td><?= str_replace(',', ', ', $data['hari_aktif']) ?></td>
                                </tr>
                                <tr>
                                    <td>Jam Aktif</td>
                                    <td><?= $data['jam_aktif'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-primary">Informasi Program</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td width="40%">Materi</td>
                                    <td><?= str_replace(',', ', ', $data['materi_dipilih']) ?></td>
                                </tr>
                                <tr>
                                    <td>Frekuensi</td>
                                    <td><?= $data['frekuensi_kunjungan'] ?> kali</td>
                                </tr>
                                <tr>
                                    <td>Duta GNB</td>
                                    <td>
                                        <span class="badge bg-<?= $data['duta_gnb'] ? 'success' : 'secondary' ?>">
                                            <?= $data['duta_gnb'] ? 'Bersedia' : 'Belum Bersedia' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Kesediaan Infaq</td>
                                    <td>
                                        <span class="badge bg-<?= $data['kesediaan_infaq'] ? 'success' : 'secondary' ?>">
                                            <?= $data['kesediaan_infaq'] ? 'Ya' : 'Tidak' ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Manfaat</td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= ucfirst($data['manfaat']) ?> Bermanfaat
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <h6 class="text-primary">Kontak PJ</h6>
                            <div class="d-flex gap-3">
                                <div class="btn-group">
                                    <a href="https://wa.me/<?= $data['no_wa'] ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="bi bi-whatsapp"></i> Chat WhatsApp
                                    </a>
                                    <a href="mailto:<?= $data['email'] ?>" class="btn btn-primary btn-sm">
                                        <i class="bi bi-envelope"></i> Kirim Email
                                    </a>
                                </div>
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

        function deleteLembaga(id) {
            if(confirm('Apakah Anda yakin ingin menghapus data lembaga ini?')) {
                $.ajax({
                    url: 'delete_lembaga.php',
                    type: 'POST',
                    data: {id: id},
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            alert('Data berhasil dihapus');
                            location.reload();
                        } else {
                            alert('Gagal menghapus data: ' + response.message);
                        }
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