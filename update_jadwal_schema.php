<?php
require_once 'koneksi.php';

try {
    $check = $conn->query("SHOW COLUMNS FROM jadwal_safari LIKE 'bukti_kegiatan'");
    if ($check->rowCount() == 0) {
        $conn->exec("ALTER TABLE jadwal_safari ADD COLUMN bukti_kegiatan VARCHAR(255) DEFAULT NULL AFTER pesan_kesan");
        echo "Berhasil: Kolom 'bukti_kegiatan' ditambahkan.<br>";
    } else {
        echo "Info: Kolom 'bukti_kegiatan' sudah ada.<br>";
    }
    echo "Selesai.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
