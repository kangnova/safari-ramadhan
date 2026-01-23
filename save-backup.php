<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

try {
    $conn->beginTransaction();
    
    // Cek duplikasi email
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if($stmt->fetchColumn() > 0) {
        throw new Exception("Email sudah terdaftar");
    }

    // Cek duplikasi nama lembaga
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE nama_lembaga = ?");
    $stmt->execute([$_POST['nama_lembaga']]);
    if($stmt->fetchColumn() > 0) {
        throw new Exception("Nama lembaga sudah terdaftar");
    }

    // Cek duplikasi alamat
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE alamat = ? AND kecamatan = ?");
    $stmt->execute([$_POST['alamat'], $_POST['kecamatan']]);
    if($stmt->fetchColumn() > 0) {
        throw new Exception("Alamat lembaga sudah terdaftar di kecamatan yang sama");
    }

    // Cek duplikasi penanggung jawab
    $stmt = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE penanggung_jawab = ? AND no_wa = ?");
    $stmt->execute([$_POST['pj'], $_POST['no_wa']]);
    if($stmt->fetchColumn() > 0) {
        throw new Exception("Penanggung jawab dengan nomor WA tersebut sudah terdaftar");
    }
    
    // Insert lembaga
    $stmt = $conn->prepare("INSERT INTO lembaga (email, nama_lembaga, alamat, kecamatan, jumlah_santri, jam_aktif, penanggung_jawab, jabatan, no_wa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $_POST['email'],
        $_POST['nama_lembaga'], 
        $_POST['alamat'],
        $_POST['kecamatan'],
        $_POST['jumlah_santri'],
        $_POST['jam_aktif'],
        $_POST['pj'],
        $_POST['jabatan'],
        $_POST['no_wa']
    ]);
    
    $lembaga_id = $conn->lastInsertId();
    
    // Insert hari aktif
    if (isset($_POST['hari_aktif'])) {
        $hari_aktif = is_array($_POST['hari_aktif']) ? $_POST['hari_aktif'] : [$_POST['hari_aktif']];
        
        $stmt = $conn->prepare("INSERT INTO hari_aktif (lembaga_id, hari) VALUES (?, ?)");
        foreach ($hari_aktif as $hari) {
            $stmt->execute([$lembaga_id, $hari]);
        }
    }
    
    // Insert materi
    if (isset($_POST['materi'])) {
        $stmt = $conn->prepare("INSERT INTO materi_dipilih (lembaga_id, materi) VALUES (?, ?)");
        $materi_list = is_array($_POST['materi']) ? $_POST['materi'] : [$_POST['materi']];
        
        $materiMap = [
            'berkisah' => 'Berkisah Islami',
            'motivasi' => 'Motivasi & Muhasabah',
            'kajian' => 'Kajian Buka Bersama'
        ];
        
        foreach ($materi_list as $materi) {
            if (isset($materiMap[$materi])) {
                $stmt->execute([$lembaga_id, $materiMap[$materi]]);
            }
        }
    }
    
    // Insert persetujuan
    $stmt = $conn->prepare("INSERT INTO persetujuan_lembaga (lembaga_id, frekuensi_kunjungan, persetujuan_ketentuan, duta_gnb, kesediaan_infaq, manfaat, pemahaman_kerjasama) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $lembaga_id,
        $_POST['frekuensi'],
        $_POST['persetujuan'] === 'setuju' ? 1 : 0,
        $_POST['duta_gnb'] === 'bersedia' ? 1 : 0,
        $_POST['kesediaan_infaq'] === 'ya' ? 1 : 0,
        $_POST['manfaat'],
        $_POST['pemahaman'] === 'ya' ? 1 : 0
    ]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil disimpan'
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>