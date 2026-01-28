<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $conn->beginTransaction();

        $id = $_POST['id'];

        // Hapus data dari tabel hari_aktif
        $stmt = $conn->prepare("DELETE FROM hari_aktif WHERE lembaga_id = ?");
        $stmt->execute([$id]);

        // Hapus data dari tabel materi_dipilih
        $stmt = $conn->prepare("DELETE FROM materi_dipilih WHERE lembaga_id = ?");
        $stmt->execute([$id]);

        // Hapus data dari tabel persetujuan_lembaga
        $stmt = $conn->prepare("DELETE FROM persetujuan_lembaga WHERE lembaga_id = ?");
        $stmt->execute([$id]);



        // Hapus data dari tabel lembaga
        $stmt = $conn->prepare("DELETE FROM lembaga WHERE id = ?");
        $stmt->execute([$id]);

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $conn->rollBack();
        
        // Cek jika error adalah integrity constraint violation
        if ($e->getCode() == '23000' || strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
             echo json_encode(['success' => false, 'message' => 'Gagal menghapus! Lembaga ini masih memiliki jadwal safari yang terkait. Silakan hapus jadwalnya terlebih dahulu di menu Jadwal.']);
        } else {
             echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}