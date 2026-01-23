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
        // Get berita data
        $stmt = $conn->prepare("SELECT gambar FROM berita WHERE id_berita = ?");
        $stmt->execute([$_GET['id']]);
        $berita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$berita) {
            throw new Exception('Berita tidak ditemukan');
        }

        // Delete the image file
        $imagePath = '../img/berita/' . $berita['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM berita WHERE id_berita = ?");
        $stmt->execute([$_GET['id']]);

        $_SESSION['success'] = 'Berita berhasil dihapus';
        header('Location: berita.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: berita.php');
        exit();
    }
} else {
    header('Location: berita.php');
    exit();
}