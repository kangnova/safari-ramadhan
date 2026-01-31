<?php
require_once 'koneksi.php';

try {
    echo "Updating donasi table...<br>";
    
    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM donasi LIKE 'pesan'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add pesan column
        // TYPE TEXT allows for longer messages/prayers
        $sql = "ALTER TABLE donasi ADD COLUMN pesan TEXT NULL AFTER program_id";
        $conn->exec($sql);
        echo "Added column 'pesan' to 'donasi' table.<br>";
    } else {
        echo "Column 'pesan' already exists.<br>";
    }
    
    echo "Schema update completed successfully.";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
