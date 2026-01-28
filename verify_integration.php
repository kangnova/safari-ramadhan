<?php
require_once 'koneksi.php';

try {
    $conn->beginTransaction();

    // 1. Create Dummy Pendamping
    $stmt = $conn->prepare("INSERT INTO pendamping (nama, no_hp, alamat) VALUES ('Test Pendamping Integration', '08123', 'Test Addr')");
    $stmt->execute();
    $pendamping_id = $conn->lastInsertId();
    echo "Created Dummy Pendamping ID: $pendamping_id\n";

    // 2. Create Dummy Lembaga (required for jadwal)
    $stmt = $conn->prepare("INSERT INTO lembaga (nama_lembaga, email, alamat, kecamatan, jumlah_santri, jam_aktif, penanggung_jawab, jabatan, no_wa, share_loc) VALUES ('Test Lembaga Integration', 'test@int.com', 'Addr', 'klaten', 10, '16:00', 'PJ', 'Takmir', '081', 'loc')");
    $stmt->execute();
    $lembaga_id = $conn->lastInsertId();
    echo "Created Dummy Lembaga ID: $lembaga_id\n";

    // 3. Simulate Jadwal Insertion (Logic from jadwal.php)
    $tanggal = date('Y-m-d', strtotime('+1 day'));
    $jam = '16:30';
    $pengisi = 'Test Pengisi';
    
    // Insert Jadwal
    $query = "INSERT INTO jadwal_safari (tanggal, jam, lembaga_id, pengisi, status) 
              VALUES (:tanggal, :jam, :lembaga_id, :pengisi, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':tanggal' => $tanggal,
        ':jam' => $jam,
        ':lembaga_id' => $lembaga_id,
        ':pengisi' => $pengisi
    ]);
    $jadwal_id = $conn->lastInsertId();
    echo "Created Jadwal ID: $jadwal_id\n";

    // 4. Simulate Pendamping Insertion (Logic from jadwal.php)
    // Get Pendamping Info
    $stmt_p = $conn->prepare("SELECT nama, id FROM pendamping WHERE id = ?");
    $stmt_p->execute([$pendamping_id]);
    $p_data = $stmt_p->fetch(PDO::FETCH_ASSOC);
    
    if($p_data) {
        $query_pendamping = "INSERT INTO pendamping_safari (jadwal_id, pendamping_id, nama_pendamping) VALUES (:jadwal_id, :pendamping_id, :nama_pendamping)";
        $stmt_pendamping = $conn->prepare($query_pendamping);
        $stmt_pendamping->execute([
            ':jadwal_id' => $jadwal_id,
            ':pendamping_id' => $p_data['id'],
            ':nama_pendamping' => $p_data['nama']
        ]);
        echo "Inserted Pendamping into pendamping_safari.\n";
    } else {
        echo "Failed to fetch pendamping data.\n";
    }

    // 5. Verify
    $stmt_v = $conn->prepare("SELECT * FROM pendamping_safari WHERE jadwal_id = ?");
    $stmt_v->execute([$jadwal_id]);
    $result = $stmt_v->fetch(PDO::FETCH_ASSOC);

    echo "Verification Result:\n";
    var_dump($result);

    if($result && $result['pendamping_id'] == $pendamping_id && $result['nama_pendamping'] == 'Test Pendamping Integration') {
        echo "Integration Verification PASSED.\n";
    } else {
        echo "Integration Verification FAILED.\n";
    }
    
    $conn->rollBack(); // Always rollback test data
    echo "Rollback successful.\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
