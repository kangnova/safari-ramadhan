<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        // Hapus file gambar jika diperlukan (Opsional)
        // $stmt = $conn->prepare("SELECT gambar_utama FROM program_donasi WHERE id = ?");
        // $stmt->execute([$id]);
        // $img = $stmt->fetchColumn();
        // if ($img && file_exists('../' . $img)) { unlink('../' . $img); }

        $stmt = $conn->prepare("DELETE FROM program_donasi WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['success'] = "Program donasi berhasil dihapus!";
    } catch (Exception $e) {
        // Cek constraint violation (misal ada donasi yang terlink)
        if ($e->getCode() == '23000') {
            $_SESSION['error'] = "Gagal menghapus: Program ini memiliki data donasi terkait.";
        } else {
            $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

header('Location: program_donasi.php');
exit();
?>
