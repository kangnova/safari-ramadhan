<?php
require_once 'koneksi.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS pendamping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        foto VARCHAR(255) NULL,
        nama VARCHAR(100) NOT NULL,
        no_hp VARCHAR(20) NOT NULL,
        alamat TEXT NOT NULL,
        status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Table 'pendamping' created successfully.\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
