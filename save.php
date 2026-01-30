<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

// Pastikan request adalah AJAX request
if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]));
}

try {
    $conn->beginTransaction();
    
    // Validasi data yang dibutuhkan
    $required_fields = ['email', 'nama_lembaga', 'alamat', 'kecamatan', 'pj', 'no_wa', 'share_loc', 'password', 'confirm_password'];
    foreach($required_fields as $field) {
        if(!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi");
        }
    }
    
    // Validasi Password Match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception("Konfirmasi Password tidak cocok");
    }
    
    if (strlen($_POST['password']) < 6) {
        throw new Exception("Password minimal 6 karakter");
    }
    
    // Cek semua duplikasi dalam satu query (Scope per tahun)
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM lembaga WHERE email = ? AND YEAR(created_at) = YEAR(NOW())) as email_count,
            (SELECT COUNT(*) FROM lembaga WHERE nama_lembaga = ? AND YEAR(created_at) = YEAR(NOW())) as lembaga_count,
            (SELECT COUNT(*) FROM lembaga WHERE alamat = ? AND kecamatan = ? AND YEAR(created_at) = YEAR(NOW())) as alamat_count,
            (SELECT COUNT(*) FROM lembaga WHERE penanggung_jawab = ? AND no_wa = ? AND YEAR(created_at) = YEAR(NOW())) as pj_count
    ");
    
    $stmt->execute([
        $_POST['email'],
        $_POST['nama_lembaga'],
        $_POST['alamat'],
        $_POST['kecamatan'],
        $_POST['pj'],
        $_POST['no_wa']
    ]);
    
    $duplicates = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($duplicates['email_count'] > 0) {
        throw new Exception("Email sudah terdaftar");
    }
    if($duplicates['lembaga_count'] > 0) {
        throw new Exception("Nama lembaga sudah terdaftar");
    }
    if($duplicates['alamat_count'] > 0) {
        throw new Exception("Alamat lembaga sudah terdaftar di kecamatan yang sama");
    }
    if($duplicates['pj_count'] > 0) {
        throw new Exception("Penanggung jawab dengan nomor WA tersebut sudah terdaftar");
    }
    
    // Validasi Password Match
    if ($_POST['password'] !== $_POST['confirm_password']) {
        throw new Exception("Konfirmasi Password tidak cocok");
    }
    
    if (strlen($_POST['password']) < 6) {
        throw new Exception("Password minimal 6 karakter");
    }

    // Ambil field tambahan (handling optional/defaults)
    $pilihan_pekan = isset($_POST['manfaat']) ? $_POST['manfaat'] : ''; // Note: di form name='manfaat'
    $frekuensi = isset($_POST['frekuensi']) ? $_POST['frekuensi'] : 1;
    $infaq = isset($_POST['kesediaan_infaq']) ? $_POST['kesediaan_infaq'] : 'tidak';
    $duta = isset($_POST['duta_gnb']) ? $_POST['duta_gnb'] : 'opsional';
    
    // Hash Password
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert lembaga dengan prepared statement
    // Update Query untuk menyertakan kolom baru
    $sql = "INSERT INTO lembaga (
        email, password, nama_lembaga, alamat, kecamatan, 
        jumlah_santri, jam_aktif, penanggung_jawab, jabatan, 
        no_wa, share_loc, pilihan_pekan, frekuensi, infaq_bersedia, duta_gnb
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if(!$stmt->execute([
        $_POST['email'],
        $password_hash,
        $_POST['nama_lembaga'], 
        $_POST['alamat'],
        $_POST['kecamatan'],
        $_POST['jumlah_santri'],
        $_POST['jam_aktif'],
        $_POST['pj'],
        $_POST['jabatan'],
        $_POST['no_wa'],
        $_POST['share_loc'],
        $pilihan_pekan,
        $frekuensi,
        $infaq,
        $duta
    ])) {
        throw new Exception("Gagal menyimpan data lembaga");
    }
    
    $lembaga_id = $conn->lastInsertId();
    error_log("Lembaga ID: " . $lembaga_id); // Logging ID yang dihasilkan
    
    // Insert hari aktif
    if (!empty($_POST['hari_aktif'])) {
        $hari_aktif = is_array($_POST['hari_aktif']) ? $_POST['hari_aktif'] : [$_POST['hari_aktif']];
        
        $stmt = $conn->prepare("INSERT INTO hari_aktif (lembaga_id, hari) VALUES (?, ?)");
        foreach ($hari_aktif as $hari) {
            if(!empty($hari)) {
                if(!$stmt->execute([$lembaga_id, $hari])) {
                    throw new Exception("Gagal menyimpan hari aktif");
                }
            }
        }
    }
    
    // Insert materi
    if (!empty($_POST['materi'])) {
        $stmt = $conn->prepare("INSERT INTO materi_dipilih (lembaga_id, materi) VALUES (?, ?)");
        $materi_list = is_array($_POST['materi']) ? $_POST['materi'] : [$_POST['materi']];
        
        $materiMap = [
            'berkisah' => 'Berkisah Islami',
            'motivasi' => 'Motivasi & Muhasabah',
            'kajian' => 'Kajian Buka Bersama'
        ];
        
        foreach ($materi_list as $materi) {
            if (isset($materiMap[$materi])) {
                if(!$stmt->execute([$lembaga_id, $materiMap[$materi]])) {
                    throw new Exception("Gagal menyimpan materi");
                }
            }
        }
    }
    
    // Insert persetujuan
    if(isset($_POST['frekuensi'])) {
        $stmt = $conn->prepare("INSERT INTO persetujuan_lembaga 
            (lembaga_id, frekuensi_kunjungan, persetujuan_ketentuan, duta_gnb, kesediaan_infaq, manfaat, pemahaman_kerjasama) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if(!$stmt->execute([
            $lembaga_id,
            $_POST['frekuensi'],
            isset($_POST['persetujuan']) && $_POST['persetujuan'] === 'setuju' ? 1 : 0,
            (!empty($_POST['duta_gnb']) && $_POST['duta_gnb'] !== 'opsional') ? $_POST['duta_gnb'] : null,
            isset($_POST['kesediaan_infaq']) && $_POST['kesediaan_infaq'] === 'ya' ? 1 : 0,
            $_POST['manfaat'] ?? '',
            isset($_POST['pemahaman']) && $_POST['pemahaman'] === 'ya' ? 1 : 0
        ])) {
            throw new Exception("Gagal menyimpan data persetujuan");
        }
    }
    
    $conn->commit();
    error_log("Transaction committed successfully"); // Logging successful commit
    
    echo json_encode([
        'success' => true,
        'message' => 'Data berhasil disimpan',
        'lembaga_id' => $lembaga_id
    ]);
    
} catch (Exception $e) {
    error_log("Error occurred: " . $e->getMessage()); // Logging error
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
?>