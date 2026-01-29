<?php
// Set headers untuk download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Data Pendaftar Safari Ramadhan.xls"');

// Query data
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
    WHERE YEAR(l.created_at) = YEAR(CURDATE())
    GROUP BY l.id
    ORDER BY l.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output table header
echo '<table border="1">';
echo '<tr>
        <th>No</th>
        <th>Nama Lembaga</th>
        <th>Email</th>
        <th>Alamat</th>
        <th>Kecamatan</th>
        <th>Jumlah Santri</th>
        <th>Hari Aktif</th>
        <th>Jam Aktif</th>
        <th>Penanggung Jawab</th>
        <th>Jabatan</th>
        <th>No WA</th>
        <th>Materi</th>
        <th>Frekuensi</th>
        <th>Duta GNB</th>
        <th>Infaq</th>
        <th>Pengajuan (Minggu Ke)</th>
        <th>Tgl Daftar</th>
    </tr>';

// Output data
foreach($data as $index => $row) {
    echo '<tr>';
    echo '<td>'.($index + 1).'</td>';
    echo '<td>'.$row['nama_lembaga'].'</td>';
    echo '<td>'.$row['email'].'</td>';
    echo '<td>'.$row['alamat'].'</td>';
    echo '<td>'.$row['kecamatan'].'</td>';
    echo '<td>'.$row['jumlah_santri'].'</td>';
    echo '<td>'.$row['hari_aktif'].'</td>';
    echo '<td>'.$row['jam_aktif'].'</td>';
    echo '<td>'.$row['penanggung_jawab'].'</td>';
    echo '<td>'.$row['jabatan'].'</td>';
    echo '<td>'.$row['no_wa'].'</td>';
    echo '<td>'.$row['materi_dipilih'].'</td>';
    echo '<td>'.$row['frekuensi_kunjungan'].'</td>';
    echo '<td>'.($row['duta_gnb'] ? 'Ya' : 'Tidak').'</td>';
    echo '<td>'.($row['kesediaan_infaq'] ? 'Ya' : 'Tidak').'</td>';
    echo '<td>'.$row['manfaat'].'</td>';
    echo '<td>'.date('d/m/Y H:i', strtotime($row['created_at'])).'</td>';
    echo '</tr>';
}

echo '</table>';
?>