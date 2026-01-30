<?php
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['lembaga_id'])) {
    header("Location: login_p.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_SESSION['lembaga_id'];
    
    // Ambil data dasar saja
    $nama = $_POST['nama_lembaga'];
    $alamat = $_POST['alamat'];
    $pj = $_POST['pj'];
    $no_wa = $_POST['no_wa'];

    // Validasi dasar
    if (empty($nama) || empty($alamat) || empty($pj) || empty($no_wa)) {
        $_SESSION['error'] = "Semua field harus diisi.";
        header("Location: dashboard_l.php");
        exit();
    }

    try {
        // Update hanya data profil dasar
        $stmt = $conn->prepare("UPDATE lembaga SET nama_lembaga = ?, alamat = ?, penanggung_jawab = ?, no_wa = ? WHERE id = ?");
        $stmt->execute([$nama, $alamat, $pj, $no_wa, $id]);

        $_SESSION['lembaga_nama'] = $nama; 
        $_SESSION['success'] = "Profil berhasil diperbarui.";
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui profil: " . $e->getMessage();
    }
}

header("Location: dashboard_l.php");
exit();
?>
