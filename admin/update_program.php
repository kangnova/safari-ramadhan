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
        if (empty($_POST['id_program']) || empty($_POST['nama_program']) || 
            empty($_POST['deskripsi']) || empty($_POST['manfaat_kegiatan'])) {
            throw new Exception('Semua field harus diisi');
        }

        // Get current program data
        $stmt = $conn->prepare("SELECT gambar FROM program WHERE id_program = ?");
        $stmt->execute([$_POST['id_program']]);
        $currentProgram = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentProgram) {
            throw new Exception('Program tidak ditemukan');
        }

        $newFilename = $currentProgram['gambar']; // Default to current image

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
            $uploadPath = '../img/program/' . $newFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Gagal mengunggah file');
            }

            // Delete old image
            $oldImagePath = '../img/program/' . $currentProgram['gambar'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Update database
        $query = "UPDATE program 
                 SET nama_program = :nama,
                     deskripsi = :deskripsi,
                     manfaat_kegiatan = :manfaat,
                     gambar = :gambar,
                     tgl_update = NOW()
                 WHERE id_program = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $_POST['nama_program'],
            ':deskripsi' => $_POST['deskripsi'],
            ':manfaat' => $_POST['manfaat_kegiatan'],
            ':gambar' => $newFilename,
            ':id' => $_POST['id_program']
        ]);

        $_SESSION['success'] = 'Program berhasil diperbarui';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        // If there was a new uploaded file and an error occurred, delete it
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