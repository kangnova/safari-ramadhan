<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
   header('Location: login.php');
   exit();
}

// Rest of the code remains same...

require_once '../koneksi.php';

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
    GROUP BY l.id
    ORDER BY l.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$pendaftar = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftar Safari Ramadhan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        @media (min-width: 768px) {
            .mobile-cards {
                display: none;
            }
            .desktop-table {
                display: block;
            }
        }
        @media (max-width: 767px) {
            .mobile-cards {
                display: block;
            }
            .desktop-table {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-light">
    
    <!-- Navbar -->
<?php require_once 'nav.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Data Pendaftar Safari Ramadhan</h2>
        
        <!-- Mobile View -->
        <div class="mobile-cards">
            <?php foreach ($pendaftar as $index => $data): ?>
            <div class="card card-pendaftar">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($data['nama_lembaga']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($data['email']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Alamat</div>
                        <div class="info-value"><?= htmlspecialchars($data['alamat']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Kecamatan</div>
                        <div class="info-value"><?= ucfirst($data['kecamatan']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Jumlah Santri</div>
                        <div class="info-value"><?= $data['jumlah_santri'] ?> santri</div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Hari Aktif</div>
                        <div class="info-value"><?= str_replace(',', ', ', $data['hari_aktif']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Jam Aktif</div>
                        <div class="info-value"><?= $data['jam_aktif'] ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Penanggung Jawab</div>
                        <div class="info-value">
                            <?= htmlspecialchars($data['penanggung_jawab']) ?> (<?= $data['jabatan'] ?>)
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">No. WhatsApp</div>
                        <div class="info-value">
                            <a href="https://wa.me/<?= $data['no_wa'] ?>" class="text-decoration-none">
                                <?= $data['no_wa'] ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Materi</div>
                        <div class="info-value"><?= str_replace(',', ', ', $data['materi_dipilih']) ?></div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="info-label">Frekuensi Kunjungan</div>
                        <div class="info-value"><?= $data['frekuensi_kunjungan'] ?> kali</div>
                    </div>

                    <hr>

                    <div class="status-section">
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-<?= $data['duta_gnb'] ? 'success' : 'danger' ?> badge-custom">
                                <?= $data['duta_gnb'] ? 'Bersedia Duta GNB' : 'Belum Bersedia Duta GNB' ?>
                            </span>
                            <span class="badge bg-<?= $data['kesediaan_infaq'] ? 'success' : 'danger' ?> badge-custom">
                                <?= $data['kesediaan_infaq'] ? 'Bersedia Infaq' : 'Belum Bersedia Infaq' ?>
                            </span>
                            <span class="badge bg-info badge-custom">
                                <?= ucfirst($data['manfaat']) ?> Bermanfaat
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    Terdaftar: <?= date('d F Y H:i', strtotime($data['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop View -->
        <!-- Di bagian atas tabel desktop -->
<div class="desktop-table">
   <div class="d-flex justify-content-between align-items-center mb-3">
       <h3>Data Pendaftar</h3>
       <a href="export-excel.php" class="btn btn-success">
           <i class="bi bi-file-excel me-2"></i>Export Excel
       </a>
   </div>
   
   <div class="table-responsive">
       <!-- Table content -->
   </div>
</div>
        <div class="desktop-table">
            <div class="table-responsive">
                
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Lembaga</th>
                            <th>Alamat</th>
                            <th>Kecamatan</th>
                            <th>Santri</th>
                            <th>PJ</th>
                            <th>No. WA</th>
                            <th>Detail</th>
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
                            <td><a href="https://wa.me/<?= $data['no_wa'] ?>"><?= $data['no_wa'] ?></a></td>
                            <td>
               <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $data['id'] ?>">
                   Detail
               </button>
           </td>
           <td>
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
    <!-- Tambahkan modal untuk setiap baris -->
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
    <!--delete-->
    <script>
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