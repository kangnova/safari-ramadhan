<?php
require_once 'koneksi.php';

try {
    // Simulate Insert
    $nama = "Test Pendamping " . time();
    $no_hp = "081234567890";
    $alamat = "Jl. Test No. 123";
    $foto = NULL;

    $stmt = $conn->prepare("INSERT INTO pendamping (nama, no_hp, alamat, foto) VALUES (?, ?, ?, ?)");
    if($stmt->execute([$nama, $no_hp, $alamat, $foto])) {
        echo "Insert SUCCESS. ID: " . $conn->lastInsertId() . "\n";
    } else {
        echo "Insert FAILED.\n";
    }

    // Verify Insert
    $id = $conn->lastInsertId();
    $stmt = $conn->prepare("SELECT * FROM pendamping WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if($result && $result['nama'] === $nama) {
        echo "Verification SUCCESS. Name matches: " . $result['nama'] . "\n";
    } else {
        echo "Verification FAILED.\n";
    }

    // Cleanup
    $conn->exec("DELETE FROM pendamping WHERE id = $id");
    echo "Cleanup SUCCESS.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
