<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['lembaga_id'])) {
    header("Location: login_p.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lembaga_id = $_SESSION['lembaga_id'];
    $subjek = trim($_POST['subjek']);
    $pesan = trim($_POST['pesan']);

    if (empty($subjek) || empty($pesan)) {
        $_SESSION['error'] = "Subjek dan Pesan tidak boleh kosong.";
        header("Location: dashboard_l.php");
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO pesan_kontak (lembaga_id, subjek, pesan) VALUES (?, ?, ?)");
        $stmt->execute([$lembaga_id, $subjek, $pesan]);

        $_SESSION['success'] = "Pesan berhasil dikirim ke Admin.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mengirim pesan: " . $e->getMessage();
    }
}

header("Location: dashboard_l.php");
exit();
?>
