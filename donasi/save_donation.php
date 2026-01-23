<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ambil data dari form
        $nominal = str_replace('.', '', $_POST['nominal']);
        $nama = $_POST['nama'];
        $is_anonymous = isset($_POST['anonymous']) ? 1 : 0;
        $whatsapp = $_POST['whatsapp'];
        $email = $_POST['email'];
        $status = 'pending';
        
        // Generate payment token
        $token = 'INV-TRX-' . date('Ymd') . rand(100000, 999999);

        // Simpan ke database
        $query = "INSERT INTO donasi (nominal, nama_donatur, is_anonymous, whatsapp, email, status, payment_token, created_at) 
                  VALUES (:nominal, :nama, :is_anonymous, :whatsapp, :email, :status, :token, NOW())";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'nominal' => $nominal,
            'nama' => $nama,
            'is_anonymous' => $is_anonymous,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'status' => $status,
            'token' => $token
        ]);

        // Return success response
        echo json_encode([
            'status' => 'success',
            'message' => 'Data berhasil disimpan',
            'token' => $token
        ]);

    } catch (PDOException $e) {
        // Return error response
        echo json_encode([
            'status' => 'error',
            'message' => 'Gagal menyimpan data: ' . $e->getMessage()
        ]);
    }
}
?>