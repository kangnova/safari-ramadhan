<?php
require_once 'koneksi.php';

try {
    // Check if column exists
    $stmt = $conn->query("DESCRIBE pendamping_safari pendamping_id");
    if (!$stmt->fetch()) {
        $sql = "ALTER TABLE pendamping_safari ADD COLUMN pendamping_id INT NULL AFTER jadwal_id";
        $conn->exec($sql);
        echo "Column 'pendamping_id' added successfully.\n";
    } else {
        echo "Column 'pendamping_id' already exists.\n";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
