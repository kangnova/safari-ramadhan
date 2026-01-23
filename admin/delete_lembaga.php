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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}