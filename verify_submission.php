<?php
// Simulate POST request to save.php
// We can't directly include save.php because it checks for AJAX headers and might have other dependencies or output restrictions.
// Instead, we will replicate the database insertion logic here to verify it works with the specific values.

require_once 'koneksi.php';

try {
    $conn->beginTransaction();

    // Test Data
    $email = 'test_' . time() . '@example.com';
    $nama_lembaga = 'Test Lembaga ' . time();
    $duta_gnb_value = 'bersedia'; // Testing 'bersedia'

    echo "Testing insertion with duta_gnb = '$duta_gnb_value'...\n";

    // Insert lembaga
    $stmt = $conn->prepare("INSERT INTO lembaga (email, nama_lembaga, alamat, kecamatan, jumlah_santri, jam_aktif, penanggung_jawab, jabatan, no_wa, share_loc) VALUES (?, ?, 'Test Address', 'klaten_tengah', 10, '16:00 - 17:00 WIB', 'Test PJ', 'TAKMIR MASJID', '628123456789', 'http://maps.google.com')");
    $stmt->execute([$email, $nama_lembaga]);
    $lembaga_id = $conn->lastInsertId();

    echo "Lembaga created with ID: $lembaga_id\n";

    // Insert persetujuan with NEW duta_gnb logic
    $stmt = $conn->prepare("INSERT INTO persetujuan_lembaga 
        (lembaga_id, frekuensi_kunjungan, persetujuan_ketentuan, duta_gnb, kesediaan_infaq, manfaat, pemahaman_kerjasama) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Logic from save.php
    $duta_gnb_to_save = (!empty($duta_gnb_value) && $duta_gnb_value !== 'opsional') ? $duta_gnb_value : null;

    $stmt->execute([
        $lembaga_id,
        '1',
        1, 
        $duta_gnb_to_save,
        1,
        'sangat', // enum value
        1
    ]);

    $conn->commit();
    echo "Insertion successful.\n";

    // Verify
    $stmt = $conn->prepare("SELECT duta_gnb FROM persetujuan_lembaga WHERE lembaga_id = ?");
    $stmt->execute([$lembaga_id]);
    $result = $stmt->fetchColumn();

    echo "Stored duta_gnb value: " . var_export($result, true) . "\n";

    if ($result === 'bersedia') {
        echo "VERIFICATION PASSED for 'bersedia'.\n";
    } else {
        echo "VERIFICATION FAILED for 'bersedia'.\n";
    }

    // Clean up
    $conn->exec("DELETE FROM persetujuan_lembaga WHERE lembaga_id = $lembaga_id");
    $conn->exec("DELETE FROM lembaga WHERE id = $lembaga_id");
    echo "Test data cleaned up.\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
