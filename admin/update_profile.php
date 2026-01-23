<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input wajib
        if (empty($_POST['judul']) || empty($_POST['deskripsi']) || empty($_POST['alamat'])) {
            throw new Exception('Judul, deskripsi, dan alamat harus diisi');
        }

        // Validasi format email
        if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid');
        }

        // Validasi format WhatsApp (harus dimulai dengan 628)
        if (!empty($_POST['whatsapp']) && !preg_match('/^628\d{8,12}$/', $_POST['whatsapp'])) {
            throw new Exception('Format nomor WhatsApp tidak valid (harus dimulai dengan 628)');
        }

        // Cek apakah sudah ada data profil
        $stmt = $conn->query("SELECT COUNT(*) FROM profil");
        $count = $stmt->fetchColumn();

        // Fungsi untuk mendapatkan ID video YouTube
        function getYoutubeVideoId($url) {
            $url = trim($url);
            if (strlen($url) === 11) return $url;
            
            $pattern = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/';
            preg_match($pattern, $url, $matches);
            return (isset($matches[2]) && strlen($matches[2]) === 11) ? $matches[2] : null;
        }

        // Validasi dan proses URL video YouTube
        $videoId = getYoutubeVideoId($_POST['video_url']);
        if (!$videoId) {
            throw new Exception('URL YouTube tidak valid');
        }

        if ($count > 0) {
            // Update data yang ada
            $query = "UPDATE profil 
                     SET judul = :judul,
                         video_url = :video_url,
                         deskripsi = :deskripsi,
                         alamat = :alamat,
                         whatsapp = :whatsapp,
                         email = :email,
                         facebook = :facebook,
                         instagram = :instagram,
                         twitter = :twitter,
                         youtube = :youtube,
                         tiktok = :tiktok,
                         tgl_update = NOW()
                     WHERE id_profil = 1";
        } else {
            // Insert data baru
            $query = "INSERT INTO profil (judul, video_url, deskripsi, alamat, whatsapp, email, 
                                        facebook, instagram, twitter, youtube, tiktok, tgl_update) 
                     VALUES (:judul, :video_url, :deskripsi, :alamat, :whatsapp, :email,
                             :facebook, :instagram, :twitter, :youtube, :tiktok, NOW())";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':judul' => $_POST['judul'],
            ':video_url' => $videoId,
            ':deskripsi' => $_POST['deskripsi'],
            ':alamat' => $_POST['alamat'],
            ':whatsapp' => $_POST['whatsapp'],
            ':email' => $_POST['email'],
            ':facebook' => $_POST['facebook'],
            ':instagram' => $_POST['instagram'],
            ':twitter' => $_POST['twitter'],
            ':youtube' => $_POST['youtube'],
            ':tiktok' => $_POST['tiktok']
        ]);

        $_SESSION['success'] = 'Profil berhasil diperbarui';
        header('Location: profile.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: profile.php');
        exit();
    }
} else {
    header('Location: profile.php');
    exit();
}