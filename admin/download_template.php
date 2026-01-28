<?php
session_start();
require_once '../koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Set header for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=template_import_jadwal.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('ID Lembaga (JANGAN DIUBAH)', 'Nama Lembaga (Referensi)', 'Tanggal (DD-MM-YYYY)', 'Jam (HH:MM)', 'Nama Pengisi'));

// Fetch all lembaga
// Fetch all lembaga
try {
    // Check if created_at exists (it does, verified)
    $filter = "WHERE YEAR(created_at) = YEAR(NOW())";
    
    // Optional: Double check just in case, but safe to assume based on verify
    /*
    $check = $conn->query("SHOW COLUMNS FROM lembaga LIKE 'created_at'");
    if ($check->rowCount() == 0) {
        $filter = ""; // Fallback
    }
    */

    $stmt = $conn->query("SELECT id, nama_lembaga, alamat, kecamatan FROM lembaga $filter ORDER BY nama_lembaga ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Pre-fill the row with ID and Name. Other columns empty.
        $label = $row['nama_lembaga'] . ' (' . ucwords(str_replace('_', ' ', $row['kecamatan'])) . ')';
        fputcsv($output, array($row['id'], $label, '', '', ''));
    }

    // Add Separator and Pengisi Reference
    fputcsv($output, array('', '', '', '', ''));
    fputcsv($output, array('', '', '', '', ''));
    fputcsv($output, array('--- DAFTAR REFERENSI PENGISI (COPY & PASTE DI BAWAH INI) ---', '', '', '', ''));
    
    // Fetch Pengisi
    $stmtP = $conn->query("SELECT nama FROM pengisi ORDER BY nama ASC");
    while ($p = $stmtP->fetch(PDO::FETCH_ASSOC)) {
         fputcsv($output, array($p['nama'], '', '', '', ''));
    }

} catch(PDOException $e) {
    // If error, just output nothing or error in CSV
}

fclose($output);
?>
