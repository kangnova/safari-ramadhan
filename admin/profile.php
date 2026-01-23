<?php
session_start();
require_once '../koneksi.php';

// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Ambil data profil
try {
    $query = "SELECT * FROM profil ORDER BY tgl_update DESC LIMIT 1";
    $stmt = $conn->query($query);
    $profil = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kesalahan database: " . $e->getMessage();
}

// Fungsi untuk mendapatkan ID video YouTube
function getYoutubeVideoId($url) {
    $url = trim($url);
    if (strlen($url) === 11) return $url;
    
    $pattern = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/';
    preg_match($pattern, $url, $matches);
    return (isset($matches[2]) && strlen($matches[2]) === 11) ? $matches[2] : null;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Profil - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Kelola Profil Safari Ramadhan</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?= $_SESSION['success'] ?>
                                <?php unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION['error'] ?>
                                <?php unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>

                        <form action="update_profile.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Judul</label>
                                <input type="text" class="form-control" name="judul" 
                                       value="<?= htmlspecialchars($profil['judul'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">URL Video YouTube</label>
                                <input type="text" class="form-control" name="video_url" 
                                       value="<?= htmlspecialchars($profil['video_url'] ?? '') ?>" required
                                       placeholder="Contoh: https://www.youtube.com/watch?v=VIDEO_ID">
                                <div class="form-text">
                                    Masukkan URL lengkap video YouTube atau kode video saja
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="4" required><?= htmlspecialchars($profil['deskripsi'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control" name="alamat" rows="3" required><?= htmlspecialchars($profil['alamat'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" name="whatsapp" 
                                       value="<?= htmlspecialchars($profil['whatsapp'] ?? '') ?>" 
                                       placeholder="628xxxxxxxxxx">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" 
                                       value="<?= htmlspecialchars($profil['email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Facebook</label>
                                <input type="text" class="form-control" name="facebook" 
                                       value="<?= htmlspecialchars($profil['facebook'] ?? '') ?>"
                                       placeholder="Username atau URL Facebook">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Instagram</label>
                                <input type="text" class="form-control" name="instagram" 
                                       value="<?= htmlspecialchars($profil['instagram'] ?? '') ?>"
                                       placeholder="Username Instagram tanpa @">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Twitter/X</label>
                                <input type="text" class="form-control" name="twitter" 
                                       value="<?= htmlspecialchars($profil['twitter'] ?? '') ?>"
                                       placeholder="Username Twitter tanpa @">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">YouTube</label>
                                <input type="text" class="form-control" name="youtube" 
                                       value="<?= htmlspecialchars($profil['youtube'] ?? '') ?>"
                                       placeholder="URL channel YouTube">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">TikTok</label>
                                <input type="text" class="form-control" name="tiktok" 
                                       value="<?= htmlspecialchars($profil['tiktok'] ?? '') ?>"
                                       placeholder="Username TikTok tanpa @">
                            </div>

                            <div class="preview mb-4">
                                <label class="form-label">Pratinjau Video:</label>
                                <?php if (!empty($profil['video_url'])): ?>
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/<?= getYoutubeVideoId($profil['video_url']) ?>" 
                                                allowfullscreen></iframe>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        Video akan ditampilkan di sini setelah URL diisi
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to extract YouTube video ID
        function getYoutubeVideoId(url) {
            url = url.trim();
            if (url.length === 11) return url;
            
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        // Update preview when URL changes
        document.querySelector('input[name="video_url"]').addEventListener('change', function() {
            const videoId = getYoutubeVideoId(this.value);
            const previewDiv = document.querySelector('.preview');
            
            if (videoId) {
                previewDiv.innerHTML = `
                    <label class="form-label">Pratinjau Video:</label>
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/${videoId}" allowfullscreen></iframe>
                    </div>`;
            } else {
                previewDiv.innerHTML = `
                    <label class="form-label">Pratinjau Video:</label>
                    <div class="alert alert-warning">
                        URL video tidak valid. Pastikan URL benar.
                    </div>`;
            }
        });
    </script>
</body>
</html>