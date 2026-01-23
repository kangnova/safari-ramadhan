<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Safari Ramadhan 1446 H/2025</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="assets/js/script.js" defer></script>
    <style>
        .contact-page {
            padding: 120px 5% 4rem; /* Adjusted top padding for fixed navbar */
            background-color: #f9f9f9;
        }
        .contact-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .contact-header h1 {
            color: #20B2AA;
            margin-bottom: 1rem;
        }
        .contact-info-list {
            margin-bottom: 2rem;
        }
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .contact-item i {
            font-size: 1.5rem;
            color: #20B2AA;
            margin-right: 1rem;
            width: 30px;
            text-align: center;
        }
        .contact-item a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        .contact-item a:hover {
            color: #20B2AA;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <section class="contact-page">
        <div class="contact-container">
            <div class="contact-header">
                <h1>Hubungi Kami</h1>
                <p>Silakan hubungi kami untuk informasi lebih lanjut mengenai program Safari Ramadhan.</p>
            </div>

            <?php
            // Ambil data profil
            try {
                $query = "SELECT * FROM profil ORDER BY tgl_update DESC LIMIT 1";
                $stmt = $conn->query($query);
                $profil = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "Terjadi kesalahan: " . $e->getMessage();
            }
            ?>

            <div class="contact-info-list">
                <div class="contact-item">
                    <i class='bx bx-map'></i>
                    <span><?= htmlspecialchars($profil['alamat'] ?? 'Alamat belum tersedia') ?></span>
                </div>
                
                <?php if (!empty($profil['whatsapp'])): ?>
                <div class="contact-item">
                    <i class='bx bxl-whatsapp'></i>
                    <a href="https://wa.me/<?= htmlspecialchars($profil['whatsapp']) ?>" target="_blank">
                        <?= substr_replace(htmlspecialchars($profil['whatsapp']), "-", 4, 0) ?> (WhatsApp)
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($profil['email'])): ?>
                <div class="contact-item">
                    <i class='bx bx-envelope'></i>
                    <a href="mailto:<?= htmlspecialchars($profil['email']) ?>">
                        <?= htmlspecialchars($profil['email']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <div class="social-links" style="justify-content: center; margin-top: 2rem;">
                <!-- Social media icons reused from index but styled centrally -->
                <?php if (!empty($profil['facebook'])): ?>
                    <a href="<?= strpos($profil['facebook'], 'http') === 0 ? htmlspecialchars($profil['facebook']) : 'https://facebook.com/' . htmlspecialchars($profil['facebook']) ?>" 
                       target="_blank" class="social-icon fb"><i class='bx bxl-facebook'></i></a>
                <?php endif; ?>
                <?php if (!empty($profil['twitter'])): ?>
                    <a href="<?= strpos($profil['twitter'], 'http') === 0 ? htmlspecialchars($profil['twitter']) : 'https://twitter.com/' . htmlspecialchars($profil['twitter']) ?>" 
                       target="_blank" class="social-icon twitter"><i class='bx bxl-twitter'></i></a>
                <?php endif; ?>
                <?php if (!empty($profil['instagram'])): ?>
                    <a href="<?= strpos($profil['instagram'], 'http') === 0 ? htmlspecialchars($profil['instagram']) : 'https://instagram.com/' . htmlspecialchars($profil['instagram']) ?>" 
                       target="_blank" class="social-icon ig"><i class='bx bxl-instagram'></i></a>
                <?php endif; ?>
                <?php if (!empty($profil['youtube'])): ?>
                    <a href="<?= strpos($profil['youtube'], 'http') === 0 ? htmlspecialchars($profil['youtube']) : 'https://youtube.com/' . htmlspecialchars($profil['youtube']) ?>" 
                       target="_blank" class="social-icon yt"><i class='bx bxl-youtube'></i></a>
                <?php endif; ?>
                <?php if (!empty($profil['tiktok'])): ?>
                    <a href="<?= strpos($profil['tiktok'], 'http') === 0 ? htmlspecialchars($profil['tiktok']) : 'https://tiktok.com/@' . htmlspecialchars($profil['tiktok']) ?>" 
                       target="_blank" class="social-icon tt"><i class='bx bxl-tiktok'></i></a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>
