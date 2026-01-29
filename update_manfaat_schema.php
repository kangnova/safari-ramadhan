<?php
require_once 'koneksi.php';

try {
    // Ubah kolom manfaat dari ENUM menjadi VARCHAR agar bisa menerima "Pekan 1", "Pekan 2", dst.
    $sql = "ALTER TABLE persetujuan_lembaga MODIFY COLUMN manfaat VARCHAR(50) NULL";
    $conn->exec($sql);
    echo "Berhasil: Kolom 'manfaat' telah diubah menjadi VARCHAR(50).<br>";
    echo "Sekarang form bisa menyimpan pilihan Pekan 1, Pekan 2, dst.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
