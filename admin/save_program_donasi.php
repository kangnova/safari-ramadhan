<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Upload Gambar
        $gambar = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newFilename = 'donasi_' . time() . '.' . $ext;
                $targetDir = '../img/donasi/';
                
                // Buat direktori jika belum ada
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetDir . $newFilename)) {
                    $gambar = 'img/donasi/' . $newFilename; // Path relatif untuk disimpan di DB
                } else {
                    throw new Exception("Gagal mengupload gambar.");
                }
            } else {
                throw new Exception("Format gambar tidak diizinkan. Gunakan JPG, PNG, atau GIF.");
            }
        }

        // Prepare Data
        $stmt = $conn->prepare("INSERT INTO program_donasi (judul, deskripsi, target_nominal, gambar_utama, tanggal_mulai, tanggal_selesai, status) VALUES (:judul, :deskripsi, :target, :gambar, :mulai, :selesai, :status)");
        
        $stmt->execute([
            ':judul' => $_POST['judul'],
            ':deskripsi' => $_POST['deskripsi'],
            ':target' => $_POST['target_nominal'],
            ':gambar' => $gambar,
            ':mulai' => $_POST['tanggal_mulai'],
            ':selesai' => $_POST['tanggal_selesai'],
            ':status' => $_POST['status']
        ]);

        $_SESSION['success'] = "Program donasi berhasil ditambahkan!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

header('Location: program_donasi.php');
exit();
?>
