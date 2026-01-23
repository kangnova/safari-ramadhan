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
header('Content-Disposition: attachment; filename=data_donasi_'.date('Y-m-d').'.csv');

// Buat file handle untuk output
$output = fopen('php://output', 'w');

// Set kolom header CSV
fputcsv($output, [
    'ID', 
    'Token', 
    'Nama Donatur', 
    'Email', 
    'Whatsapp', 
    'Nominal', 
    'Anonim', 
    'Metode Pembayaran', 
    'Status', 
    'Bukti Transfer', 
    'Tanggal Dibuat', 
    'Tanggal Diperbarui'
]);

// Filter status jika ada
$whereClause = '';
$params = [];

if (isset($_GET['status']) && $_GET['status'] !== '') {
    $whereClause = " WHERE status = ?";
    $params[] = $_GET['status'];
}

// Pencarian jika ada
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = trim($_GET['search']);
    if ($whereClause === '') {
        $whereClause = " WHERE (nama_donatur LIKE ? OR token LIKE ? OR email LIKE ?)";
    } else {
        $whereClause .= " AND (nama_donatur LIKE ? OR token LIKE ? OR email LIKE ?)";
    }
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Query untuk mengambil data donasi
$query = "SELECT * FROM donasi" . $whereClause . " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);

// Ambil data dan tuliskan ke CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Modifikasi data jika perlu
    $row['is_anonim'] = $row['is_anonim'] ? 'Ya' : 'Tidak';
    $row['nominal'] = 'Rp ' . number_format($row['nominal'], 0, ',', '.');
    
    // Tulis baris ke CSV
    fputcsv($output, [
        $row['id'],
        $row['token'],
        $row['nama_donatur'],
        $row['email'],
        $row['whatsapp'],
        $row['nominal'],
        $row['is_anonim'],
        $row['metode_pembayaran'] ?? 'Belum dipilih',
        ucfirst($row['status']),
        $row['bukti_transfer'] ?? 'Tidak ada',
        $row['created_at'],
        $row['updated_at']
    ]);
}

// Tutup file handle
fclose($output);
exit;