<?php
require_once 'koneksi.php';

try {
    // 1. Modify bukti_kegiatan to TEXT to support JSON
    $conn->exec("ALTER TABLE jadwal_safari MODIFY COLUMN bukti_kegiatan TEXT DEFAULT NULL");
    echo "Berhasil: Kolom 'bukti_kegiatan' diubah menjadi TEXT.<br>";

    // 2. Create pesan_kontak table
    $sql = "CREATE TABLE IF NOT EXISTS pesan_kontak (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lembaga_id INT NOT NULL,
        subjek VARCHAR(255) NOT NULL,
        pesan TEXT NOT NULL,
        status ENUM('unread', 'read') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (lembaga_id) REFERENCES lembaga(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Berhasil: Tabel 'pesan_kontak' dibuat/diperiksa.<br>";

    echo "Selesai.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
