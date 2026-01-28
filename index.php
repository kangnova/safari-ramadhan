<?php
session_start();
require_once 'koneksi.php';

require_once 'hit_counter.php';

// Ambil data pengisi yang aktif
$tahun_masehi = date('Y');
$tahun_hijriyah = floor(($tahun_masehi - 621) * 1.03);
try {
    $query = "SELECT * FROM pengisi WHERE status = 'aktif' ORDER BY nama ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pengisi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung jumlah ustadz dan kakak pengkisah
    $count_ustadz = 0;
    $count_kakak = 0;
    
    foreach ($pengisi_list as $pengisi) {
        if (strpos($pengisi['nama'], 'Ustadz') !== false) {
            $count_ustadz++;
        } else {
            $count_kakak++;
        }
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Fetch Program Status
$stmtS = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_program_status'");
$stmtS->execute();
$stmtS->execute();
$programStatus = $stmtS->fetchColumn() ?: 'active';

// Admin Exception: Admin always sees active site
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $programStatus = 'active';
}

$stmtM = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_program_ended_message'");
$stmtM->execute();
$programEndedMessage = $stmtM->fetchColumn() ?: '';
// Setup Logo Path
$logoPath = 'img/logo.png'; // Default fallback
if (file_exists('assets/img/logo.png')) {
    $logoPath = 'assets/img/logo.png';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Safari Ramadhan <?= $tahun_hijriyah ?> H/<?= $tahun_masehi ?> merupakan program dakwah Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah di berbagai TPQ Kota Klaten untuk memberikan pendidikan melalui kisah-kisah inspiratif.">
<meta property="og:title" content="Safari Ramadhan <?= $tahun_hijriyah ?> H/<?= $tahun_masehi ?>">
<meta property="og:description" content="Safari Ramadhan adalah program dakwah rutin Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah untuk menghadirkan kajian dan motivasi di TPQ Kota Klaten selama bulan Ramadhan (1-26 Maret <?= $tahun_masehi ?>). Program ini memberikan pendidikan melalui kisah-kisah inspiratif kepada santri TPQ.">
<meta property="og:image" content="https://gnb.or.id/safariramadhan/img/img1.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:url" content="https://gnb.or.id/safariramadhan/">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Safari Ramadhan <?= $tahun_hijriyah ?> H/<?= $tahun_masehi ?>">
<meta name="twitter:description" content="Safari Ramadhan adalah program dakwah rutin Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah untuk menghadirkan kajian dan motivasi di TPQ Kota Klaten selama bulan Ramadhan (1-26 Maret <?= $tahun_masehi ?>). Program ini memberikan pendidikan melalui kisah-kisah inspiratif kepada santri TPQ.">
<meta name="twitter:image" content="https://gnb.or.id/safariramadhan/img/img1.jpg">


<title>Safari Ramadhan <?= $tahun_hijriyah ?> H/<?= $tahun_masehi ?></title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="assets/js/script.js" defer></script>
</head>
<body>
    <nav class="navbar">
    <div class="logo">
        <img src="<?= $logoPath ?>?v=<?= time() ?>" alt="Logo">
    </div>
    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <ul class="nav-links">
        <li><a href="#hero">Home</a></li>
        <li><a href="#program">Program</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#news">Berita</a></li>
        <li><a href="#team_safari">Tim Safari</a></li>
        <li><a href="#kontak">Kontak</a></li>
        <li class="cta-button"><a href="jadwal_safariramadhan.php" class="donate-btn">Jadwal Safari</a></li>

        <li class="cta-button"><a href="form.php" class="donate-btn daftar-online">Daftar Online</a></li>
        <li class="cta-button"><a href="donasi.php" class="donate-btn donasi">Donasi</a></li>
        <li class="cta-button">
            <a href="login_p.php" class="btn-login-nav" title="Login">
                <i class='bx bx-log-in'></i> Login
            </a>
        </li>
    </ul>
</nav>

    <!-- Hero Section -->
    <section id="hero" class="hero">
        <?php if ($programStatus === 'active'): ?>
            <?php
            // Fetch hero slides
            try {
                $stmt = $conn->prepare("SELECT * FROM hero_slides WHERE aktif = 1 ORDER BY urutan ASC");
                $stmt->execute();
                $hero_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                $hero_slides = [];
            }
            ?>
    
            <div class="slider desktop-only">
                <?php if (!empty($hero_slides)): ?>
                    <?php foreach ($hero_slides as $slide): ?>
                    <div class="slide">
                        <div class="slide-content">
                            <?php if (!empty($slide['link'])): ?>
                                <a href="<?= htmlspecialchars($slide['link']) ?>">
                                    <img src="img/slides/<?= htmlspecialchars($slide['gambar']) ?>" alt="<?= htmlspecialchars($slide['judul']) ?>">
                                </a>
                            <?php else: ?>
                                <img src="img/slides/<?= htmlspecialchars($slide['gambar']) ?>" alt="<?= htmlspecialchars($slide['judul']) ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback Text -->
                    <div class="slide">
                        <div class="slide-content" style="display: flex; justify-content: center; align-items: center; background-color: #f8f9fa;">
                            <h3 style="color: #6c757d; font-weight: normal;">Belum ada banner yang aktif</h3>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
    
            <div class="slider-nav">
                <button class="nav-button prev">❮</button>
                <button class="nav-button next">❯</button>
            </div>
    
            <div class="slider-dots">
                <?php if (!empty($hero_slides)): ?>
                    <?php foreach ($hero_slides as $index => $slide): ?>
                        <div class="dot <?= $index === 0 ? 'active' : '' ?>"></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="dot active"></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Program Ended Banner -->
            <div class="container h-100 d-flex align-items-center justify-content-center" style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #20B2AA 0%, #008B8B 100%); text-align: center; color: white; padding: 0 20px;">
                <div class="ended-message" style="max-width: 800px;">
                    <h1 style="font-size: 2.5rem; margin-bottom: 20px; font-weight: bold;">
                        <i class='bx bx-moon'></i> Safari Ramadhan <?= $tahun_hijriyah ?> H
                    </h1>
                    <div style="font-size: 1.2rem; line-height: 1.8; white-space: pre-line;">
                        <?= htmlspecialchars($programEndedMessage) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
    
    <?php
require_once 'koneksi.php';

// Mengambil data program dari database
// Mengambil data program dari database
try {
    $query = "SELECT * FROM program WHERE status = 'published' ORDER BY urutan ASC, tgl_update DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
    $programs = [];
}
?>

<!-- Program Section -->
<section id="program" class="program">
    <div class="container">
        <h2 class="program-title">PROGRAM</h2>
        <div class="card-container">
            <?php if (!empty($programs)): ?>
                <?php foreach ($programs as $program): ?>
                    <div class="card">
                        <img src="img/program/<?= htmlspecialchars($program['gambar']) ?>" 
                             alt="<?= htmlspecialchars($program['nama_program']) ?>" 
                             class="card-image">
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($program['nama_program']) ?></h3>
                            <p class="card-description">
                                <?= htmlspecialchars(substr($program['deskripsi'], 0, 100)) ?>...
                            </p>
                            <a href="detail_program.php?id=<?= $program['id_program'] ?>" class="card-button">
                                Selengkapnya
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center w-100">
                    <?php if (isset($error)): ?>
                        Terjadi kesalahan dalam memuat program.
                    <?php else: ?>
                        Belum ada program yang ditambahkan.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
    
    <!--Profile-->
    <?php if ($programStatus === 'active'): ?>
        <?php
        require_once 'koneksi.php';

        // Ambil data profil terbaru
        try {
            $query = "SELECT * FROM profil ORDER BY tgl_update DESC LIMIT 1";
            $stmt = $conn->query($query);
            $profil = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "Terjadi kesalahan: " . $e->getMessage();
        }
        ?>

        <!-- /* Profile */ -->
        <section class="profile-section">
            <div class="container">
                <div class="profile-container">
                    <?php if (isset($profil) && $profil): ?>
                        <div class="profile-video">
                            <iframe src="https://www.youtube.com/embed/<?= htmlspecialchars($profil['video_url']) ?>" 
                                    allowfullscreen></iframe>
                        </div>
                        <div class="profile-content">
                            <h2 class="profile-title"><?= htmlspecialchars($profil['judul']) ?></h2>
                            <p class="profile-text">
                                <?= nl2br(htmlspecialchars($profil['deskripsi'])) ?>
                            </p>
                            <a href="form.php" class="profile-btn">DAFTAR SEKARANG</a>
                        </div>
                    <?php else: ?>
                        <div class="profile-content text-center">
                            <p>Informasi profil belum tersedia</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

   <?php
   if ($programStatus === 'active') {
       require_once 'koneksi.php';

       // Ambil data kategori
       try {
           $query = "SELECT * FROM gallery_kategori ORDER BY nama_kategori";
           $stmt = $conn->query($query);
           $kategoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

           // Ambil data gallery dengan kategori
           $query = "SELECT g.*, k.nama_kategori, k.slug 
                     FROM gallery g 
                     JOIN gallery_kategori k ON g.id_kategori = k.id_kategori 
                     ORDER BY g.tgl_update DESC";
           $stmt = $conn->query($query);
           $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);
       } catch (PDOException $e) {
           echo "Terjadi kesalahan: " . $e->getMessage();
       }
   ?>

    <!-- Gallery -->
    <div class="container">
        <section id="gallery" class="gallery-section">
            <h2 class="section-title">GALLERY</h2>
            <div class="gallery-tabs">
                <button class="gallery-tab active" data-category="all">Semua</button>
                <?php foreach ($kategoris as $kategori): ?>
                    <button class="gallery-tab" data-category="<?= htmlspecialchars($kategori['slug']) ?>">
                        <?= htmlspecialchars($kategori['nama_kategori']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="gallery-container">
                <?php foreach ($galleries as $gallery): ?>
                    <div class="gallery-item" data-category="<?= htmlspecialchars($gallery['slug']) ?>">
                        <img src="img/gallery/<?= htmlspecialchars($gallery['gambar']) ?>" 
                             alt="<?= htmlspecialchars($gallery['judul']) ?>" 
                             class="gallery-image">
                        <div class="gallery-overlay">
                            <h3><?= htmlspecialchars($gallery['judul']) ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
   <?php } ?>
<?php if ($programStatus === 'active'): ?>
    <?php
    require_once 'koneksi.php';

    // Ambil 4 berita terbaru yang sudah dipublish
    try {
        $query = "SELECT * FROM berita 
                  WHERE status = 'published' 
                  ORDER BY tgl_posting DESC 
                  LIMIT 8";
        $stmt = $conn->query($query);
        $beritas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Terjadi kesalahan: " . $e->getMessage();
    }

    // Fungsi untuk memotong excerpt
    function createExcerpt($text, $length = 100) {
        // Hilangkan HTML tags
        $text = strip_tags($text);
        // Potong teks
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length) . '...';
        }
        return $text;
    }
    ?>

    <!-- Berita -->
    <section id="news" class="news-section">
        <div class="container">
            <h2 class="section-title">BERITA & KEGIATAN</h2>
            <div class="news-container">
                <?php if (!empty($beritas)): ?>
                    <?php foreach ($beritas as $berita): ?>
                        <article class="news-card">
                            <img src="img/berita/<?= htmlspecialchars($berita['gambar']) ?>" 
                                 alt="<?= htmlspecialchars($berita['judul']) ?>" 
                                 class="news-image">
                            <div class="news-content">
                                <div class="news-date">
                                    <?= date('d/m/Y', strtotime($berita['tgl_posting'])) ?>
                                </div>
                                <h3 class="news-headline">
                                    <?= htmlspecialchars($berita['judul']) ?>
                                </h3>
                                <p class="news-excerpt">
                                    <?= createExcerpt($berita['konten']) ?>
                                </p>
                                <a href="baca_berita.php?slug=<?= $berita['slug'] ?>" class="news-btn">Baca</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center w-100">
                        <p>Belum ada berita yang dipublikasikan</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!--Team Safari-->

