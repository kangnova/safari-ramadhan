<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Cek ID Donasi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Donasi tidak valid.");
}

$id = intval($_GET['id']);

// Ambil data donasi
try {
    $stmt = $conn->prepare("
        SELECT d.*, pd.judul as program_judul 
        FROM donasi d 
        LEFT JOIN program_donasi pd ON d.program_id = pd.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$id]);
    $donasi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donasi) {
        die("Data donasi tidak ditemukan.");
    }

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Function format rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Function penyebut terbilang (Sederhana)
function terbilang($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai <20) {
        $temp = terbilang($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = terbilang($nilai/10)." puluh". terbilang($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . terbilang($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = terbilang($nilai/100) . " ratus" . terbilang($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . terbilang($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = terbilang($nilai/1000) . " ribu" . terbilang($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = terbilang($nilai/1000000) . " juta" . terbilang($nilai % 1000000);
    }
    return $temp;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Donasi #<?= $donasi['token'] ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            width: 800px;
            margin: 20px auto;
            border: 2px solid #ccc;
            padding: 40px;
            position: relative;
        }
        .header {
            text-align: center;
            border-bottom: 2px double #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #003366;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .address {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .content {
            margin-bottom: 40px;
        }
        .row {
            display: flex;
            margin-bottom: 10px;
        }
        .label {
            width: 180px;
            font-weight: bold;
        }
        .separator {
            width: 20px;
        }
        .value {
            flex: 1;
            border-bottom: 1px dotted #ccc;
        }
        .amount-box {
            background-color: #f0f0f0;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            border: 1px solid #ddd;
            margin-top: 10px;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
        }
        .signature {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            margin-top: 70px;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-weight: bold;
        }
        .note {
            font-size: 11px;
            color: #888;
            margin-top: 40px;
            font-style: italic;
        }
        @media print {
            .no-print {
                display: none;
            }
            .container {
                border: none;
                width: 100%;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align:center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Cetak Kwitansi</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Tutup</button>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">SAFARI RAMADHAN</div>
            <div class="address">Panitia Kegiatan Ramadhan & Idul Fitri<br>Masjid Agung & Islamic Centre</div>
        </div>

        <div class="title">TANDA TERIMA DONASI</div>

        <div class="content">
            <div class="row">
                <div class="label">No. Kwitansi</div>
                <div class="separator">:</div>
                <div class="value"><?= $donasi['token'] ?></div>
            </div>
            <div class="row">
                <div class="label">Telah Terima Dari</div>
                <div class="separator">:</div>
                <div class="value"><?= $donasi['is_anonim'] ? 'Hamba Allah' : htmlspecialchars($donasi['nama_donatur']) ?></div>
            </div>
            <div class="row">
                <div class="label">Guna Membayar</div>
                <div class="separator">:</div>
                <div class="value">Donasi Program <?= htmlspecialchars($donasi['program_judul'] ?? 'Umum') ?></div>
            </div>
            <div class="row">
                <div class="label">Sebesar</div>
                <div class="separator">:</div>
                <div class="value" style="font-style: italic; text-transform: capitalize;"><?= trim(terbilang($donasi['nominal'])) ?> rupiah</div>
            </div>
            
            <div class="amount-box">
                <?= formatRupiah($donasi['nominal']) ?>
            </div>
        </div>

        <div class="footer">
            <div class="signature">
                <br>
                <div class="signature-line"></div>
            </div>
            <div class="signature">
                <?= date('d F Y', strtotime($donasi['created_at'])) ?><br>
                Penerima,
                <div class="signature-line">Panitia Safari Ramadhan</div>
            </div>
        </div>

        <div class="note">
            * Kwitansi ini adalah bukti pembayaran yang sah.<br>
            * Dicetak otomatis oleh sistem pada tanggal <?= date('d/m/Y H:i') ?>
        </div>
    </div>

</body>
</html>
