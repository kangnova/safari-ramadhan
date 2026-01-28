<?php
require_once 'koneksi.php';

try {
    echo "Updating target_donasi table...<br>";
    
    // Check if column exists
    $stmt = $conn->query("SHOW COLUMNS FROM target_donasi LIKE 'program_id'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        // Add program_id column
        $sql = "ALTER TABLE target_donasi ADD COLUMN program_id INT NULL AFTER id";
        $conn->exec($sql);
        echo "Added column 'program_id' to 'target_donasi'.<br>";
        
        // Add foreign key constraint if desired (optional but good practice)
        // Leaving FK optional to avoid strict constraint issues if data is messy, 
        // but adding an index is good.
        $conn->exec("CREATE INDEX idx_program_id ON target_donasi(program_id)");
        echo "Created index on 'program_id'.<br>";
        
    } else {
        echo "Column 'program_id' already exists.<br>";
    }
    
    echo "Schema update completed successfully.";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
