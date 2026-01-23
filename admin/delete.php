<?php
session_start();
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $conn->beginTransaction();
        
        // Hapus data dari tabel terkait
        $conn->exec("DELETE FROM hari_aktif WHERE lembaga_id = $id");
        $conn->exec("DELETE FROM materi_dipilih WHERE lembaga_id = $id");
        $conn->exec("DELETE FROM persetujuan_lembaga WHERE lembaga_id = $id");
        $conn->exec("DELETE FROM lembaga WHERE id = $id");
        
        $conn->commit();
        
        header('Location: pendaftar.php?status=success&message=Data berhasil dihapus');
    } catch(Exception $e) {
        $conn->rollBack();
        header('Location: pendaftar.php?status=error&message=Gagal menghapus data');
    }
}
?>