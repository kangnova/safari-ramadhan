<?php
session_start();
require_once '../koneksi.php';

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

$dutaGNB = [];
$totalDutaGNB = 0;

try {
    // Query untuk mendapatkan data Duta GNB
    $query = "SELECT l.*, pl.frekuensi_kunjungan 
              FROM lembaga l 
              INNER JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id 
              WHERE pl.duta_gnb = 1 
              ORDER BY l.created_at DESC";
    $stmt = $conn->query($query);
    $dutaGNB = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hitung total Duta GNB
    $stmt = $conn->query("SELECT COUNT(*) FROM persetujuan_lembaga WHERE duta_gnb = 1");
    $totalDutaGNB = $stmt->fetchColumn();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Duta GNB - Admin Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Data Duta GNB</h1>
                <p class="text-muted">Menampilkan daftar lembaga yang bersedia menjadi Duta GNB</p>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0">Total Duta GNB</h6>
                                <h2 class="my-2"><?= $totalDutaGNB ?></h2>
                                <p class="card-text mb-0">Lembaga bersedia</p>
                            </div>
                            <i class="bi bi-star fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="card-title mb-0">Daftar Lembaga Duta GNB</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="dutaTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Lembaga</th>
                                <th>Kecamatan</th>
                                <th>Penanggung Jawab</th>
                                <th>No. WhatsApp</th>
                                <th>Jumlah Santri</th>
                                <th>Frekuensi</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($dutaGNB as $duta): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($duta['nama_lembaga']) ?></td>
                                <td><?= ucwords(str_replace('_', ' ', $duta['kecamatan'])) ?></td>
                                <td><?= htmlspecialchars($duta['penanggung_jawab']) ?></td>
                                <td>
                                    <a href="https://wa.me/<?= $duta['no_wa'] ?>" target="_blank" class="text-decoration-none">
                                        <i class="bi bi-whatsapp text-success"></i> 
                                        <?= $duta['no_wa'] ?>
                                    </a>
                                </td>
                                <td><?= $duta['jumlah_santri'] ?></td>
                                <td><?= $duta['frekuensi_kunjungan'] ?>x</td>
                                <td><?= date('d/m/Y', strtotime($duta['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dutaTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                }
            });
        });
    </script>
</body>
</html>