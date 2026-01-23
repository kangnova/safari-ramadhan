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
        // Get sponsor data
        $stmt = $conn->prepare("SELECT gambar FROM sponsor WHERE id_sponsor = ?");
        $stmt->execute([$_GET['id']]);
        $sponsor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sponsor) {
            throw new Exception('Sponsor tidak ditemukan');
        }

        // Delete the image file
        $imagePath = '../img/sponsor/' . $sponsor['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM sponsor WHERE id_sponsor = ?");
        $stmt->execute([$_GET['id']]);

        // Reorder remaining sponsors
        $stmt = $conn->query("SET @count = 0");
        $stmt = $conn->query("UPDATE sponsor SET urutan = @count:= @count + 1 ORDER BY urutan");

        $_SESSION['success'] = 'Sponsor berhasil dihapus';
        header('Location: sponsor.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: sponsor.php');
        exit();
    }
} else {
    header('Location: sponsor.php');
    exit();
}