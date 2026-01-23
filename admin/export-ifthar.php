<?php
// Set headers untuk download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data Pendaftar Ifthar 1000 Santri.xls"');

// Query data
require_once '../koneksi.php';
$query = "SELECT 
    id,
    email,
    nama_lengkap,
    no_hp,
    asal_lembaga,
    jumlah_santri,
    santri_yatim,
    status,
    created_at
FROM ifthar
ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output table header
echo '<table border="1">';
echo '<tr>
        <th>No</th>
        <th>Asal Lembaga</th>
        <th>Email</th>
        <th>Nama Lengkap</th>
        <th>No HP</th>
        <th>Jumlah Santri</th>
        <th>Pengajuan Santri Yatim</th>
        <th>Status</th>
        <th>Tgl Daftar</th>
    </tr>';

// Output data
foreach($data as $index => $row) {
    echo '<tr>';
    echo '<td>'.($index + 1).'</td>';
    echo '<td>'.$row['asal_lembaga'].'</td>';
    echo '<td>'.$row['email'].'</td>';
    echo '<td>'.$row['nama_lengkap'].'</td>';
    echo '<td>'.$row['no_hp'].'</td>';
    echo '<td>'.$row['jumlah_santri'].'</td>';
    echo '<td>'.$row['santri_yatim'].'</td>';
    echo '<td>'.ucfirst($row['status']).'</td>';
    echo '<td>'.date('d/m/Y H:i', strtotime($row['created_at'])).'</td>';
    echo '</tr>';
}
echo '</table>';
?>