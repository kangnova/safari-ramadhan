<?php
session_start();
require_once '../koneksi.php';

// Cek login dan ID
if (!isset($_SESSION['authenticated']) || !isset($_GET['id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data laporan
try {
    $stmt = $conn->prepare("
        SELECT js.*, l.nama_lembaga, l.alamat, l.kecamatan, l.penanggung_jawab, l.no_wa, l.jam_aktif,
               p.nama as nama_pengisi 
        FROM jadwal_safari js
        JOIN lembaga l ON js.lembaga_id = l.id
        LEFT JOIN pengisi p ON js.pengisi = p.nama 
        WHERE js.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch();

    if (!$data) {
        die('Data tidak ditemukan');
    }

    // Ambil data pendamping
    $pendamping_stmt = $conn->prepare("
        SELECT nama_pendamping 
        FROM pendamping_safari 
        WHERE jadwal_id = ? 
        ORDER BY id ASC
    ");
    $pendamping_stmt->execute([$_GET['id']]);
    $pendamping_list = $pendamping_stmt->fetchAll();

    // Format hari Indonesia
    $hari = date('l', strtotime($data['tanggal']));
    $hari_id = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pelaksanaan Safari Ramadhan 2025</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .subtitle {
            font-size: 16px;
            margin: 5px 0;
        }
        .content {
            margin: 20px 0;
        }
        .section {
            margin: 15px 0;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ccc;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 150px auto;
            gap: 5px;
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
        .signature-space {
            height: 80px;
        }
        .pendamping-list {
            list-style-type: decimal;
            margin-left: 20px;
            padding-left: 20px;
        }
        .pendamping-list li {
            margin-bottom: 5px;
        }
        .no-data {
            font-style: italic;
            color: #666;
        }
        @media print {
            body {
                margin: 20px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../img/logo.png" alt="Logo GNB" class="logo">
        <div class="title">LAPORAN PELAKSANAAN SAFARI RAMADHAN 2025</div>
        <div class="subtitle">YAYASAN GURU NGAJI BERDAYA KLATEN</div>
    </div>

    <div class="content">
        <div class="section">
            <div class="section-title">A. Data Lembaga</div>
            <div class="info-grid">
                <div class="label">Nama Lembaga</div>
                <div>: <?= htmlspecialchars($data['nama_lembaga']) ?></div>
                
                <div class="label">Alamat</div>
                <div>: <?= htmlspecialchars($data['alamat']) ?></div>
                
                <div class="label">Kecamatan</div>
                <div>: <?= ucwords(str_replace('_', ' ', $data['kecamatan'])) ?></div>
                
                <div class="label">Penanggung Jawab</div>
                <div>: <?= htmlspecialchars($data['penanggung_jawab']) ?></div>
                
                <div class="label">No. WhatsApp</div>
                <div>: <?= htmlspecialchars($data['no_wa']) ?></div>
                
                <div class="label">Jam Aktif</div>
                <div>: <?= htmlspecialchars($data['jam_aktif']) ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">B. Data Kunjungan</div>
            <div class="info-grid">
                <div class="label">Hari, Tanggal</div>
                <div>: <?= $hari_id[$hari] ?>, <?= date('d/m/Y', strtotime($data['tanggal'])) ?></div>
                
                <div class="label">Jam Kegiatan</div>
                <div>: <?= $data['jam'] ?> WIB</div>
                
                <div class="label">Jam Kedatangan</div>
                <div>: <?= $data['jam_kedatangan'] ? $data['jam_kedatangan'] . ' WIB' : '-' ?></div>
                
                <div class="label">Pengisi</div>
                <div>: <?= htmlspecialchars($data['pengisi']) ?></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">C. Data Kehadiran</div>
            <table class="table">
                <tr>
                    <th>Jumlah Santri</th>
                    <th>Jumlah Guru</th>
                    <th>Total Peserta</th>
                </tr>
                <tr>
                    <td><?= $data['jumlah_santri'] ? $data['jumlah_santri'] . ' santri' : '-' ?></td>
                    <td><?= $data['jumlah_guru'] ? $data['jumlah_guru'] . ' guru' : '-' ?></td>
                    <td><?= ($data['jumlah_santri'] + $data['jumlah_guru']) . ' orang' ?></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">D. Pendamping Safari</div>
            <?php if (!empty($pendamping_list)): ?>
                <ol class="pendamping-list">
                    <?php foreach ($pendamping_list as $pendamping): ?>
                        <li><?= htmlspecialchars($pendamping['nama_pendamping']) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="no-data">Tidak ada data pendamping</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title">E. Pesan & Kesan Lembaga</div>
            <p style="text-align: justify;">
                <?= $data['pesan_kesan'] ? nl2br(htmlspecialchars($data['pesan_kesan'])) : 'Belum ada pesan dan kesan yang diisi' ?>
            </p>
        </div>

        <?php if(!empty($data['keterangan'])): ?>
        <div class="section">
            <div class="section-title">F. Keterangan Tambahan</div>
            <p style="text-align: justify;">
                <?= nl2br(htmlspecialchars($data['keterangan'])) ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div>Klaten, <?= date('d/m/Y', strtotime($data['tgl_laporan'])) ?></div>
            <div>Pengisi Safari Ramadhan,</div>
            <div class="signature-space"></div>
            <div><strong><?= htmlspecialchars($data['pengisi']) ?></strong></div>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            Cetak Laporan
        </button>
    </div>
</body>
</html>