<?php if ($programStatus === 'active'): ?>
    <!-- Team Safari Section -->
    <section id="team_safari" class="team-section">
        <div class="team-container">
            <h2 class="section-title">TIM SAFARI RAMADHAN</h2>
            
            <p class="team-intro">
                Tim Safari Ramadhan <?= $tahun_hijriyah ?> H/<?= $tahun_masehi ?> terdiri dari para ustadz dan kakak pengkisah yang berpengalaman 
                dalam memberikan kajian dan kisah inspiratif kepada santri TPQ di seluruh Klaten. Mereka siap 
                menghadirkan kegiatan dakwah yang menarik dan edukatif selama bulan Ramadhan.
            </p>
            
            <div class="team-filters">
                <button class="filter-btn active" data-filter="all">
                    Semua <span class="filter-count"><?= count($pengisi_list) ?></span>
                </button>
                <button class="filter-btn" data-filter="Kak">
                    Kakak Pengkisah <span class="filter-count"><?= $count_kakak ?></span>
                </button>
                <button class="filter-btn" data-filter="Ustadz">
                    Ustadz <span class="filter-count"><?= $count_ustadz ?></span>
                </button>
            </div>
            
            <div class="team-grid">
                <?php
                if (!empty($pengisi_list)):
                    foreach ($pengisi_list as $pengisi):
                        // Extract type (Kak or Ustadz) from nama
                        $type = (strpos($pengisi['nama'], 'Ustadz') !== false) ? 'Ustadz' : 'Kak';
                ?>
                <div class="team-card" data-type="<?= $type ?>">
    <div class="team-photo">
        <?php if(!empty($pengisi['foto']) && file_exists( $pengisi['foto'])): ?>
            <img src="<?= $pengisi['foto'] ?>" alt="<?= htmlspecialchars($pengisi['nama']) ?>">
        <?php else: ?>
            <img src="img/pengisi/default.jpg" alt="Foto Default">
        <?php endif; ?>
    </div>
    <div class="team-info">
        <h3 class="team-name"><?= htmlspecialchars($pengisi['nama']) ?></h3>
        <div class="team-location">
            <i class='bx bx-map'></i> <?= htmlspecialchars($pengisi['alamat']) ?>
        </div>

    </div>
