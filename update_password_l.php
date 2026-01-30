<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['lembaga_id'])) {
    header("Location: login_p.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['lembaga_id'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($new_pass) || empty($confirm_pass)) {
        $_SESSION['error'] = "Password tidak boleh kosong.";
        header("Location: dashboard_l.php");
        exit();
    }

    if (strlen($new_pass) < 6) {
        $_SESSION['error'] = "Password minimal 6 karakter.";
        header("Location: dashboard_l.php");
        exit();
    }

    if ($new_pass !== $confirm_pass) {
        $_SESSION['error'] = "Konfirmasi password tidak cocok.";
        header("Location: dashboard_l.php");
        exit();
    }

    try {
        $hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE lembaga SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $id]);

        $_SESSION['success'] = "Password berhasil diubah.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal mengubah password: " . $e->getMessage();
    }
}

header("Location: dashboard_l.php");
exit();
?>
