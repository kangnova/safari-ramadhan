<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login (Pengisi atau Lembaga)
if (!isset($_SESSION['pengisi_id']) && !isset($_SESSION['lembaga_id'])) {
    header("Location: login_p.php");
    exit();
}

$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'dashboard_p.php';

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
    
    // Handle File Upload (Multiple)
    $bukti_kegiatan_json = null;
    $uploaded_paths = [];
    
    // Check if files are uploaded
    if (isset($_FILES['bukti_kegiatan']) && count($_FILES['bukti_kegiatan']['name']) > 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $upload_dir = 'uploads/laporan/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Loop through each file
        $total_files = count($_FILES['bukti_kegiatan']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $filename = $_FILES['bukti_kegiatan']['name'][$i];
            $tmp_name = $_FILES['bukti_kegiatan']['tmp_name'][$i];
            $error = $_FILES['bukti_kegiatan']['error'][$i];
            
            // Skip empty or error files
            if ($error != 0 || empty($filename)) continue;

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = 'laporan_' . $jadwal_id . '_' . time() . '_' . $i . '.' . $ext;
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                    $uploaded_paths[] = $upload_dir . $new_filename;
                }
            }
        }
    }

    // If there are new uploads, encode to JSON
    if (!empty($uploaded_paths)) {
        $bukti_kegiatan_json = json_encode($uploaded_paths);
    }

    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update status jadwal
        $sql = "UPDATE jadwal_safari 
            SET status = ?, 
                jam_kedatangan = ?,
                jumlah_santri = ?,
                jumlah_guru = ?,
                pesan_kesan = ?,
                keterangan = ?,
                tgl_laporan = CURRENT_TIMESTAMP";
        
        $params = [
            $status,
            $jam_kedatangan,
            $jumlah_santri,
            $jumlah_guru,
            $pesan_kesan,
            $keterangan
        ];

        // Only update 'bukti_kegiatan' if new files were uploaded
        if ($bukti_kegiatan_json) {
            $sql .= ", bukti_kegiatan = ?";
            $params[] = $bukti_kegiatan_json;
        }

        $sql .= " WHERE id = ?";
        $params[] = $jadwal_id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
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
        
        $_SESSION['success'] = "Laporan berhasil dikirim"; // Consistent session key 'success'
        if(isset($_SESSION['success_message'])) unset($_SESSION['success_message']); // Cleanup legacy

    } catch(PDOException $e) {
        // Rollback transaction jika terjadi error
        $conn->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage(); // Consistent session key 'error'
    }
}

// Redirect kembali ke halaman dashboard yang sesuai
header("Location: " . $redirect_to);
exit();
?>