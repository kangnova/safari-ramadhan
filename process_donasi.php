<?php
// File: process_donasi.php
// Menangani pemrosesan form donasi secara terpisah dan aman

// Koneksi database
require_once 'koneksi.php';

session_start();

// Set header JSON
header('Content-Type: application/json');

// Pastikan respons selalu JSON yang valid
ob_start();

// Validasi request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Validasi keamanan gagal. Silakan muat ulang halaman.'
    ]);
    exit;
}

try {
    // Ambil dan sanitasi data
    $nominal = isset($_POST['nominal']) ? str_replace(['Rp', '.', ' '], '', $_POST['nominal']) : 0;
    
    // Validasi nominal minimum
    if ((int)$nominal < 20000) {
        throw new Exception('Nominal donasi minimum adalah Rp20.000');
    }
    
    $nama = isset($_POST['nama']) ? htmlspecialchars(trim($_POST['nama'])) : '';
    if (empty($nama)) {
        throw new Exception('Nama harus diisi');
    }
    
    // Validasi email
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Format email tidak valid');
    }
    
    // Validasi dan format nomor WhatsApp
    $whatsapp = isset($_POST['whatsapp']) ? preg_replace('/[^0-9]/', '', $_POST['whatsapp']) : '';
    if (strlen($whatsapp) < 10 || strlen($whatsapp) > 15) {
        throw new Exception('Nomor WhatsApp tidak valid');
    }
    
    // Awali dengan 62 jika dimulai dengan 0
    if (substr($whatsapp, 0, 1) === '0') {
        $whatsapp = '62' . substr($whatsapp, 1);
    }
    
    $is_anonim = isset($_POST['anonim']) ? 1 : 0;
    
    // Generate token transaksi
    $token = 'INV-TRX-' . date('Ymd') . rand(100000, 999999);
    
    // Status awal
    $status = 'pending';

    // Query insert menggunakan prepared statement dari objek $conn (PDO)
    $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 1; 
    if ($program_id <= 0) $program_id = 1;

    $sql = "INSERT INTO donasi (nama_donatur, nominal, is_anonim, email, whatsapp, token, status, created_at, program_id) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$nama, $nominal, $is_anonim, $email, $whatsapp, $token, $status, $program_id])) {
        // Buat CSRF token baru setelah berhasil untuk mencegah resubmit
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'message' => 'Data berhasil disimpan'
        ]);
    } else {
        throw new Exception('Gagal menyimpan data ke database');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

// Bersihkan output buffer dan pastikan hanya respons JSON yang dikirim
$output = ob_get_clean();

// Periksa jika output sudah berupa JSON valid
if (json_decode($output) === null && json_last_error() !== JSON_ERROR_NONE) {
    // Jika bukan JSON valid, log error dan kirim respons error JSON yang valid
    // error_log("Invalid JSON output in process_donasi.php: " . $output);
    echo json_encode([
        'status' => 'error',
        'message' => 'Terjadi kesalahan pada server saat memproses data.'
    ]);
} else {
    // Kirim output asli jika sudah berupa JSON valid
    echo $output;
}
exit;
?>
