<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

if(isset($_GET['id'])) {
    try {
        $id = $_GET['id'];
        
        // Cek apakah data ada
        $check = $conn->prepare("SELECT id FROM jadwal_safari WHERE id = ?");
        $check->execute([$id]);
        
        if($check->rowCount() > 0) {
            // Hapus data
            $query = "DELETE FROM jadwal_safari WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Jadwal berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Data tidak ditemukan!";
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    // Redirect kembali ke halaman jadwal atau halaman yang ditentukan
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
    } else {
        header("Location: jadwal.php");
    }
    exit();
} else {
    $_SESSION['error'] = "ID tidak valid!";
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
    } else {
        header("Location: jadwal.php");
    }
    exit();
}
?>