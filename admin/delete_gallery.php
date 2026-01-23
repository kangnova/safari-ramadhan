<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Get gallery data
        $stmt = $conn->prepare("SELECT gambar FROM gallery WHERE id_gallery = ?");
        $stmt->execute([$_GET['id']]);
        $gallery = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gallery) {
            throw new Exception('Gallery tidak ditemukan');
        }

        // Delete the image file
        $imagePath = '../img/gallery/' . $gallery['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM gallery WHERE id_gallery = ?");
        $stmt->execute([$_GET['id']]);

        $_SESSION['success'] = 'Gallery berhasil dihapus';
        header('Location: gallery.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: gallery.php');
        exit();
    }
} else {
    header('Location: gallery.php');
    exit();
}