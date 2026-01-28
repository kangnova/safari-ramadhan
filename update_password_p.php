<?php
session_start();
require_once 'koneksi.php';

// Check if user is logged in
if (!isset($_SESSION['pengisi_id'])) {
    header("Location: login_p.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $id = $_SESSION['pengisi_id'];

    // 1. Validate Input
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password baru minimal 6 karakter!";
        header("Location: dashboard_p.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Konfirmasi password baru tidak cocok!";
        header("Location: dashboard_p.php");
        exit();
    }

    try {
        // 2. Verify Old Password
        $stmt = $conn->prepare("SELECT password FROM pengisi WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            // 3. Update Password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE pengisi SET password = :password WHERE id = :id");
            $update_stmt->execute(['password' => $hashed_password, 'id' => $id]);

            $_SESSION['success'] = "Password berhasil diubah!";
        } else {
            $_SESSION['error'] = "Password saat ini salah!";
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header("Location: dashboard_p.php");
    exit();
}
?>