</div>
                <?php
                    endforeach;
                else:
                ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 0;">
                    <p>Belum ada data tim safari yang tersedia.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<!--Team Safari-->

   <?php
   if ($programStatus === 'active') {
       require_once 'koneksi.php';

       // Ambil data sponsor yang aktif
       try {
           $query = "SELECT * FROM sponsor WHERE status = 'aktif' ORDER BY urutan";
           $stmt = $conn->query($query);
           $sponsors = $stmt->fetchAll(PDO::FETCH_ASSOC);
       } catch (PDOException $e) {
           echo "Terjadi kesalahan: " . $e->getMessage();
       }
   ?>

    <!-- Sponsor -->
    <section class="sponsor-section">
        <?php if (!empty($sponsors)): ?>
            <div class="sponsor-container">
                <div class="sponsor-track">
                    <!-- Original sponsors -->
                    <?php foreach ($sponsors as $sponsor): ?>
                        <div class="sponsor-item">
                            <?php if (!empty($sponsor['url'])): ?>
                                <a href="<?= htmlspecialchars($sponsor['url']) ?>" target="_blank">
                                    <img src="img/sponsor/<?= htmlspecialchars($sponsor['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>">
                                </a>
                            <?php else: ?>
                                <img src="img/sponsor/<?= htmlspecialchars($sponsor['gambar']) ?>" 
                                     alt="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Duplicate set for seamless loop -->
                    <?php foreach ($sponsors as $sponsor): ?>
                        <div class="sponsor-item">
                            <?php if (!empty($sponsor['url'])): ?>
                                <a href="<?= htmlspecialchars($sponsor['url']) ?>" target="_blank">
                                    <img src="img/sponsor/<?= htmlspecialchars($sponsor['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>">
                                </a>
                            <?php else: ?>
                                <img src="img/sponsor/<?= htmlspecialchars($sponsor['gambar']) ?>" 
                                     alt="<?= htmlspecialchars($sponsor['nama_sponsor']) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
   <?php } ?>
    <!-- footer -->
    <?php include 'footer_content.php'; ?>

