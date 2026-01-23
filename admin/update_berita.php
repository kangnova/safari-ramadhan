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
        if (empty($_POST['id_berita']) || empty($_POST['judul']) || 
            empty($_POST['konten']) || empty($_POST['tgl_posting']) || 
            empty($_POST['status'])) {
            throw new Exception('Semua field harus diisi');
        }

        // Get current berita data
        $stmt = $conn->prepare("SELECT gambar FROM berita WHERE id_berita = ?");
        $stmt->execute([$_POST['id_berita']]);
        $currentBerita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentBerita) {
            throw new Exception('Berita tidak ditemukan');
        }

        // Generate slug dari judul
        function createSlug($string) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
            return $slug;
        }
        $slug = createSlug($_POST['judul']);

        $newFilename = $currentBerita['gambar']; // Default to current image

        // Process new image if uploaded
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
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
            $uploadPath = '../img/berita/' . $newFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Gagal mengunggah file');
            }

            // Delete old image
            $oldImagePath = '../img/berita/' . $currentBerita['gambar'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Update database
        $query = "UPDATE berita 
                 SET judul = :judul,
                     slug = :slug,
                     konten = :konten,
                     gambar = :gambar,
                     tgl_posting = :tgl_posting,
                     status = :status,
                     tgl_update = NOW()
                 WHERE id_berita = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':judul' => $_POST['judul'],
            ':slug' => $slug,
            ':konten' => $_POST['konten'],
            ':gambar' => $newFilename,
            ':tgl_posting' => $_POST['tgl_posting'],
            ':status' => $_POST['status'],
            ':id' => $_POST['id_berita']
        ]);

        $_SESSION['success'] = 'Berita berhasil diperbarui';
        header('Location: berita.php');
        exit();

    } catch (Exception $e) {
        // If there was a new uploaded file and an error occurred, delete it
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