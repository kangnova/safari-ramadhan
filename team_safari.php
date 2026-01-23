<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

// Ambil data pengisi yang aktif
$total_all = 0;
$total_kakak = 0;
$total_ustadz = 0;
$pengisi_list = [];

try {
    $query = "SELECT * FROM pengisi WHERE status = 'aktif' ORDER BY nama ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pengisi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_all = count($pengisi_list);
    foreach($pengisi_list as $p) {
        if (strpos($p['nama'], 'Kak') !== false) {
            $total_kakak++;
        } elseif (strpos($p['nama'], 'Ustadz') !== false) {
            $total_ustadz++;
        }
    }
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tim Safari Ramadhan 1446 H/2025 Yayasan Guru Ngaji Berdaya di Klaten">
    <meta property="og:title" content="Tim Safari Ramadhan 1446 H/2025">
    <meta property="og:description" content="Perkenalan Tim Safari Ramadhan 1446 H/2025 Yayasan Guru Ngaji Berdaya yang membawakan kisah-kisah inspiratif di berbagai TPQ Kota Klaten selama bulan Ramadhan.">
    <meta property="og:image" content="https://gnb.or.id/safariramadhan/img/img1.jpg">
    <meta property="og:url" content="https://gnb.or.id/safariramadhan/team_safari.php">

    <title>Tim Safari Ramadhan 1446 H/2025 - Yayasan GNB</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        /* Navbar Styles */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .logo img {
            height: 40px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
        }

        .nav-links a {
            text-decoration: none;
            color: #20B2AA;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #008B8B;
        }

        .donate-btn {
            background-color: #20B2AA;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .donate-btn:hover {
            background-color: #008B8B;
        }

        .donate-btn.daftar-online {
            background-color: #1E90FF;
        }

        .donate-btn.daftar-online:hover {
            background-color: #0077B6;
        }

        .donate-btn.donasi {
            background-color: #FF5252;
        }

        .donate-btn.donasi:hover {
            background-color: #FF0000;
        }

        /* Hamburger menu */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
        }

        .hamburger span {
            display: block;
            width: 25px;
            height: 3px;
            background: #333;
            transition: 0.3s;
        }

        /* Page Styles */
        .page-header {
            background: linear-gradient(135deg, #20B2AA, #48D1CC);
            color: white;
            text-align: center;
            padding: 3.5rem 1rem 2rem;
            margin-top: 60px;
        }

        .page-title {
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }

        .page-description {
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .team-container {
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 1rem;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .team-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }

        .team-card:hover {
            transform: translateY(-5px);
        }
        
        .role-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            z-index: 1;
        }
        
        .bg-kakak { background-color: #20B2AA; }
        .bg-ustadz { background-color: #FF7F50; }

        .team-photo {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-bottom: 2px solid #f0f0f0;
            background-color: #f8f9fa; /* Placeholder color */
        }

        .team-content {
            padding: 1.5rem;
            text-align: center;
        }

        .team-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #20B2AA;
            margin-bottom: 0.5rem;
        }

        .team-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .team-info i {
            margin-right: 0.5rem;
            color: #20B2AA;
        }

        .team-contact {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background-color: #20B2AA;
            color: white;
            text-decoration: none;
            border-radius: 50%;
            transition: background-color 0.3s ease;
            font-size: 1.5rem;
            margin-top: 0.5rem;
        }

        .team-contact:hover {
            background-color: #008B8B;
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.5rem 1.5rem;
            background: transparent;
            border: 2px solid #20B2AA;
            color: #20B2AA;
            cursor: pointer;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background: #20B2AA;
            color: white;
        }
        
        .load-more-container {
            text-align: center;
            margin-top: 2rem;
        }
        
        .btn-load-more {
            background-color: transparent;
            color: #20B2AA;
            border: 2px solid #20B2AA;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-load-more:hover {
            background-color: #20B2AA;
            color: white;
        }

        footer {
            background-color: #20B2AA;
            color: white;
            padding: 2rem 0;
            text-align: center;
        }

        .footer-text {
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background: white;
                padding: 1rem;
                gap: 1rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }

            .nav-links.active {
                display: flex;
            }

            .logo img {
                height: 30px;
            }

            .page-header {
                padding: 3rem 1rem 1.5rem;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .team-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.5rem;
            }

            .team-photo {
                height: 200px;
            }

            .team-content {
                padding: 1rem;
            }

            .team-name {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <img src="img/logo.png" alt="Logo">
        </div>
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    <ul class="nav-links">
            <li><a href="index.php#hero">Home</a></li>
            <li><a href="index.php#program">Program</a></li>
            <li><a href="index.php#gallery">Gallery</a></li>
            <li><a href="index.php#news">Berita</a></li>
            <li><a href="team_safari.php" class="active">Tim Safari</a></li>
            <li><a href="index.php#contact">Kontak</a></li>
            <li class="cta-button"><a href="jadwal_safariramadhan.php" class="donate-btn">Jadwal Safari</a></li>
            <li class="cta-button"><a href="form.php" class="donate-btn daftar-online">Daftar Online</a></li>
            <li class="cta-button"><a href="donasi.php" class="donate-btn donasi">Donasi</a></li>
        </ul>
    </nav>

    <!-- Header -->
    <header class="page-header">
        <h1 class="page-title">Tim Safari Ramadhan 1446 H/2025</h1>
        <p class="page-description">
            Perkenalkan para pengisi kegiatan Safari Ramadhan yang terdiri dari para pengkisah, ustadz, dan motivator yang akan hadir di TPQ Kota Klaten selama bulan Ramadhan untuk memberikan inspirasi dan pengetahuan.
        </p>
    </header>

    <!-- Team Section -->
    <section class="team-container">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger text-center">
                <?= $error ?>
            </div>
        <?php else: ?>
            <!-- Filter buttons -->
            <div class="filter-container">
                <button class="filter-btn active" data-filter="all">Semua (<?= $total_all ?>)</button>
                <button class="filter-btn" data-filter="Kak">Pengkisah (<?= $total_kakak ?>)</button>
                <button class="filter-btn" data-filter="Ustadz">Ustadz (<?= $total_ustadz ?>)</button>
            </div>

            <!-- Team grid -->
            <div class="team-grid" id="teamGrid">
                <?php foreach($pengisi_list as $pengisi): 
                    $is_kakak = strpos($pengisi['nama'], 'Kak') !== false;
                    $category = $is_kakak ? 'Kak' : 'Ustadz';
                    $badge_class = $is_kakak ? 'bg-kakak' : 'bg-ustadz';
                    $badge_text = $is_kakak ? 'Pengkisah' : 'Ustadz';
                ?>
                    <div class="team-card" data-category="<?= $category ?>" style="display: none;">
                        <span class="role-badge <?= $badge_class ?>"><?= $badge_text ?></span>
                        <?php if(!empty($pengisi['foto']) && file_exists($pengisi['foto'])): ?>
                            <img src="<?= $pengisi['foto'] ?>" class="team-photo" alt="Foto <?= $pengisi['nama'] ?>" loading="lazy">
                        <?php elseif(!empty($pengisi['foto']) && file_exists('admin/'.$pengisi['foto'])): ?>
                            <img src="admin/<?= $pengisi['foto'] ?>" class="team-photo" alt="Foto <?= $pengisi['nama'] ?>" loading="lazy">
                        <?php else: ?>
                            <img src="admin/uploads/default.png" class="team-photo" alt="Foto Default" loading="lazy">
                        <?php endif; ?>
                        <div class="team-content">
                            <h3 class="team-name"><?= htmlspecialchars($pengisi['nama']) ?></h3>
                            <p class="team-info">
                                <i class='bx bx-map'></i><?= htmlspecialchars($pengisi['alamat']) ?>
                            </p>
                            <a href="https://wa.me/<?= $pengisi['no_hp'] ?>" class="team-contact" target="_blank">
                                <i class='bx bxl-whatsapp'></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="load-more-container">
                <button id="loadMoreBtn" class="btn-load-more">Tampilkan Lebih Banyak</button>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-text">
            <p>© 2025 - Yayasan Guru Ngaji Berdaya - Safari Ramadhan 1446 H/2025</p>
            <p>Memberikan inspirasi dan pengetahuan kepada santri TPQ Klaten melalui kisah-kisah inspiratif</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.hamburger').addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-links').classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.navbar')) {
                document.querySelector('.hamburger').classList.remove('active');
                document.querySelector('.nav-links').classList.remove('active');
            }
        });

        // Close menu when clicking on a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                document.querySelector('.hamburger').classList.remove('active');
                document.querySelector('.nav-links').classList.remove('active');
            });
        });

        // Team Pagination Logic
        document.addEventListener('DOMContentLoaded', function() {
            const itemsPerPage = 12;
            let currentFilter = 'all';
            let visibleCount = 0;
            const allCards = document.querySelectorAll('.team-card');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const teamGrid = document.getElementById('teamGrid');

            function filterAndShow() {
                // Get all items that match current filter
                const filteredItems = Array.from(allCards).filter(card => {
                    if (currentFilter === 'all') return true;
                    // Check if name or data-category matches
                    const category = card.getAttribute('data-category');
                    return category.includes(currentFilter);
                });

                // Hide all initially
                allCards.forEach(card => card.style.display = 'none');

                // Show items up to visibleCount
                let shown = 0;
                filteredItems.forEach((card, index) => {
                    if (index < visibleCount) {
                        card.style.display = 'block';
                        shown++;
                    }
                });

                // Toggle Update Load More Button
                if (visibleCount >= filteredItems.length) {
                    loadMoreBtn.style.display = 'none';
                } else {
                    loadMoreBtn.style.display = 'inline-block';
                    loadMoreBtn.textContent = `Tampilkan Lebih Banyak (${filteredItems.length - shown} lagi)`;
                }
            }

            // Initialize
            visibleCount = itemsPerPage;
            filterAndShow();

            // Load More Click Handler
            loadMoreBtn.addEventListener('click', function() {
                visibleCount += itemsPerPage;
                filterAndShow();
            });

            // Filter Click Handlers
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Update active state
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Reset pagination and set filter
                    currentFilter = this.getAttribute('data-filter');
                    visibleCount = itemsPerPage;
                    filterAndShow();
                });
            });
        });
    </script>
</body>
</html>