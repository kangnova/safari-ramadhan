<?php
require_once 'koneksi.php';

try {
    // Cek apakah kolom exists
    $check = $conn->query("SHOW COLUMNS FROM lembaga LIKE 'is_contacted'");
    if ($check->rowCount() == 0) {
        // Tambahkan kolom jika belum ada
        $sql = "ALTER TABLE lembaga ADD COLUMN is_contacted TINYINT(1) DEFAULT 0 AFTER no_wa";
        $conn->exec($sql);
        echo "Berhasil: Kolom 'is_contacted' telah ditambahkan ke tabel lembaga.<br>";
    } else {
        echo "Info: Kolom 'is_contacted' sudah ada.<br>";
    }
    
    echo "Selesai.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
