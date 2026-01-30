<?php
require_once 'koneksi.php';

function executeQuery($conn, $sql, $message) {
    try {
        $conn->exec($sql);
        echo "<div style='color:green'>[BERHASIL] $message</div><br>";
    } catch (PDOException $e) {
        // Ignore "Table already exists" or "Duplicate column" errors
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column') !== false) {
             echo "<div style='color:orange'>[INFO] $message (Sudah ada)</div><br>";
        } else {
             echo "<div style='color:red'>[ERROR] $message: " . $e->getMessage() . "</div><br>";
        }
    }
}

echo "<h2>Setup Database Donasi</h2>";

// 1. Table program_donasi
$sql = "CREATE TABLE IF NOT EXISTS program_donasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    target_nominal DECIMAL(15,2) DEFAULT 0,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    gambar_utama VARCHAR(255),
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
executeQuery($conn, $sql, "Membuat tabel program_donasi");

// 2. Table target_donasi
$sql = "CREATE TABLE IF NOT EXISTS target_donasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT,
    is_active TINYINT(1) DEFAULT 1,
    jumlah DECIMAL(15,2) DEFAULT 0,
    deskripsi TEXT,
    tanggal_mulai DATETIME,
    tanggal_selesai DATETIME,
    FOREIGN KEY (program_id) REFERENCES program_donasi(id) ON DELETE SET NULL
)";
executeQuery($conn, $sql, "Membuat tabel target_donasi");

// 3. Table logo_bank (Just in case)
$sql = "CREATE TABLE IF NOT EXISTS logo_bank (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_bank VARCHAR(100),
    nomor_rekening VARCHAR(50),
    atas_nama VARCHAR(100),
    logo_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1
)";
executeQuery($conn, $sql, "Membuat tabel logo_bank");

// 4. Update Table donasi (Add program_id, whatsapp, email if missing)
// Check if donasi table exists first
try {
    $conn->query("SELECT 1 FROM donasi LIMIT 1");
    // Table exists, alter it
    $sql = "ALTER TABLE donasi ADD COLUMN IF NOT EXISTS program_id INT AFTER id";
    executeQuery($conn, $sql, "Menambah kolom program_id ke tabel donasi");
    
    $sql = "ALTER TABLE donasi ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) AFTER email";
    executeQuery($conn, $sql, "Menambah kolom whatsapp ke tabel donasi");

    $sql = "ALTER TABLE donasi ADD COLUMN IF NOT EXISTS token VARCHAR(50) AFTER whatsapp";
    executeQuery($conn, $sql, "Menambah kolom token ke tabel donasi");

} catch (PDOException $e) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE IF NOT EXISTS donasi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT,
        nama_donatur VARCHAR(100),
        email VARCHAR(100),
        whatsapp VARCHAR(20),
        nominal DECIMAL(15,2),
        is_anonim TINYINT(1) DEFAULT 0,
        token VARCHAR(50),
        status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
        bukti_bayar VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($conn, $sql, "Membuat tabel donasi baru");
}

echo "<hr><h3>Selesai! Silakan cek kembali halaman donasi.php</h3>";
?>
