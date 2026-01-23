<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Fungsi resize gambar
function resizeImage($sourcePath, $targetPath, $maxWidth = 200, $maxHeight = 100) {
    // Get image info
    list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth = round($origWidth * $ratio);
    $newHeight = round($origHeight * $ratio);

    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Handle transparency for PNG
    if($type == IMAGETYPE_PNG) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Load source image
    switch($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }

    // Resize
    imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

    // Save image
    switch($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $targetPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $targetPath);
            break;
    }

    // Clean up
    imagedestroy($newImage);
    imagedestroy($source);

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validasi input
        if (empty($_POST['nama_sponsor']) || empty($_POST['status'])) {
            throw new Exception('Nama sponsor dan status harus diisi');
        }

        // Validasi dan proses upload gambar
        if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File gambar wajib diunggah');
        }

        $file = $_FILES['gambar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau GIF');
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid() . '.' . $extension;
        $uploadPath = '../img/sponsor/' . $newFilename;
        $tempPath = $file['tmp_name'];

        // Create directory if it doesn't exist
        if (!is_dir('../img/sponsor')) {
            mkdir('../img/sponsor', 0755, true);
        }

        // Resize and save image
        if (!resizeImage($tempPath, $uploadPath, 200, 100)) {
            throw new Exception('Gagal memproses gambar');
        }

        // Get max urutan
        $stmt = $conn->query("SELECT MAX(urutan) as max_urutan FROM sponsor");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextUrutan = ($result['max_urutan'] ?? 0) + 1;

        // Insert into database
        $query = "INSERT INTO sponsor (nama_sponsor, url, gambar, status, urutan, tgl_update) 
                 VALUES (:nama, :url, :gambar, :status, :urutan, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $_POST['nama_sponsor'],
            ':url' => $_POST['url'] ?? null,
            ':gambar' => $newFilename,
            ':status' => $_POST['status'],
            ':urutan' => $nextUrutan
        ]);

        $_SESSION['success'] = 'Sponsor berhasil ditambahkan';
        header('Location: sponsor.php');
        exit();

    } catch (Exception $e) {
        // If there was an uploaded file and an error occurred, delete it
        if (isset($uploadPath) && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        $_SESSION['error'] = $e->getMessage();
        header('Location: sponsor.php');
        exit();
    }
} else {
    header('Location: sponsor.php');
    exit();
}