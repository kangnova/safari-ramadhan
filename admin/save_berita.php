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
        if (empty($_POST['judul']) || empty($_POST['konten']) || empty($_POST['tgl_posting']) || empty($_POST['status'])) {
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

        // Generate slug dari judul
        function createSlug($string) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
            return $slug;
        }
        $slug = createSlug($_POST['judul']);

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid() . '.' . $extension;
        $uploadPath = '../img/berita/' . $newFilename;

        // Create directory if it doesn't exist
        if (!is_dir('../img/berita')) {
            mkdir('../img/berita', 0755, true);
        }

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Gagal mengunggah file');
        }

        // Insert into database
        $query = "INSERT INTO berita (judul, slug, konten, gambar, tgl_posting, status, tgl_update) 
                 VALUES (:judul, :slug, :konten, :gambar, :tgl_posting, :status, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':judul' => $_POST['judul'],
            ':slug' => $slug,
            ':konten' => $_POST['konten'],
            ':gambar' => $newFilename,
            ':tgl_posting' => $_POST['tgl_posting'],
            ':status' => $_POST['status']
        ]);

        $_SESSION['success'] = 'Berita berhasil ditambahkan';
        header('Location: berita.php');
        exit();

    } catch (Exception $e) {
        // If there was an uploaded file and an error occurred, delete it
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        $_SESSION['error'] = $e->getMessage();
        header('Location: berita.php');
        exit();
    }
} else {
    header('Location: berita.php');
    exit();
}