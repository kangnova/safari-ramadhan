<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Metode request tidak valid'
    ]);
    exit;
}

try {
    // Validasi input
    $required_fields = ['email', 'nama_lengkap', 'no_hp', 'asal_lembaga', 'jumlah_santri'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field $field wajib diisi");
        }
    }

    // Validasi format email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format email tidak valid');
    }

    // Format nomor HP ke 62
    $no_hp = preg_replace('/[^0-9]/', '', $_POST['no_hp']);
    if (substr($no_hp, 0, 1) === '0') {
        $no_hp = '62' . substr($no_hp, 1);
    } elseif (substr($no_hp, 0, 1) === '8') {
        $no_hp = '62' . $no_hp;
    }

    // Validasi format nomor HP (sekarang harus dimulai dengan 62)
    if (!preg_match('/^62[0-9]{8,13}$/', $no_hp)) {
        throw new Exception('Format nomor HP tidak valid (gunakan format: 08xx atau 628xx)');
    }
    
    // Update $_POST value for subsequent use
    $_POST['no_hp'] = $no_hp;

    // Validasi jumlah santri
    if (!is_numeric($_POST['jumlah_santri']) || $_POST['jumlah_santri'] < 1) {
        throw new Exception('Jumlah santri harus berupa angka positif');
    }

    // Cek duplikasi email
    $stmt = $conn->prepare("SELECT id FROM ifthar WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Email sudah terdaftar');
    }

    // Cek duplikasi nomor HP
    $stmt = $conn->prepare("SELECT id FROM ifthar WHERE no_hp = ?");
    $stmt->execute([$no_hp]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Nomor HP sudah terdaftar');
    }

    // Cek duplikasi asal lembaga
    $stmt = $conn->prepare("SELECT id FROM ifthar WHERE asal_lembaga = ?");
    $stmt->execute([$_POST['asal_lembaga']]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Lembaga sudah terdaftar');
    }

    // Siapkan query insert
    $query = "INSERT INTO ifthar (email, nama_lengkap, no_hp, asal_lembaga, jumlah_santri, santri_yatim) 
              VALUES (:email, :nama_lengkap, :no_hp, :asal_lembaga, :jumlah_santri, :santri_yatim)";
    
    // Siapkan statement
    $stmt = $conn->prepare($query);
    
    // Bind parameter
    $params = [
        ':email' => $_POST['email'],
        ':nama_lengkap' => $_POST['nama_lengkap'],
        ':no_hp' => $no_hp,
        ':asal_lembaga' => $_POST['asal_lembaga'],
        ':jumlah_santri' => $_POST['jumlah_santri'],
        ':santri_yatim' => !empty($_POST['santri_yatim']) ? $_POST['santri_yatim'] : null
    ];
    
    // Eksekusi query
    $stmt->execute($params);
    
    // Kirim response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Pendaftaran berhasil disimpan'
    ]);

} catch (PDOException $e) {
    // Handle database error
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server'
    ]);
} catch (Exception $e) {
    // Handle validation error
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}