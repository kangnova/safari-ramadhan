<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['pengisi_id'])) {
    header("Location: login_p.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jadwal_id = $_POST['jadwal_id'];
    $status = $_POST['status'];
    $jam_kedatangan = $_POST['jam_kedatangan'];
    $jumlah_santri = $_POST['jumlah_santri'];
    $jumlah_guru = $_POST['jumlah_guru'];
    $pesan_kesan = $_POST['pesan_kesan'];
    $keterangan = $_POST['keterangan'];
    
    // Ambil data pendamping dari array
    $pendampingList = isset($_POST['nama_pendamping']) ? $_POST['nama_pendamping'] : [];
    
    try {
        // Begin transaction untuk memastikan semua operasi berhasil atau tidak sama sekali
        $conn->beginTransaction();
        
        // Update status jadwal dengan data baru
        $stmt = $conn->prepare("
            UPDATE jadwal_safari 
            SET status = ?, 
                jam_kedatangan = ?,
                jumlah_santri = ?,
                jumlah_guru = ?,
                pesan_kesan = ?,
                keterangan = ?,
                tgl_laporan = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([
            $status,
            $jam_kedatangan,
            $jumlah_santri,
            $jumlah_guru,
            $pesan_kesan,
            $keterangan,
            $jadwal_id
        ]);
        
        // Hapus data pendamping lama jika ada (untuk kasus update)
        $deleteStmt = $conn->prepare("DELETE FROM pendamping_safari WHERE jadwal_id = ?");
        $deleteStmt->execute([$jadwal_id]);
        
        // Simpan data pendamping baru
        if (!empty($pendampingList)) {
            $insertStmt = $conn->prepare("
                INSERT INTO pendamping_safari (jadwal_id, nama_pendamping, created_at) 
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            
            foreach ($pendampingList as $pendamping) {
                // Lewati jika nama pendamping kosong
                if (trim($pendamping) === '') continue;
                
                $insertStmt->execute([
                    $jadwal_id, 
                    trim($pendamping)
                ]);
            }
        }
        
        // Commit transaction jika semua operasi berhasil
        $conn->commit();
        
        $_SESSION['success_message'] = "Laporan berhasil dikirim";
    } catch(PDOException $e) {
        // Rollback transaction jika terjadi error
        $conn->rollBack();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Redirect kembali ke halaman dashboard
header("Location: dashboard_p.php");
exit();
?>