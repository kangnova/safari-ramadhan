<?php
require_once 'koneksi.php';

try {
    echo "Checking logo_bank table schema...<br>";
    
    // Check existing columns
    $stmt = $conn->query("SHOW COLUMNS FROM logo_bank");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Add nomor_rekening if not exists
    if (!in_array('nomor_rekening', $columns)) {
        $conn->exec("ALTER TABLE logo_bank ADD COLUMN nomor_rekening VARCHAR(50) AFTER nama_bank");
        echo "Added column 'nomor_rekening'<br>";
    }
    
    // Add atas_nama if not exists
    if (!in_array('atas_nama', $columns)) {
        $conn->exec("ALTER TABLE logo_bank ADD COLUMN atas_nama VARCHAR(100) AFTER nomor_rekening");
        echo "Added column 'atas_nama'<br>";
    }
    
    // Add kategori if not exists
    if (!in_array('kategori', $columns)) {
        $conn->exec("ALTER TABLE logo_bank ADD COLUMN kategori ENUM('bank', 'ewallet', 'qris') DEFAULT 'bank' AFTER atas_nama");
        echo "Added column 'kategori'<br>";
    }
    
    // Add urutan if not exists
    if (!in_array('urutan', $columns)) {
        $conn->exec("ALTER TABLE logo_bank ADD COLUMN urutan INT DEFAULT 0 AFTER kategori");
        echo "Added column 'urutan'<br>";
    }
    
    // Add is_active if not exists
    if (!in_array('is_active', $columns)) {
        $conn->exec("ALTER TABLE logo_bank ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER urutan");
        echo "Added column 'is_active'<br>";
    }
    
    echo "Schema update completed successfully!";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
