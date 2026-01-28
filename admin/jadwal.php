<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Koneksi
require_once '../koneksi.php';
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Proses tambah jadwal
if(isset($_POST['tambah'])) {
    try {
        $tanggal = $_POST['tanggal'];
        $jam = $_POST['jam'];
        $lembaga_id = $_POST['lembaga_id'];
        $pengisi = $_POST['pengisi'];
        
        // Insert jadwal
        $query = "INSERT INTO jadwal_safari (tanggal, jam, lembaga_id, pengisi, status) 
                  VALUES (:tanggal, :jam, :lembaga_id, :pengisi, 'pending')";
                  
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':tanggal' => $tanggal,
            ':jam' => $jam,
            ':lembaga_id' => $lembaga_id,
            ':pengisi' => $pengisi
        ]);
        
        $jadwal_id = $conn->lastInsertId();

        // Insert Pendamping
        if(isset($_POST['pendamping_id']) && !empty($_POST['pendamping_id'])) {
            $pendamping_id = $_POST['pendamping_id'];
            
            // Get Pendamping Info
            $stmt_p = $conn->prepare("SELECT nama, id FROM pendamping WHERE id = ?");
            $stmt_p->execute([$pendamping_id]);
            $p_data = $stmt_p->fetch(PDO::FETCH_ASSOC);
            
            if($p_data) {
                $query_pendamping = "INSERT INTO pendamping_safari (jadwal_id, pendamping_id, nama_pendamping) VALUES (:jadwal_id, :pendamping_id, :nama_pendamping)";
                $stmt_pendamping = $conn->prepare($query_pendamping);
                $stmt_pendamping->execute([
                    ':jadwal_id' => $jadwal_id,
                    ':pendamping_id' => $p_data['id'],
                    ':nama_pendamping' => $p_data['nama']
                ]);
            }
        }
        
        $success = "Jadwal berhasil ditambahkan!";
        
        // Refresh halaman setelah tambah
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses update status
if(isset($_POST['update_status'])) {
    try {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $query = "UPDATE jadwal_safari SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
        
        $success = "Status berhasil diupdate!";
        
        // Refresh halaman setelah update
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses update jadwal
if(isset($_POST['update_jadwal'])) {
    try {
        $id = $_POST['id'];
        $tanggal = $_POST['tanggal'];
        $jam = $_POST['jam'];
        $pengisi = $_POST['pengisi'];
        
        // Update jadwal info
        $query = "UPDATE jadwal_safari SET tanggal = :tanggal, jam = :jam, pengisi = :pengisi WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':tanggal' => $tanggal,
            ':jam' => $jam,
            ':pengisi' => $pengisi,
            ':id' => $id
        ]);
        
        // Update Pendamping
        if(isset($_POST['pendamping_id'])) {
            $pendamping_id = $_POST['pendamping_id'];
            
            // Check current pendamping
            $check_q = "SELECT id FROM pendamping_safari WHERE jadwal_id = ?";
            $check_stmt = $conn->prepare($check_q);
            $check_stmt->execute([$id]);
            $exists = $check_stmt->fetch();
            
            // Get Pendamping Name
            if(!empty($pendamping_id)) {
                $p_stmt = $conn->prepare("SELECT nama FROM pendamping WHERE id = ?");
                $p_stmt->execute([$pendamping_id]);
                $p_name = $p_stmt->fetchColumn();
                
                if($exists) {
                    // Update
                    $up_q = "UPDATE pendamping_safari SET pendamping_id = :pid, nama_pendamping = :pname WHERE jadwal_id = :jid";
                    $up_stmt = $conn->prepare($up_q);
                    $up_stmt->execute([':pid' => $pendamping_id, ':pname' => $p_name, ':jid' => $id]);
                } else {
                    // Insert
                    $in_q = "INSERT INTO pendamping_safari (jadwal_id, pendamping_id, nama_pendamping) VALUES (:jid, :pid, :pname)";
                    $in_stmt = $conn->prepare($in_q);
                    $in_stmt->execute([':jid' => $id, ':pid' => $pendamping_id, ':pname' => $p_name]);
                }
            } else {
                 // If pendamping_id is empty, maybe we should delete the mapping? 
                 // For now, let's keep it simple, if they select "Pilih Pendamping" (empty), we might want to clear it?
                 // Let's assume selecting empty means no pendamping.
                 if($exists) {
                     $del_q = "DELETE FROM pendamping_safari WHERE jadwal_id = ?";
                     $del_stmt = $conn->prepare($del_q);
                     $del_stmt->execute([$id]);
                 }
            }
        }

        $success = "Jadwal berhasil diupdate!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil data jadwal dengan JOIN ke tabel lembaga
try {
    $query = "SELECT js.*, l.nama_lembaga, l.alamat, l.jumlah_santri, l.penanggung_jawab, l.no_wa, l.kecamatan, l.share_loc,
              ps.pendamping_id, ps.nama_pendamping, p.no_hp as no_hp_pendamping
              FROM jadwal_safari js
              JOIN lembaga l ON js.lembaga_id = l.id 
              LEFT JOIN pendamping_safari ps ON js.id = ps.jadwal_id
              LEFT JOIN pendamping p ON ps.pendamping_id = p.id
              WHERE YEAR(js.tanggal) = YEAR(NOW())
              ORDER BY js.tanggal ASC, js.jam ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jadwal_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Ambil data lembaga yang belum dijadwalkan
try {
    $query_lembaga = "SELECT l.*, 
                      GROUP_CONCAT(DISTINCT ha.hari) as hari_aktif,
                      GROUP_CONCAT(DISTINCT md.materi) as materi_dipilih,
                      pl.frekuensi_kunjungan,
                      (SELECT COUNT(*) FROM jadwal_safari js WHERE js.lembaga_id = l.id) as jumlah_terjadwal
                      FROM lembaga l
                      LEFT JOIN hari_aktif ha ON l.id = ha.lembaga_id 
                      LEFT JOIN materi_dipilih md ON l.id = md.lembaga_id
                      LEFT JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
                      GROUP BY l.id
                      HAVING jumlah_terjadwal < frekuensi_kunjungan OR jumlah_terjadwal IS NULL
                      ORDER BY l.nama_lembaga ASC";
                      
    $stmt = $conn->prepare($query_lembaga);
    $stmt->execute();
    $lembaga_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}


// Tambahkan ini di bagian PHP awal file setelah mengambil data jadwal_list
$modalData = [];
if (!empty($jadwal_list)) {
    foreach($jadwal_list as $jadwal) {
        if ($jadwal['status'] == 'terlaksana') {
            $modalData[] = $jadwal;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Safari Ramadhan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!--/////////////////////-->
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
<body>
    
    <?php require_once 'includes/header.php'; ?>
    
    <div class="container my-4">
        <div>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Jadwal Safari Ramadhan <?= date('Y') ?></h1>
                    <?php 
if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                        <i class='bx bx-plus'></i> Tambah Jadwal
                    </button>
                </div>

                <?php if(isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

<!-- Export dan Pencarian -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <div class="btn-group">
                    <a href="export_excel.php" class="btn btn-success">
                        <i class="bi bi-file-excel me-2"></i>Export Excel
                    </a>
                    <a href="import_jadwal.php" class="btn btn-primary">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Import Jadwal
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <input type="text" id="searchBox" class="form-control" placeholder="Cari...">
                </div>
            </div>
        </div>
    </div>
</div>
                <!-- Tabel Jadwal -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Hari</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Lembaga</th>
                                        <th>Pengisi</th>
                                        <th>Pendamping</th>
                                        <th>Maps</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    if (!empty($jadwal_list)):
                                        foreach($jadwal_list as $row): 
                                            $hari = date('l', strtotime($row['tanggal']));
                                            $hari_id = [
                                                'Sunday' => 'Minggu',
                                                'Monday' => 'Senin',
                                                'Tuesday' => 'Selasa',
                                                'Wednesday' => 'Rabu',
                                                'Thursday' => 'Kamis',
                                                'Friday' => 'Jumat',
                                                'Saturday' => 'Sabtu'
                                            ];
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= $hari_id[$hari] ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                        <td><?= $row['jam'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_lembaga']) ?></td>
                                        <td><?= htmlspecialchars($row['pengisi']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['nama_pendamping'] ?? '-') ?>
                                            <?php if(!empty($row['no_hp_pendamping'])): ?>
                                                <br>
                                                <a href="https://wa.me/<?= $row['no_hp_pendamping'] ?>" target="_blank" class="text-success text-decoration-none">
                                                    <small><i class='bx bxl-whatsapp'></i> <?= $row['no_hp_pendamping'] ?></small>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['share_loc'])): ?>
                                            <a href="<?= htmlspecialchars($row['share_loc']) ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-secondary" title="Buka Maps">
                                                <i class='bx bx-map'></i>
                                            </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $row['status'] == 'terlaksana' ? 'success' : 
                                                ($row['status'] == 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td>
    <button class="btn btn-sm btn-info" 
            data-bs-toggle="modal" 
            data-bs-target="#detailModal<?= $row['id'] ?>" title="Detail Jadwal">
        <i class='bx bx-detail'></i>
    </button>
    </button>
    <button class="btn btn-sm btn-primary" 
            title="Edit Jadwal"
            onclick="editJadwal(<?= $row['id'] ?>, '<?= $row['tanggal'] ?>', '<?= $row['jam'] ?>', '<?= htmlspecialchars($row['nama_lembaga']) ?>', '<?= htmlspecialchars($row['pengisi']) ?>', '<?= $row['pendamping_id'] ?? '' ?>')">
        <i class='bx bx-edit'></i>
    </button>
    <button class="btn btn-sm btn-warning" 
            onclick="editStatus(<?= $row['id'] ?>, '<?= $row['status'] ?>')" title="Update Status">
        <i class='bx bx-refresh'></i>
    </button>
    <button class="btn btn-sm btn-danger" 
            onclick="confirmDelete(<?= $row['id'] ?>)" title="Hapus Jadwal">
        <i class='bx bx-trash'></i>
    </button>
    <a href="https://wa.me/<?= $row['no_wa'] ?>" 
       target="_blank" 
       class="btn btn-sm btn-success" title="Chat WA">
        <i class='bx bxl-whatsapp'></i>
    </a>
    <?php if($row['status'] == 'terlaksana'): ?>
    <button class="btn btn-sm btn-primary" 
            data-bs-toggle="modal" 
            data-bs-target="#laporanModal<?= $row['id'] ?>">
        <i class='bx bx-file'></i> Laporan
    </button>
    <?php endif; ?>
</td>
                                    </tr>

                                    <!-- Modal Detail -->
                                    <div class="modal fade" id="detailModal<?= $row['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detail Jadwal</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
    <p><strong>Lembaga:</strong> <?= $row['nama_lembaga'] ?></p>
    <p><strong>Kecamatan:</strong> <?= ucwords(str_replace('_', ' ', $row['kecamatan'])) ?></p>
    <p><strong>Alamat:</strong> <?= $row['alamat'] ?></p>
    <p><strong>Jumlah Santri:</strong> <?= $row['jumlah_santri'] ?></p>
    <p><strong>Penanggung Jawab:</strong> <?= $row['penanggung_jawab'] ?></p>
    <p><strong>No. WhatsApp:</strong> <?= $row['no_wa'] ?></p>
    <?php if(!empty($row['share_loc'])): ?>
        <p><strong>Lokasi:</strong> <a href="<?= htmlspecialchars($row['share_loc']) ?>" target="_blank" class="text-decoration-none"><i class='bx bx-map'></i> Buka Google Maps</a></p>
    <?php endif; ?>
    <p><strong>Jadwal:</strong> <?= $hari_id[$hari] ?>, <?= date('d/m/Y', strtotime($row['tanggal'])) ?> <?= $row['jam'] ?></p>
    <p><strong>Pengisi:</strong> <?= $row['pengisi'] ?></p>
    <p><strong>Pendamping:</strong> <?= $row['nama_pendamping'] ?? '-' ?></p>
    <p><strong>Status:</strong> <?= ucfirst($row['status']) ?></p>
    <p><strong>Frekuensi:</strong> 
        <?php
        $query_frek = "SELECT COUNT(*) as total FROM jadwal_safari WHERE lembaga_id = :lembaga_id";
        $stmt_frek = $conn->prepare($query_frek);
        $stmt_frek->execute([':lembaga_id' => $row['lembaga_id']]);
        $frek = $stmt_frek->fetch();
        
        $query_target = "SELECT frekuensi_kunjungan FROM persetujuan_lembaga WHERE lembaga_id = :lembaga_id";
        $stmt_target = $conn->prepare($query_target);
        $stmt_target->execute([':lembaga_id' => $row['lembaga_id']]);
        $target = $stmt_target->fetch();
        
        echo $frek['total'] . " dari " . $target['frekuensi_kunjungan'] . " kali";
        ?>
    </p>
</div>
                                            </div>
                                        </div>
                                    </div>


                                    
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Tidak ada data jadwal</td>
                                    </tr>

                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>


                        
                    </div>
                </div>
        </div>
    </div>
<!--Modal laporan-->
<!-- Modal Laporan -->
<!-- Modal Laporan -->
<?php foreach($modalData as $row): ?>
<div class="modal fade" id="laporanModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Laporan Pelaksanaan Kegiatan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Detail Lembaga:</h6>
                        <p class="mb-1"><strong>Nama:</strong> <?= $row['nama_lembaga'] ?></p>
                        <p class="mb-1"><strong>Alamat:</strong> <?= $row['alamat'] ?></p>
                        <p class="mb-1"><strong>Kecamatan:</strong> <?= ucwords(str_replace('_', ' ', $row['kecamatan'])) ?></p>
                        <p class="mb-1"><strong>Penanggung Jawab:</strong> <?= ucwords(str_replace('_', ' ', $row['penanggung_jawab'])) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Detail Kunjungan:</h6>
                        <p class="mb-1"><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($row['tanggal'])) ?></p>
                        <p class="mb-1"><strong>Jam Kegiatan:</strong> <?= $row['jam'] ?></p>
                        <p class="mb-1"><strong>Pengisi:</strong> <?= $row['pengisi'] ?></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h6 class="fw-bold">Laporan Kehadiran:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Jam Kedatangan</th>
                                    <td><?= !empty($row['jam_kedatangan']) ? $row['jam_kedatangan'] : '-' ?></td>
                                    <th>Jumlah Santri Hadir</th>
                                    <td><?= !empty($row['jumlah_santri']) ? $row['jumlah_santri'] : '-' ?> santri</td>
                                </tr>
                                <tr>
                                    <th>Jumlah Guru Hadir</th>
                                    <td><?= !empty($row['jumlah_guru']) ? $row['jumlah_guru'] : '-' ?> guru</td>
                                    <th>Tanggal Laporan</th>
                                    <td><?= !empty($row['tgl_laporan']) ? date('d/m/Y H:i', strtotime($row['tgl_laporan'])) : '-' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="fw-bold">Nama Pendamping:</h6>
                    <div class="border p-2 rounded bg-light">
                        <?php
                        // Query untuk mendapatkan data pendamping
                        try {
                            $pendamping_query = "SELECT nama_pendamping FROM pendamping_safari WHERE jadwal_id = :jadwal_id ORDER BY id ASC";
                            $pendamping_stmt = $conn->prepare($pendamping_query);
                            $pendamping_stmt->execute([':jadwal_id' => $row['id']]);
                            $pendamping_list = $pendamping_stmt->fetchAll();
                            
                            if (!empty($pendamping_list)) {
                                echo '<ol class="mb-0 ps-3">';
                                foreach ($pendamping_list as $pendamping) {
                                    echo '<li>' . htmlspecialchars($pendamping['nama_pendamping']) . '</li>';
                                }
                                echo '</ol>';
                            } else {
                                echo '<p class="mb-0">Tidak ada data pendamping</p>';
                            }
                        } catch(PDOException $e) {
                            echo '<p class="mb-0 text-danger">Error: ' . $e->getMessage() . '</p>';
                        }
                        ?>
                    </div>
                </div>

                <div class="mt-3">
                    <h6 class="fw-bold">Pesan & Kesan Lembaga:</h6>
                    <p class="border p-2 rounded bg-light">
                        <?= !empty($row['pesan_kesan']) ? nl2br(htmlspecialchars($row['pesan_kesan'])) : 'Belum ada pesan dan kesan yang diisi' ?>
                    </p>
                </div>

                <?php if(!empty($row['keterangan'])): ?>
                <div class="mt-3">
                    <h6 class="fw-bold">Keterangan Tambahan:</h6>
                    <p class="border p-2 rounded bg-light">
                        <?= nl2br(htmlspecialchars($row['keterangan'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="cetak_laporan.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary">
                    <i class='bx bx-printer'></i> Cetak Laporan
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    // Tambahkan ini untuk memastikan modal bekerja dengan benar
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('[autofocus]').focus();
    });
});
</script>
    <!-- Modal Tambah Jadwal -->
    <?php include 'modal_tambah_jadwal.php'; ?>

    <!-- Modal Update Status -->
    <?php include 'modal_update_status.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <?php include 'jadwal_scripts.php'; ?>

<!-- Modal Edit Jadwal -->
<div class="modal fade" id="editJadwalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Jadwal Safari</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Lembaga</label>
                        <input type="text" id="edit_lembaga" class="form-control" readonly disabled>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam</label>
                            <input type="time" name="jam" id="edit_jam" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pengisi</label>
                        <select name="pengisi" id="edit_pengisi" class="form-select" required>
                            <option value="">Pilih Pengisi</option>
                            <?php 
                            // Re-use pengisi query logic or just manual query here if needed, 
                            // but best to just use the same query as modal_tambah_jadwal.php 
                            // Since we can't easily include it inside this loop or function scope, let's query again or assume $conn is available.
                            // The easiest way is to query here.
                            $q_p = "SELECT * FROM pengisi WHERE status = 'aktif' ORDER BY nama ASC";
                            $s_p = $conn->prepare($q_p);
                            $s_p->execute();
                            while($p = $s_p->fetch()) {
                                echo "<option value='" . htmlspecialchars($p['nama']) . "'>" . htmlspecialchars($p['nama']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pendamping</label>
                        <select name="pendamping_id" id="edit_pendamping" class="form-select">
                            <option value="">Pilih Pendamping</option>
                            <?php 
                            $q_pd = "SELECT * FROM pendamping WHERE status = 'aktif' ORDER BY nama ASC";
                            $s_pd = $conn->prepare($q_pd);
                            $s_pd->execute();
                            while($pd = $s_pd->fetch()) {
                                echo "<option value='" . $pd['id'] . "'>" . htmlspecialchars($pd['nama']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_jadwal" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editJadwal(id, tanggal, jam, lembaga, pengisi, pendamping_id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_jam').value = jam;
    document.getElementById('edit_lembaga').value = lembaga;
    document.getElementById('edit_pengisi').value = pengisi;
    document.getElementById('edit_pendamping').value = pendamping_id;
    
    new bootstrap.Modal(document.getElementById('editJadwalModal')).show();
}
</script>
    
    <!--// hapus-->
    <script>
        
    function confirmDelete(id) {
    if(confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {
        window.location.href = 'delete_jadwal.php?id=' + id;
    }
}
    </script>
    <!--// hapus-->
    
</body>
</html>