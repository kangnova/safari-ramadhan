<?php
session_start();
require_once '../koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Set header for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="jadwal_safari_ramadhan.xls"');
header('Cache-Control: max-age=0');

try {
    // Query untuk mengambil data jadwal
    $query = "SELECT js.*, l.nama_lembaga, l.alamat, l.kecamatan, l.jumlah_santri, 
              l.penanggung_jawab, l.no_wa
              FROM jadwal_safari js
              JOIN lembaga l ON js.lembaga_id = l.id 
              WHERE YEAR(js.tanggal) = YEAR(NOW())
              ORDER BY js.tanggal ASC, js.jam ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $jadwal_list = $stmt->fetchAll();

    // Output Excel content
    echo '
    <table border="1">
        <tr>
            <th>No</th>
            <th>Hari</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>Lembaga</th>
            <th>Kecamatan</th>
            <th>Alamat</th>
            <th>Jumlah Santri</th>
            <th>Penanggung Jawab</th>
            <th>No WhatsApp</th>
            <th>Pengisi</th>
            <th>Status</th>
        </tr>';

    $no = 1;
    foreach($jadwal_list as $row) {
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

        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . $hari_id[$hari] . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
        echo '<td>' . $row['jam'] . '</td>';
        echo '<td>' . $row['nama_lembaga'] . '</td>';
        echo '<td>' . ucwords(str_replace('_', ' ', $row['kecamatan'])) . '</td>';
        echo '<td>' . $row['alamat'] . '</td>';
        echo '<td>' . $row['jumlah_santri'] . '</td>';
        echo '<td>' . $row['penanggung_jawab'] . '</td>';
        echo '<td>' . $row['no_wa'] . '</td>';
        echo '<td>' . $row['pengisi'] . '</td>';
        echo '<td>' . ucfirst($row['status']) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>