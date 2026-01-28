<?php
session_start();
require_once '../koneksi.php';

// Check auth
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['import_preview'])) {
    try {
        $count = 0;
        $data = $_SESSION['import_preview'];
        
        $query = "INSERT INTO jadwal_safari (lembaga_id, tanggal, jam, pengisi, status) 
                  VALUES (:lembaga_id, :tanggal, :jam, :pengisi, 'pending')";
        $stmt = $conn->prepare($query);

        foreach ($data as $row) {
            // Only insert rows that are not ERRORS
            if ($row['status'] !== 'ERROR') {
                $stmt->execute([
                    ':lembaga_id' => $row['id_lembaga'],
                    ':tanggal' => $row['tanggal_db'],
                    ':jam' => $row['jam'],
                    ':pengisi' => $row['pengisi']
                ]);
                $count++;
            }
        }

        // Clear session data
        unset($_SESSION['import_preview']);

        $_SESSION['success'] = "Berhasil mengimport $count jadwal baru!";
        header('Location: jadwal.php');
        exit();

    } catch(PDOException $e) {
        $_SESSION['error'] = "Terjadi kesalahan saat import: " . $e->getMessage();
        header('Location: import_jadwal.php');
        exit();
    }
} else {
    header('Location: import_jadwal.php');
    exit();
}
?>
