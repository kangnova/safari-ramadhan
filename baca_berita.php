<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

// Ambil slug dari URL
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Ambil data berita
try {
    $query = "SELECT * FROM berita WHERE slug = :slug AND status = 'published'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':slug' => $slug]);
    $berita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$berita) {
        header('Location: index.php');
        exit();
    }

    // Increment jumlah pembaca
    $updateQuery = "UPDATE berita SET dibaca = COALESCE(dibaca, 0) + 1 WHERE id_berita = :id_berita";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([':id_berita' => $berita['id_berita']]);

    // Update data berita yang ditampilkan (agar jumlah pembaca real-time)
    $berita['dibaca'] = isset($berita['dibaca']) ? $berita['dibaca'] + 1 : 1;

    // Ambil berita terkait
    $query = "SELECT * FROM berita 
              WHERE status = 'published' 
              AND id_berita != :id_berita 
              ORDER BY tgl_posting DESC LIMIT 3";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id_berita' => $berita['id_berita']]);
    $beritaTerkait = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($berita['judul']) ?> - Safari Ramadhan</title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($berita['konten']), 0, 160)) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($berita['judul']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr(strip_tags($berita['konten']), 0, 160)) ?>">
    <meta property="og:image" content="https://gnb.or.id/safariramadhan/img/berita/<?= htmlspecialchars($berita['gambar']) ?>">
    <meta property="og:url" content="https://gnb.or.id/safariramadhan/baca_berita.php?slug=<?= $berita['slug'] ?>">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding-top: 80px; /* Adjusted for fixed navbar */
            background-color: #f8f9fa;
        }
        
        /* Remove conflicting .navbar styles */

        .article-header {
            background-color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
            margin-top: 20px;
        }

        .article-header {
            background-color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }

        .article-image {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .article-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .article-content {
            line-height: 1.8;
            font-size: 1.1rem;
            color: #343a40;
        }

        .related-articles {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,.1);
        }

        .related-article-card {
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .related-article-card:hover {
            transform: translateY(-5px);
        }

        .related-article-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .share-buttons {
            margin: 2rem 0;
        }

        .share-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            margin-right: 0.5rem;
            transition: opacity 0.3s ease;
        }

        .share-button:hover {
            opacity: 0.9;
            color: white;
        }

        .share-button i {
            margin-right: 0.5rem;
        }

        .facebook { background-color: #3b5998; }
        .twitter { background-color: #1da1f2; }
        .whatsapp { background-color: #25d366; }

        @media (max-width: 768px) {
            .article-header {
                padding: 1rem 0;
            }

            .article-image {
                margin: -1rem -1rem 1rem -1rem;
                border-radius: 0;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="article-header">
        <div class="container">
            <h1 class="mb-3"><?= htmlspecialchars($berita['judul']) ?></h1>
            <div class="article-meta">
                <i class="bi bi-calendar3"></i> <?= date('d F Y', strtotime($berita['tgl_posting'])) ?>
                <span class="mx-2">|</span>
                <i class="bi bi-eye"></i> Dibaca <?= number_format($berita['dibaca'] ?? 0) ?> kali
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8">
                <img src="img/berita/<?= htmlspecialchars($berita['gambar']) ?>" 
                     alt="<?= htmlspecialchars($berita['judul']) ?>" 
                     class="article-image">

                <div class="article-content">
                    <?= nl2br($berita['konten']) ?>
                </div>

                <div class="share-buttons">
                    <h5 class="mb-3">Bagikan artikel ini:</h5>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" 
                       class="share-button facebook">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($berita['judul']) ?>" 
                       target="_blank" 
                       class="share-button twitter">
                        <i class="bi bi-twitter"></i> Twitter
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' - https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" 
                       class="share-button whatsapp">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="related-articles">
                    <h4 class="mb-4">Berita Terkait</h4>
                    <?php if (!empty($beritaTerkait)): ?>
                        <?php foreach ($beritaTerkait as $terkait): ?>
                            <div class="related-article-card">
                                <a href="baca_berita.php?slug=<?= $terkait['slug'] ?>" 
                                   class="text-decoration-none">
                                    <img src="img/berita/<?= htmlspecialchars($terkait['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($terkait['judul']) ?>" 
                                         class="related-article-image mb-2">
                                    <h5 class="text-dark mb-1"><?= htmlspecialchars($terkait['judul']) ?></h5>
                                    <small class="text-muted">
                                        <?= date('d F Y', strtotime($terkait['tgl_posting'])) ?>
                                    </small>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Tidak ada berita terkait</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>