<!-- HTML untuk Lightbox -->
<div id="teamLightbox" class="lightbox">
    <span class="lightbox-close">&times;</span>
    <div class="lightbox-content">
        <img id="lightboxImage" class="lightbox-image" src="" alt="">
        <div class="lightbox-nav">
            <button id="lightboxPrev">❮</button>
            <button id="lightboxNext">❯</button>
        </div>
    </div>
    <div class="lightbox-caption" id="lightboxCaption"></div>
    <div class="lightbox-controls">
        <button class="lightbox-button" id="downloadBtn">
            ⬇️ Unduh
        </button>
    </div>
</div>

<!-- Script untuk Lightbox -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi lightbox
    const lightbox = document.getElementById('teamLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxCaption = document.getElementById('lightboxCaption');
    const lightboxClose = document.querySelector('.lightbox-close');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    const downloadBtn = document.getElementById('downloadBtn');
    
    // Kumpulkan semua gambar tim
    const teamPhotos = document.querySelectorAll('.team-photo img');
    let currentIndex = 0;
    
    // Event untuk membuka lightbox ketika tim photo diklik
    teamPhotos.forEach((photo, index) => {
        photo.addEventListener('click', function() {
            currentIndex = index;
            showImage(index);
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden'; // Mencegah scroll
        });
    });
    
    // Fungsi untuk menampilkan gambar sesuai index
    function showImage(index) {
        if (index < 0) index = teamPhotos.length - 1;
        if (index >= teamPhotos.length) index = 0;
        
        currentIndex = index;
        const photo = teamPhotos[index];
        const photoSrc = photo.getAttribute('src');
        const name = photo.closest('.team-card').querySelector('.team-name').textContent;
        
        lightboxImage.setAttribute('src', photoSrc);
        lightboxCaption.textContent = name;
        
        // Set up download button dengan nama file yang benar
        downloadBtn.onclick = function() {
            downloadImage(photoSrc, name.replace(/\s+/g, '_') + '.jpg');
        };
    }
    
    // Fungsi untuk download gambar
    function downloadImage(src, filename) {
        // Buat link untuk download
        const a = document.createElement('a');
        a.href = src;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // Event untuk menutup lightbox
    lightboxClose.addEventListener('click', function() {
        lightbox.classList.remove('active');
        document.body.style.overflow = ''; // Kembalikan scroll
    });
    
    // Event untuk navigasi prev/next
    lightboxPrev.addEventListener('click', function() {
        showImage(currentIndex - 1);
    });
    
    lightboxNext.addEventListener('click', function() {
        showImage(currentIndex + 1);
    });
    
    // Event keyboard untuk navigasi
    document.addEventListener('keydown', function(e) {
        if (lightbox.classList.contains('active')) {
            if (e.key === 'Escape') {
                lightbox.classList.remove('active');
                document.body.style.overflow = '';
            } else if (e.key === 'ArrowLeft') {
                showImage(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                showImage(currentIndex + 1);
            }
        }
    });
    
    // Tutup lightbox jika klik di luar gambar
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slider = document.querySelector('.slider');
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
            const prevButton = document.querySelector('.prev');
            const nextButton = document.querySelector('.next');
            
            let currentSlide = 0;
            const slideCount = slides.length;
            let slideInterval;

            // Fungsi untuk menampilkan slide
            function showSlide(index) {
                if (index >= slideCount) {
                    currentSlide = 0;
                } else if (index < 0) {
                    currentSlide = slideCount - 1;
                } else {
                    currentSlide = index;
                }

                // Update posisi slider
                slider.style.transform = `translateX(-${currentSlide * 100}%)`;

                // Update dots aktif
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentSlide);
                });
            }

            // Event listener untuk tombol prev/next
            prevButton.addEventListener('click', () => {
                showSlide(currentSlide - 1);
                resetInterval();
            });

            nextButton.addEventListener('click', () => {
                showSlide(currentSlide + 1);
                resetInterval();
            });

            // Event listener untuk dots
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    showSlide(index);
                    resetInterval();
                });
            });

            // Fungsi untuk slider otomatis
            function startSlideInterval() {
                slideInterval = setInterval(() => {
                    showSlide(currentSlide + 1);
                }, 5000); // Ganti slide setiap 5 detik
            }

            // Reset interval saat interaksi manual
            function resetInterval() {
                clearInterval(slideInterval);
                startSlideInterval();
            }

            // Touch events untuk mobile
            let touchStartX = 0;
            let touchEndX = 0;

            slider.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            });

            slider.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].clientX;
                handleSwipe();
            });

            function handleSwipe() {
                const swipeThreshold = 50;
                const difference = touchStartX - touchEndX;

                if (Math.abs(difference) > swipeThreshold) {
                    if (difference > 0) {
                        // Swipe kiri
                        showSlide(currentSlide + 1);
                    } else {
                        // Swipe kanan
                        showSlide(currentSlide - 1);
                    }
                    resetInterval();
                }
            }

            // Mulai slider otomatis
            startSlideInterval();
        });
    </script>
    


