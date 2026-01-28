<?php
require_once 'koneksi.php';

try {
    // Check if column exists and its type
    $stmt = $conn->query("DESCRIBE persetujuan_lembaga duta_gnb");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($column) {
        if (strpos(strtolower($column['Type']), 'varchar') === false) {
            echo "Modifying column duta_gnb to VARCHAR(20)...\n";
            $sql = "ALTER TABLE persetujuan_lembaga MODIFY duta_gnb VARCHAR(20) NULL";
            $conn->exec($sql);
            echo "Column modified successfully.\n";
        } else {
            echo "Column duta_gnb is already compatible (" . $column['Type'] . ").\n";
        }
    } else {
        echo "Column duta_gnb does not exist, creating it...\n";
        $sql = "ALTER TABLE persetujuan_lembaga ADD COLUMN duta_gnb VARCHAR(20) NULL";
        $conn->exec($sql);
        echo "Column created successfully.\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
