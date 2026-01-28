<?php
require_once 'koneksi.php';
try {
    $stmt = $conn->query("DESCRIBE persetujuan_lembaga");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