<!--smooth scroll-->
<script>
// Fungsi untuk smooth scroll
function smoothScroll(target, duration) {
   const element = document.querySelector(target);
   const targetPosition = element.offsetTop;
   const startPosition = window.pageYOffset;
   const distance = targetPosition - startPosition;
   let startTime = null;

   function animation(currentTime) {
       if (startTime === null) startTime = currentTime;
       const timeElapsed = currentTime - startTime;
       const run = ease(timeElapsed, startPosition, distance, duration);
       window.scrollTo(0, run);
       if (timeElapsed < duration) requestAnimationFrame(animation);
   }

   // Fungsi easing
   function ease(t, b, c, d) {
       t /= d / 2;
       if (t < 1) return c / 2 * t * t + b;
       t--;
       return -c / 2 * (t * (t - 2) - 1) + b;
   }

   requestAnimationFrame(animation);
}

// Event listener untuk menu links
// Event listener untuk menu links
document.querySelectorAll('.nav-links a').forEach(link => {
   link.addEventListener('click', function(e) {
       const href = this.getAttribute('href');
       
       // Hanya jalankan smooth scroll jika link adalah anchor (diawali #)
       if (href.startsWith('#')) {
           e.preventDefault();
           const target = href.substring(1);
           smoothScroll(`#${target}`, 1000);
           
           // Tutup menu mobile jika terbuka
           document.querySelector('.hamburger').classList.remove('active');
           document.querySelector('.nav-links').classList.remove('active');
       }
   });
});
</script>
<!--smooth scroll-->

<!--full screen-->
<script>
    // Add scroll reveal
document.addEventListener('DOMContentLoaded', function() {
    if(window.innerWidth <= 768) {
        const sections = document.querySelectorAll('.program, .profile-section, .gallery-section, .news-section, .sponsor-section, footer');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        });

        sections.forEach(section => observer.observe(section));
    }
});
</script>
<!--full screen-->

<!--Tab Gallery-->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter gallery
    const galleryTabs = document.querySelectorAll('.gallery-tab');
    const galleryItems = document.querySelectorAll('.gallery-item');

    galleryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            galleryTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');

            const category = this.getAttribute('data-category');
            
            galleryItems.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, 50);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
});
</script>
<!--Tab Gallery-->

 <script>


        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const teamCards = document.querySelectorAll('.team-card');

            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const filter = this.getAttribute('data-filter');

                    // Filter cards
                    teamCards.forEach(card => {
                        if (filter === 'all' || card.getAttribute('data-type').includes(filter)) {
                            card.style.display = 'block';
                            setTimeout(() => {
                                card.style.opacity = '1';
                                card.style.transform = 'translateY(0)';
                            }, 50);
                        } else {
                            card.style.opacity = '0';
                            card.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                card.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
