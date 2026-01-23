<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if (empty($_POST['judul']) || empty($_POST['id_kategori'])) {
            throw new Exception('Semua field harus diisi');
        }

        // Validasi dan proses upload gambar
        if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File gambar wajib diunggah');
        }

        $file = $_FILES['gambar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('Ukuran file terlalu besar. Maksimal 5MB');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid() . '.' . $extension;
        $uploadPath = '../img/gallery/' . $newFilename;

        // Create directory if it doesn't exist
        if (!is_dir('../img/gallery')) {
            mkdir('../img/gallery', 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Gagal mengunggah file');
        }

        // Insert into database
        $query = "INSERT INTO gallery (judul, id_kategori, gambar, tgl_update) 
                 VALUES (:judul, :kategori, :gambar, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':judul' => $_POST['judul'],
            ':kategori' => $_POST['id_kategori'],
            ':gambar' => $newFilename
        ]);

        $_SESSION['success'] = 'Foto berhasil ditambahkan';
        header('Location: gallery.php');
        exit();

    } catch (Exception $e) {
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        $_SESSION['error'] = $e->getMessage();
        header('Location: gallery.php');
        exit();
    }
} else {
    header('Location: gallery.php');
    exit();
}