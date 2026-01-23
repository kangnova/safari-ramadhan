<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Set header untuk download file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data_pengeluaran_'.date('Y-m-d').'.csv');

// Buat file handle untuk output
$output = fopen('php://output', 'w');

// Set kolom header CSV
fputcsv($output, [
    'ID', 
    'Tanggal', 
    'Jumlah', 
    'Keterangan', 
    'Bukti', 
    'Tanggal Dibuat', 
    'Tanggal Diperbarui'
]);

// Filter tanggal jika ada
$whereClause = '';
$params = [];

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $whereClause = " WHERE tanggal >= ?";
    $params[] = $_GET['start_date'];
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    if (empty($whereClause)) {
        $whereClause = " WHERE tanggal <= ?";
    } else {
        $whereClause .= " AND tanggal <= ?";
    }
    $params[] = $_GET['end_date'];
}

// Pencarian jika ada
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    if (empty($whereClause)) {
        $whereClause = " WHERE keterangan LIKE ?";
    } else {
        $whereClause .= " AND keterangan LIKE ?";
    }
    $params[] = "%$search%";
}

// Query untuk mengambil data pengeluaran
$query = "SELECT * FROM pengeluaran" . $whereClause . " ORDER BY tanggal DESC, id DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);

// Ambil data dan tuliskan ke CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Modifikasi data jika perlu
    $row['jumlah'] = 'Rp ' . number_format($row['jumlah'], 0, ',', '.');
    $row['tanggal'] = date('d/m/Y', strtotime($row['tanggal']));
    
    // Tulis baris ke CSV
    fputcsv($output, [
        $row['id'],
        $row['tanggal'],
        $row['jumlah'],
        $row['keterangan'],
        $row['bukti'] ?? 'Tidak ada',
        $row['created_at'],
        $row['updated_at']
    ]);
}

// Tutup file handle
fclose($output);
exit;