<?php
require_once 'koneksi.php';

try {
    echo "<h2>Menambahkan Kolom ke Tabel Lembaga...</h2>";

    // 1. Ubah kolom jabatan jadi VARCHAR agar fleksibel (menghindari error enum mismatched)
    $conn->exec("ALTER TABLE lembaga MODIFY COLUMN jabatan VARCHAR(100)");
    echo "✅ Kolom 'jabatan' diubah menjadi VARCHAR(100)<br>";

    // 2. Tambah kolom Pilihan Pekan
    try {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN pilihan_pekan VARCHAR(50) AFTER jam_aktif");
        echo "✅ Kolom 'pilihan_pekan' berhasil ditambahkan<br>";
    } catch (PDOException $e) {
        echo "⚠️ Kolom 'pilihan_pekan' mungkin sudah ada: " . $e->getMessage() . "<br>";
    }

    // 3. Tambah kolom Frekuensi
    try {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN frekuensi INT(1) DEFAULT 1 AFTER pilihan_pekan");
        echo "✅ Kolom 'frekuensi' berhasil ditambahkan<br>";
    } catch (PDOException $e) {
        echo "⚠️ Kolom 'frekuensi' mungkin sudah ada<br>";
    }

    // 4. Tambah kolom Infaq Bersedia
    try {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN infaq_bersedia ENUM('ya', 'tidak') DEFAULT 'tidak' AFTER frekuensi");
        echo "✅ Kolom 'infaq_bersedia' berhasil ditambahkan<br>";
    } catch (PDOException $e) {
        echo "⚠️ Kolom 'infaq_bersedia' mungkin sudah ada<br>";
    }

    // 5. Tambah kolom Duta GNB
    try {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN duta_gnb VARCHAR(20) DEFAULT 'opsional' AFTER infaq_bersedia");
        echo "✅ Kolom 'duta_gnb' berhasil ditambahkan<br>";
    } catch (PDOException $e) {
        echo "⚠️ Kolom 'duta_gnb' mungkin sudah ada<br>";
    }

    echo "<hr><h3>Selesai. Silakan cek kembali debug_lembaga.php</h3>";

} catch(PDOException $e) {
    echo "<h1>Error Fatal:</h1> " . $e->getMessage();
}
?>
