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
        $check = $conn->prepare("SELECT id FROM pengisi WHERE id = ?");
        $check->execute([$id]);
        
        if($check->rowCount() > 0) {
            // Hapus data
            $query = "DELETE FROM pengisi WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Data pengisi berhasil dihapus!";
        } else {
            $_SESSION['error'] = "Data tidak ditemukan!";
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: pengisi.php");
    exit();
} else {
    $_SESSION['error'] = "ID tidak valid!";
    header("Location: pengisi.php");
    exit();
}
?>