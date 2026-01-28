<?php
// Koneksi database
require_once 'koneksi.php'; // Menggunakan file koneksi.php yang Anda berikan
$pdo = $conn; // Menggunakan variabel $conn dari koneksi.php

/**
 * Fungsi untuk mengambil gambar slider berdasarkan ID kampanye
 * @param PDO $pdo Koneksi database
 * @param int|null $campaignId ID kampanye, kosongkan untuk semua kampanye
 * @return array Data gambar slider
 */
// Ambil ID program dari query string
$programId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Ambil Data Program Utama (Selected or Default)
function getProgramDetail($pdo, $id = null) {
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM program_donasi WHERE id = ?");
        $stmt->execute([$id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
    } 
    
    // Jika tidak ada ID yang diminta, cek apakah ada Program Unggulan yang diset di admin
    if (!$id && empty($program)) {
        $stmt = $pdo->query("SELECT program_id FROM target_donasi WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
        $featured = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($featured && !empty($featured['program_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM program_donasi WHERE id = ?");
            $stmt->execute([$featured['program_id']]);
            $program = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    // Jika masih tidak ada program (tidak ada ID, tidak ada Featured), ambil yang terbaru/aktif
    if (empty($program)) {
        $stmt = $pdo->query("SELECT * FROM program_donasi WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return $program;
}

$program = getProgramDetail($pdo, $programId);

// Jika database kosong sama sekali
if (!$program) {
    $program = [
        'id' => 0,
        'judul' => 'Ifthar Ramadhan',
        'deskripsi' => 'Program berbagi hidangan berbuka puasa untuk sesama.',
        'target_nominal' => 350000000,
        'tanggal_selesai' => date('Y-m-d', strtotime('+30 days')),
        'gambar_utama' => 'img/donasi/default.jpg'
    ];
}

$programId = $program['id']; // Pastikan ID terisi dari hasil query

// Get total donasi per program
function getTotalDonasi($pdo, $programId) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(nominal) as total FROM donasi WHERE status = 'success' AND (program_id = ? OR program_id IS NULL)"); // Fallback logic for old donations
        if ($programId > 1) { // Jika program baru, strictly check ID
             $stmt = $pdo->prepare("SELECT SUM(nominal) as total FROM donasi WHERE status = 'success' AND program_id = ?");
        }
        $stmt->execute([$programId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Get jumlah donatur per program
function getJumlahDonatur($pdo, $programId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM donasi WHERE status = 'success' AND (program_id = ? OR program_id IS NULL)");
        if ($programId > 1) {
             $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM donasi WHERE status = 'success' AND program_id = ?");
        }
        $stmt->execute([$programId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?: 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Hitung sisa hari
function hitungSisaHari($tanggalSelesai) {
    $sekarang = new DateTime();
    $selesai = new DateTime($tanggalSelesai);
    
    if ($selesai < $sekarang) {
        return 0; 
    }
    
    $selisih = $sekarang->diff($selesai);
    return $selisih->days;
}

// Format angka dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Assign variables for view
$titleDonasi = $program['judul'];
$targetDonasi = [
    'jumlah' => $program['target_nominal'],
    'deskripsi' => $program['deskripsi'],
    'tanggal_selesai' => $program['tanggal_selesai']
];

$totalDonasi = getTotalDonasi($pdo, $programId);
$jumlahDonatur = getJumlahDonatur($pdo, $programId);
$sisaHari = hitungSisaHari($targetDonasi['tanggal_selesai']);
$persentase = ($targetDonasi['jumlah'] > 0) ? min(100, ($totalDonasi / $targetDonasi['jumlah']) * 100) : 0;

// Slider logic: Use gambar_utama as first slide, fallback to existing logic if needed
$sliderImages = [];
if (!empty($program['gambar_utama']) && file_exists($program['gambar_utama'])) {
    $sliderImages[] = [
        'image_path' => $program['gambar_utama'],
        'alt_text' => $program['judul']
    ];
} else {
    // Default images
    $sliderImages[] = ['image_path' => 'img/donasi/default.jpg', 'alt_text' => 'Default 1'];
}

// Ambil Donasi Terbaru per Program
$query = "SELECT nama_donatur, nominal, is_anonim, created_at 
          FROM donasi 
          WHERE status = 'success' AND (program_id = ? OR program_id IS NULL)
          ORDER BY created_at DESC 
          LIMIT 5";
if ($programId > 1) {
    $query = "SELECT nama_donatur, nominal, is_anonim, created_at 
          FROM donasi 
          WHERE status = 'success' AND program_id = ?
          ORDER BY created_at DESC 
          LIMIT 5";
}

try {
    $stmt = $conn->prepare($query);
    $stmt->execute([$programId]);
    $donasi_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $donasi_terbaru = [];
}

// Ambil Program Lainnya
try {
    $stmtOther = $conn->prepare("SELECT id, judul, gambar_utama FROM program_donasi WHERE id != ? AND status = 'active' LIMIT 5");
    $stmtOther->execute([$programId]);
    $otherPrograms = $stmtOther->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $otherPrograms = [];
}

// Fungsi untuk menghitung waktu yang lalu dengan lebih akurat
function time_elapsed_string($datetime) {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
    $diff = $now->diff($ago);

    // Konversi ke total menit untuk perhitungan yang lebih akurat
    $total_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

    if ($diff->y > 0) {
        return $diff->y . ' tahun yang lalu';
    } elseif ($diff->m > 0) {
        return $diff->m . ' bulan yang lalu';
    } elseif ($diff->d > 0) {
        return $diff->d . ' hari yang lalu';
    } elseif ($diff->h > 0) {
        return $diff->h . ' jam yang lalu';
    } elseif ($diff->i > 0) {
        return $diff->i . ' menit yang lalu';
    } else {
        return 'baru saja';
    }
}

// Debug: tampilkan waktu asli di belakang layar untuk pengecekan
function debug_time($created_at) {
    $dt = new DateTime($created_at);
    return $dt->format('Y-m-d H:i:s');
}
?>
<!DOCTYPE html>
<html lang="id">
    <?php
// Mengambil judul dari tabel paket_donasi (misal judul terbaru atau yang aktif)
// $titleDonasi sudah diset di atas dari data program

?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titleDonasi); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variabel warna */
        :root {
            --turquoise: #40E0D0;
            --dark-blue: #003366;
            --orange: #FF8800;
            --yellow: #FFD700;
            --light-turquoise: #AFFBFB;
            --dark-turquoise: #20B2AA;
        }
        
        body {
            background-color: var(--light-turquoise);
        }
        
        /* Container styles */
        .page-container {
            max-width: 480px;
            margin: 0 auto;
            position: relative;
            min-height: 100vh;
            background: white;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .header {
            position: fixed;
            top: 0;
            width: 100%;
            max-width: 480px;
            z-index: 1000;
            background: var(--dark-blue);
            color: white;
            border-bottom: 1px solid var(--turquoise);
        }
        
        .header a, .header button {
            color: white;
        }

        .content {
            margin-top: 56px;
            margin-bottom: 70px;
            padding: 0;
            overflow-y: auto;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            max-width: 480px;
            background: var(--dark-blue);
            color: white;
            border-top: 1px solid var(--turquoise);
            padding: 10px 15px;
        }

        /* Slider styles */
        .slider-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 300px;
        }

        .slider {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out; /* Durasi 1 detik */
        }

        .slide.active {
            opacity: 1;
        }

        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Button styles */
        .back-button, .cart-button {
            border: none;
            background: none;
            padding: 10px;
        }

        .share-button {
            border: 1px solid var(--turquoise);
            background: transparent;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
        }

        .donate-button {
            background: var(--orange);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            flex-grow: 1;
            margin-left: 10px;
        }

        /* Content sections */
        .donation-info {
            padding: 20px;
        }

        .donation-progress {
            height: 5px;
            margin-top: 10px;
        }
        
        .progress-bar {
            background-color: var(--orange) !important;
        }

        .donation-stats {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        h3.total-donasi {
            color: var(--orange);
        }

        .donor-item {
            border-left: 3px solid var(--turquoise);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .donor-item h6 {
            color: var(--dark-blue);
        }

        /* Dots navigation */
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 100;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
        }

        .dot.active {
            background: var(--yellow);
        }

        /* Desktop styles */
        @media (min-width: 992px) {
            .page-container {
                margin-top: 20px;
                margin-bottom: 20px;
                border-radius: 15px;
                overflow: hidden;
            }

            .header {
                border-radius: 15px 15px 0 0;
            }

            .footer {
                border-radius: 0 0 15px 15px;
            }
        }
    
        /* Share Modal Styles */
        .share-modal {
            position: fixed;
            bottom: -100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 20px 20px 0 0;
            padding: 20px;
            z-index: 1100;
            transition: bottom 0.3s ease-in-out;
            max-width: 480px;
            margin: 0 auto;
        }

        .share-modal.show {
            bottom: 0;
        }

        .share-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1050;
        }

        .share-modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        .share-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .share-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: inherit;
        }

        .share-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: white;
        }

        .share-text {
            font-size: 12px;
            text-align: center;
        }

        .share-close {
            width: 100%;
            padding: 12px;
            border: none;
            background: var(--orange);
            color: white;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Styles untuk floating button cek status */
        .floating-cek-status {
            position: fixed;
            right: 20px;
            bottom: 80px;
            z-index: 900;
            max-width: 480px;
            margin: 0 auto;
        }
        
        .btn-cek-status {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--orange);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: 2px solid var(--yellow);
        }
        
        .btn-cek-status:hover {
            background-color: var(--dark-blue);
        }
        
        /* Styles untuk info box */
        .upload-info-box {
            background-color: var(--light-turquoise);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--dark-turquoise);
        }
        
        .upload-info-box h6 {
            color: var(--dark-blue);
        }
        
        .btn-info {
            background-color: var(--turquoise) !important;
            border-color: var(--dark-turquoise) !important;
        }
        
        .btn-success {
            background-color: var(--orange) !important;
            border-color: var(--orange) !important;
        }
        
        /* Dropdown menu styles */
        /* Modifikasi dropdown menu */
.dropdown-menu {
    background-color: white;
    border: 1px solid var(--turquoise);
    padding: 8px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dropdown-item {
    color: #000000;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    font-weight: 500;
}

.dropdown-item i {
    margin-right: 10px;
    color: var(--dark-blue);
}

.dropdown-item:hover {
    background-color: var(--light-turquoise);
    color: #000000;
}

/* Tambahkan styling khusus untuk dropdown yang terbuka */
.dropdown-menu.show {
    display: block;
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
        
        /* Modify description section */
        .description h5 {
            color: var(--dark-blue);
            border-bottom: 2px solid var(--yellow);
            padding-bottom: 5px;
            display: inline-block;
        }
        
        .recent-donations h5 {
            color: var(--dark-blue);
            border-bottom: 2px solid var(--yellow);
            padding-bottom: 5px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center p-3">
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
               
                <span class="fw-bold"><?php echo htmlspecialchars($titleDonasi); ?></span>
                <div class="dropdown">
                    <button class="cart-button" id="menuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuDropdown">
                        <li><a class="dropdown-item" href="cek_status_donasi.php">
                            <i class="fas fa-check-circle me-2"></i>Cek Status Donasi
                        </a></li>
                        <!--<li><a class="dropdown-item" href="upload_bukti_bayar.php">-->
                        <!--    <i class="fas fa-upload me-2"></i>Upload Bukti Transfer-->
                        <!--</a></li>-->
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Slider -->
            <div class="slider-container">
                <div class="slider">
                    <?php foreach ($sliderImages as $index => $image): ?>
                        <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="<?php echo htmlspecialchars($image['alt_text']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="slider-dots">
                    <?php for ($i = 0; $i < count($sliderImages); $i++): ?>
                        <div class="dot <?php echo $i === 0 ? 'active' : ''; ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Donation Info yang Diperbarui -->
            <div class="donation-info">
                <h4><?php echo htmlspecialchars($titleDonasi); ?></h4>
                <h3 class="total-donasi mb-2"><?php echo formatRupiah($totalDonasi); ?></h3>
                <p class="text-muted">Target Donasi <?php echo formatRupiah($targetDonasi['jumlah']); ?></p>
                <div class="progress donation-progress">
                    <div class="progress-bar" style="width: <?php echo $persentase; ?>%"></div>
                </div>

                <div class="donation-stats">
                    <div>
                        <span class="fw-bold"><?php echo $jumlahDonatur; ?></span>
                        <span class="text-muted ms-1">Donasi</span>
                    </div>
                    <div>
                        <span class="fw-bold"><?php echo $sisaHari; ?></span>
                        <span class="text-muted ms-1">Hari</span>
                    </div>
                </div>
                
                <!-- Tambahkan info box untuk cek status dan upload bukti -->
                <div class="upload-info-box">
                    <h6><i class="fas fa-info-circle me-2"></i>Informasi</h6>
                    <p class="mb-2">Sudah berdonasi tapi belum upload bukti transfer? Kunjungi halaman cek status donasi untuk mengupload bukti transfer.</p>
                    <a href="cek_status_donasi.php" class="btn btn-sm btn-info text-white">
                        <i class="fas fa-upload me-1"></i> Cek Status & Upload Bukti
                    </a>
                </div>

                <div class="description mb-4">
                    <h5>Deskripsi</h5>
                    <?php if (!empty($targetDonasi['deskripsi'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($targetDonasi['deskripsi'])); ?></p>
                    <?php else: ?>
                        <p>Ramadhan adalah bulan penuh berkah, di mana setiap kebaikan dilipatgandakan.</p>
                        <p>Dalam indahnya nuansa kebersamaan, program Ifthar Ramadhan hadir untuk mengajak Sahabat berbagi kebahagiaan melalui hidangan berbuka puasa.</p>
                    <?php endif; ?>
                </div>

                <!-- Section Program Lainnya -->
                <?php if (!empty($otherPrograms)): ?>
                <div class="other-programs mb-4">
                    <h5 class="mb-3" style="color: var(--dark-blue); border-bottom: 2px solid var(--yellow); display: inline-block; padding-bottom: 5px;">Program Lainnya</h5>
                    <div class="d-flex overflow-auto pb-2" style="gap: 15px;">
                        <?php foreach ($otherPrograms as $op): ?>
                        <a href="donasi.php?id=<?= $op['id'] ?>" class="text-decoration-none text-dark" style="min-width: 140px; width: 140px;">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="<?= !empty($op['gambar_utama']) ? htmlspecialchars($op['gambar_utama']) : 'img/donasi/default.jpg' ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($op['judul']) ?>" 
                                     style="height: 100px; object-fit: cover; border-radius: 8px 8px 0 0;">
                                <div class="card-body p-2">
                                    <h6 class="card-title small mb-0 fw-bold" style="font-size: 0.9rem; line-height: 1.2;"><?= htmlspecialchars($op['judul']) ?></h6>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="recent-donations">
                    <h5>Donasi Terbaru</h5>
                    <?php if (count($donasi_terbaru) > 0): ?>
                        <?php foreach ($donasi_terbaru as $donasi): ?>
                            <div class="donor-item">
                                <h6><?php echo ($donasi['is_anonim'] == 1) ? 'Hamba Allah' : htmlspecialchars($donasi['nama_donatur']); ?></h6>
                                <p class="text-muted mb-1">Berdonasi Sebesar Rp <?php echo number_format($donasi['nominal'], 0, ',', '.'); ?></p>
                                <small class="text-muted"><?php echo time_elapsed_string($donasi['created_at']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="donor-item">
                            <p class="text-muted">Belum ada donasi</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="d-flex justify-content-between align-items-center">
                <button class="share-button">
                    <i class="fas fa-share-alt me-2"></i> Bagikan
                </button>
                <a href="donasi_form.php?id=<?= $programId ?>" class="btn btn-success btn-lg">Donasi Sekarang</a>
            </div>
        </div>
        
        <!-- Floating Button Cek Status Donasi -->
        <div class="floating-cek-status">
            <a href="cek_status_donasi.php" class="btn-cek-status" title="Cek Status Donasi">
                <i class="fas fa-file-invoice"></i>
            </a>
        </div>
    </div>

    <!-- Tambahkan modal share sebelum penutup body -->
    <div class="share-modal-backdrop"></div>
    <div class="share-modal">
        <h5 class="text-center mb-3">Bagikan</h5>
        <div class="share-options">
            <a href="#" class="share-option copy-link">
                <div class="share-icon" style="background: var(--dark-blue);">
                    <i class="fas fa-link"></i>
                </div>
                <span class="share-text">Copy Link</span>
            </a>
            <a href="#" class="share-option">
                <div class="share-icon" style="background: var(--turquoise);">
                    <i class="fab fa-twitter"></i>
                </div>
                <span class="share-text">Twitter</span>
            </a>
            <a href="#" class="share-option">
                <div class="share-icon" style="background: var(--dark-blue);">
                    <i class="fab fa-facebook-f"></i>
                </div>
                <span class="share-text">Facebook</span>
            </a>
            <a href="#" class="share-option">
                <div class="share-icon" style="background: var(--turquoise);">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="share-text">Whatsapp</span>
            </a>
            <a href="#" class="share-option">
                <div class="share-icon" style="background: var(--dark-blue);">
                    <i class="fab fa-telegram-plane"></i>
                </div>
                <span class="share-text">Telegram</span>
            </a>
        </div>
        <button class="share-close">Close</button>
    </div>

    <!-- Script untuk slider dengan durasi 1 detik -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.slide');
            const dots = document.querySelectorAll('.dot');
            let currentSlide = 0;
            let touchStartX = 0;
            let touchEndX = 0;

            // Fungsi untuk menampilkan slide
            function showSlide(index) {
                // Pastikan ada slide sebelum mencoba mengubah
                if (slides.length === 0) return;
                
                // Reset current slide
                slides[currentSlide].classList.remove('active');
                if (dots.length > currentSlide) {
                    dots[currentSlide].classList.remove('active');
                }

                // Update current slide
                currentSlide = index;
                if (currentSlide >= slides.length) currentSlide = 0;
                if (currentSlide < 0) currentSlide = slides.length - 1;

                // Show new slide
                slides[currentSlide].classList.add('active');
                if (dots.length > currentSlide) {
                    dots[currentSlide].classList.add('active');
                }
            }

            // Event listeners untuk dots
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => showSlide(index));
            });

            // Touch events untuk swipe
            const sliderContainer = document.querySelector('.slider-container');
            
            sliderContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            });

            sliderContainer.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].clientX;
                handleSwipe();
            });

            function handleSwipe() {
                const swipeThreshold = 50;
                const difference = touchStartX - touchEndX;

                if (Math.abs(difference) > swipeThreshold) {
                    if (difference > 0) {
                        // Swipe left
                        showSlide(currentSlide + 1);
                    } else {
                        // Swipe right
                        showSlide(currentSlide - 1);
                    }
                }
            }

            // Auto slide setiap 1 detik
            setInterval(() => {
                showSlide(currentSlide + 1);
            }, 3000); // Durasi 1 detik
        });
    </script>

    <!-- Share functionality scripts -->
    <script>
        // Share functionality
        const shareButton = document.querySelector('.share-button');
        const shareModal = document.querySelector('.share-modal');
        const shareModalBackdrop = document.querySelector('.share-modal-backdrop');
        const shareCloseButton = document.querySelector('.share-close');
        const copyLinkButton = document.querySelector('.copy-link');

        function toggleShareModal() {
            shareModal.classList.toggle('show');
            shareModalBackdrop.classList.toggle('show');
        }

        shareButton.addEventListener('click', toggleShareModal);
        shareModalBackdrop.addEventListener('click', toggleShareModal);
        shareCloseButton.addEventListener('click', toggleShareModal);

        // Copy link functionality
        copyLinkButton.addEventListener('click', (e) => {
            e.preventDefault();
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
        });

        // Social media share links
        const shareLinks = {
            twitter: (url) => `https://twitter.com/intent/tweet?url=${url}`,
            facebook: (url) => `https://www.facebook.com/sharer/sharer.php?u=${url}`,
            whatsapp: (url) => `https://wa.me/?text=${url}`,
            telegram: (url) => `https://t.me/share/url?url=${url}`
        };

        // Add click handlers for social media buttons
        document.querySelectorAll('.share-option').forEach(button => {
            if (!button.classList.contains('copy-link')) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const platform = button.querySelector('.share-text').textContent.toLowerCase();
                    const url = encodeURIComponent(window.location.href);
                    if (shareLinks[platform]) {
                        window.open(shareLinks[platform](url), '_blank');
                    }
                });
            }
        });
    </script>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>