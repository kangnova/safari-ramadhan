<?php
session_start();
require_once '../koneksi.php';

// Check authentication
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($_POST['nama_program']) || empty($_POST['deskripsi']) || empty($_POST['manfaat_kegiatan'])) {
            throw new Exception('Semua field harus diisi');
        }

        // Validate and process image upload
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
        $uploadPath = '../img/program/' . $newFilename;

        // Create directory if it doesn't exist
        if (!is_dir('../img/program')) {
            mkdir('../img/program', 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Gagal mengunggah file');
        }

        // Insert into database
        $query = "INSERT INTO program (nama_program, deskripsi, manfaat_kegiatan, gambar, urutan, status, tgl_update) 
                 VALUES (:nama, :deskripsi, :manfaat, :gambar, :urutan, :status, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $_POST['nama_program'],
            ':deskripsi' => $_POST['deskripsi'],
            ':manfaat' => $_POST['manfaat_kegiatan'],
            ':gambar' => $newFilename,
            ':urutan' => $_POST['urutan'] ?? 0,
            ':status' => $_POST['status'] ?? 'published'
        ]);

        $_SESSION['success'] = 'Program berhasil ditambahkan';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        // If there was an uploaded file and an error occurred, delete it
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}