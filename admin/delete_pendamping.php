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
        
        // Ambil info foto lama
        $query = "SELECT foto FROM pendamping WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();
        
        // Hapus file foto jika ada
        if($data['foto']) {
            $file_path = '../' . $data['foto'];
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Hapus data dari database
        $query = "DELETE FROM pendamping WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);
        
        $_SESSION['success'] = "Data pendamping berhasil dihapus!";
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header('Location: pendamping.php');
exit();
?>
