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
        if (empty($_POST['id_sponsor']) || empty($_POST['nama_sponsor']) || empty($_POST['status'])) {
            throw new Exception('Semua field harus diisi');
        }

        // Get current sponsor data
        $stmt = $conn->prepare("SELECT gambar FROM sponsor WHERE id_sponsor = ?");
        $stmt->execute([$_POST['id_sponsor']]);
        $currentSponsor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentSponsor) {
            throw new Exception('Sponsor tidak ditemukan');
        }

        $newFilename = $currentSponsor['gambar']; // Default to current image

        // Process new image if uploaded
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
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

            // Resize and save image
            if (!resizeImage($tempPath, $uploadPath, 200, 100)) {
                throw new Exception('Gagal memproses gambar');
            }

            // Delete old image
            $oldImagePath = '../img/sponsor/' . $currentSponsor['gambar'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Update database
        $query = "UPDATE sponsor 
                 SET nama_sponsor = :nama,
                     url = :url,
                     gambar = :gambar,
                     status = :status,
                     tgl_update = NOW()
                 WHERE id_sponsor = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $_POST['nama_sponsor'],
            ':url' => $_POST['url'] ?? null,
            ':gambar' => $newFilename,
            ':status' => $_POST['status'],
            ':id' => $_POST['id_sponsor']
        ]);

        $_SESSION['success'] = 'Sponsor berhasil diperbarui';
        header('Location: sponsor.php');
        exit();

    } catch (Exception $e) {
        // If there was a new uploaded file and an error occurred, delete it
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