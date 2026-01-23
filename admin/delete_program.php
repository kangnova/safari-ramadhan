<?php
session_start();
require_once '../koneksi.php';

// Check authentication
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Get program data
        $stmt = $conn->prepare("SELECT gambar FROM program WHERE id_program = ?");
        $stmt->execute([$_GET['id']]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$program) {
            throw new Exception('Program tidak ditemukan');
        }

        // Delete the image file
        $imagePath = '../img/program/' . $program['gambar'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }

        // Delete from database
        $stmt = $conn->prepare("DELETE FROM program WHERE id_program = ?");
        $stmt->execute([$_GET['id']]);

        $_SESSION['success'] = 'Program berhasil dihapus';
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}