<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
        
        // Cek apakah ada gambar baru
        $updateGambar = false;
        $gambar = '';
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['gambar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newFilename = 'donasi_' . time() . '.' . $ext;
                $targetDir = '../img/donasi/';
                
                // Buat direktori jika belum ada
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetDir . $newFilename)) {
                    $gambar = 'img/donasi/' . $newFilename;
                    $updateGambar = true;
                    
                    // Optional: Hapus gambar lama jika ada
                    // $oldStmt = $conn->query("SELECT gambar_utama FROM program_donasi WHERE id = $id");
                    // $oldImg = $oldStmt->fetchColumn();
                    // if ($oldImg && file_exists('../' . $oldImg)) { unlink('../' . $oldImg); }
                    
                } else {
                    throw new Exception("Gagal mengupload gambar.");
                }
            } else {
                throw new Exception("Format gambar tidak diizinkan.");
            }
        }

        // Build Query
        $sql = "UPDATE program_donasi SET 
                judul = :judul, 
                deskripsi = :deskripsi, 
                target_nominal = :target, 
                tanggal_mulai = :mulai, 
                tanggal_selesai = :selesai, 
                status = :status";
        
        if ($updateGambar) {
            $sql .= ", gambar_utama = :gambar";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        
        $params = [
            ':judul' => $_POST['judul'],
            ':deskripsi' => $_POST['deskripsi'],
            ':target' => $_POST['target_nominal'],
            ':mulai' => $_POST['tanggal_mulai'],
            ':selesai' => $_POST['tanggal_selesai'],
            ':status' => $_POST['status'],
            ':id' => $id
        ];
        
        if ($updateGambar) {
            $params[':gambar'] = $gambar;
        }
        
        $stmt->execute($params);

        $_SESSION['success'] = "Program donasi berhasil diperbarui!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

header('Location: program_donasi.php');
exit();
?>
