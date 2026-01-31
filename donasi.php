<?php
// Koneksi database
require_once 'koneksi.php';
$pdo = $conn;

// Ambil ID program dari query string
$programId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fungsi-fungsi helper (sama seperti sebelumnya, plus fungsi baru untuk list)
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

function hitungSisaHari($tanggalSelesai) {
    $sekarang = new DateTime();
    $selesai = new DateTime($tanggalSelesai);
    if ($selesai < $sekarang) return 0;
    $selisih = $sekarang->diff($selesai);
    return $selisih->days;
}

function getTotalDonasi($pdo, $programId) {
    try {
        if ($programId) {
            $stmt = $pdo->prepare("SELECT SUM(nominal) as total FROM donasi WHERE status = 'success' AND program_id = ?");
            $stmt->execute([$programId]);
        } else {
            // Jika ID null (global), mungkin hitung semua donasi? Opsional, tapi di sini kita butuh per program
            return 0; 
        }
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// === LOGIKA TAMPILAN ===
if ($programId) {
    // --- MODE DETAIL ---
    // Logika lama dipindahkan ke sini
    
    // Ambil Data Program Utama
    $stmt = $pdo->prepare("SELECT * FROM program_donasi WHERE id = ?");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fallback jika ID tidak valid -> Redirect atau Tampilkan Error? 
    // Untuk user experience, jika ID salah, kita redirect ke halaman List saja
    if (!$program) {
        header("Location: donasi.php");
        exit;
    }
    
    // Setup Data Detail
    $titleDonasi = $program['judul'];
    $targetDonasi = [
        'jumlah' => $program['target_nominal'],
        'deskripsi' => $program['deskripsi'],
        'tanggal_selesai' => $program['tanggal_selesai']
    ];

    $totalDonasi = getTotalDonasi($pdo, $programId);
    $sisaHari = hitungSisaHari($targetDonasi['tanggal_selesai']);
    $persentase = ($targetDonasi['jumlah'] > 0) ? min(100, ($totalDonasi / $targetDonasi['jumlah']) * 100) : 0;
    
    // Slider Images
    $sliderImages = [];
    
    // 1. Gambar Utama Program (Selalu jadi slide pertama jika ada)
    if (!empty($program['gambar_utama']) && file_exists($program['gambar_utama'])) {
        $sliderImages[] = ['image_path' => $program['gambar_utama'], 'alt_text' => $program['judul']];
    } else {
        // Fallback jika tidak ada gambar utama, pakai default
        if (empty($sliderImages)) {
            $sliderImages[] = ['image_path' => 'img/donasi/default.jpg', 'alt_text' => 'Default'];
        }
    }

    // 2. Gambar Tambahan dari tabel slider_images
    try {
        $stmtSlide = $pdo->prepare("SELECT image_path, alt_text FROM slider_images WHERE campaign_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmtSlide->execute([$programId]);
        $additionalSlides = $stmtSlide->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($additionalSlides as $slide) {
            if (file_exists($slide['image_path'])) {
                $sliderImages[] = $slide;
            }
        }
    } catch (PDOException $e) {
        // Ignore error
    }

    // Donasi Terbaru
    $stmt = $pdo->prepare("SELECT nama_donatur, nominal, is_anonim, created_at FROM donasi WHERE status = 'success' AND program_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$programId]);
    $donasi_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper Time Elapsed
    function time_elapsed_string($datetime) {
        $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $ago = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
        $diff = $now->diff($ago);
        $total_minutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
        if ($diff->y > 0) return $diff->y . ' tahun yang lalu';
        if ($diff->m > 0) return $diff->m . ' bulan yang lalu';
        if ($diff->d > 0) return $diff->d . ' hari yang lalu';
        if ($diff->h > 0) return $diff->h . ' jam yang lalu';
        if ($diff->i > 0) return $diff->i . ' menit yang lalu';
        return 'baru saja';
    }

    $jumlahDonatur = count($donasi_terbaru); // Ini cuma 5 terbaru, harusnya count total
    // Fix count total
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM donasi WHERE status = 'success' AND program_id = ?");
    $stmtCount->execute([$programId]);
    $jumlahDonatur = $stmtCount->fetchColumn();

} else {
    // --- MODE LIST (DAFTAR PROGRAM) ---
    $titleDonasi = "Daftar Program Donasi";
    
    // Ambil semua program aktif
    $stmt = $pdo->query("SELECT * FROM program_donasi WHERE status = 'active' ORDER BY created_at DESC");
    $listProgram = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Siapkan data tambahan untuk setiap program (progress bar dll)
    foreach ($listProgram as &$p) {
        $p['terkumpul'] = getTotalDonasi($pdo, $p['id']);
        $p['persen'] = ($p['target_nominal'] > 0) ? min(100, ($p['terkumpul'] / $p['target_nominal']) * 100) : 0;
        $p['sisa_hari'] = hitungSisaHari($p['tanggal_selesai']);
        $p['gambar'] = (!empty($p['gambar_utama']) && file_exists($p['gambar_utama'])) ? $p['gambar_utama'] : 'img/donasi/default.jpg';
    }
    unset($p); // break reference
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titleDonasi) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --turquoise: #40E0D0;
            --dark-blue: #003366;
            --orange: #FF8800;
            --yellow: #FFD700;
            --light-turquoise: #AFFBFB;
        }
        body { background-color: var(--light-turquoise); }
        .page-container {
            max-width: 480px; margin: 0 auto; position: relative; min-height: 100vh;
            background: white; box-shadow: 0 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;
        }
        .header {
            background: var(--dark-blue); color: white; padding: 15px;
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid var(--turquoise);
        }
        .header a { color: white; text-decoration: none; }
        .content { flex: 1; padding-bottom: 70px; }
        
        /* List Mode Styles */
        .program-card {
            border: none; border-radius: 10px; overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .program-card:active { transform: scale(0.98); }
        .program-img { height: 160px; object-fit: cover; width: 100%; }
        .program-body { padding: 15px; }
        .program-title { font-size: 1.1rem; font-weight: bold; color: var(--dark-blue); margin-bottom: 10px; }
        .progress { height: 8px; border-radius: 4px; background-color: #eee; margin-bottom: 8px; }
        .progress-bar { background-color: var(--orange); }
        .program-stats { display: flex; justify-content: space-between; font-size: 0.85rem; color: #666; }
        .btn-donate-sm { 
            background: var(--orange); color: white; border: none; width: 100%; 
            padding: 8px; border-radius: 5px; margin-top: 10px; font-weight: 600; 
        }

        /* Detail Mode Styles (Ported) */
        .slider-container { height: 250px; position: relative; overflow: hidden; }
        .slide { position: absolute; width: 100%; height: 100%; opacity: 0; transition: opacity 0.5s; }
        .slide.active { opacity: 1; }
        .slide img { width: 100%; height: 100%; object-fit: cover; }
        .donation-info { padding: 20px; }
        .total-donasi { color: var(--orange); font-weight: bold; }
        .footer-action {
            position: fixed; bottom: 0; width: 100%; max-width: 480px;
            background: white; padding: 15px; border-top: 1px solid #eee;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .btn-donate-lg { width: 100%; background: var(--orange); color: white; padding: 12px; border-radius: 8px; font-weight: bold; border: none; }
        
        .empty-state { text-align: center; padding: 50px 20px; color: #888; }
        .empty-icon { font-size: 50px; color: #ddd; margin-bottom: 20px; }

        @media (max-width: 480px) {
            .slider-container {
                height: auto;
                aspect-ratio: 16/9;
            }
            .slide img {
                object-fit: contain;
                background-color: #f8f9fa; /* Optional background for non-matching ratios */
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center">
            <?php if ($programId): ?>
                <a href="donasi.php"><i class="fas fa-arrow-left me-2"></i> Kembali</a>
            <?php else: ?>
                <a href="index.php"><i class="fas fa-home me-2"></i> Home</a>
            <?php endif; ?>
            <span class="fw-bold"><?= htmlspecialchars($titleDonasi) ?></span>
            <div style="width: 24px;"></div> <!-- Spacer for balance -->
        </div>

        <div class="content">
            <?php if ($programId): ?>
                <!-- === DETAIL VIEW === -->
                <div class="slider-container">
                    <?php foreach ($sliderImages as $idx => $img): ?>
                        <div class="slide <?= $idx == 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="Slide">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="donation-info">
                    <h1 class="h4 mb-3"><?= htmlspecialchars($program['judul']) ?></h1>
                    
                    <h2 class="total-donasi h3 mb-0"><?= formatRupiah($totalDonasi) ?></h2>
                    <small class="text-muted d-block mb-3">Terkumpul dari target <?= formatRupiah($targetDonasi['jumlah']) ?></small>
                    
                    <div class="progress mb-2">
                        <div class="progress-bar" style="width: <?= $persentase ?>%"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between text-muted small mb-4">
                        <span><strong><?= $jumlahDonatur ?></strong> Donatur</span>
                        <span><strong><?= $sisaHari ?></strong> Hari Lagi</span>
                    </div>
                    
                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="card-title text-dark"><i class="fas fa-info-circle me-1"></i> Deskripsi Program</h6>
                            <p class="card-text small text-secondary mb-0">
                                <?= nl2br(htmlspecialchars($targetDonasi['deskripsi'])) ?: 'Belum ada deskripsi.' ?>
                            </p>
                        </div>
                    </div>
                    
                    <h5 class="border-bottom pb-2 mb-3">Doa & Dukungan Terbaru</h5>
                    <?php if (count($donasi_terbaru) > 0): ?>
                        <?php foreach ($donasi_terbaru as $d): ?>
                            <div class="mb-3 border-start border-3 border-info ps-3">
                                <div class="fw-bold"><?= $d['is_anonim'] ? 'Hamba Allah' : htmlspecialchars($d['nama_donatur']) ?></div>
                                <div class="small text-muted">
                                    Berdonasi <?= formatRupiah($d['nominal']) ?> • <?= time_elapsed_string($d['created_at']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small fst-italic">Belum ada donasi. Jadilah yang pertama!</p>
                    <?php endif; ?>
                </div>
                
                <div class="footer-action">
                    <a href="donasi_form.php?id=<?= $programId ?>" class="btn-donate-lg text-decoration-none text-center d-block">
                        Donasi Sekarang
                    </a>
                </div>

            <?php else: ?>
                <!-- === LIST VIEW === -->
                <div class="p-3">
                    <?php if (count($listProgram) > 0): ?>
                        <?php foreach ($listProgram as $p): ?>
                            <a href="donasi.php?id=<?= $p['id'] ?>" class="text-decoration-none">
                                <div class="program-card bg-white">
                                    <img src="<?= htmlspecialchars($p['gambar']) ?>" class="program-img" alt="Program">
                                    <div class="program-body">
                                        <div class="program-title text-truncate"><?= htmlspecialchars($p['judul']) ?></div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $p['persen'] ?>%"></div>
                                        </div>
                                        <div class="program-stats">
                                            <span>Terkumpul <strong><?= formatRupiah($p['terkumpul']) ?></strong></span>
                                            <span>Sisa <strong><?= $p['sisa_hari'] ?></strong> Hari</span>
                                        </div>
                                        <button class="btn-donate-sm mt-3">Donasi Sekarang</button>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open empty-icon"></i>
                            <h5>Belum ada program donasi</h5>
                            <p>Mohon maaf, saat ini belum ada program donasi yang tersedia.</p>
                            <a href="index.php" class="btn btn-outline-primary btn-sm mt-3">Kembali ke Beranda</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple slider auto-play
        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.slide');
            let current = 0;
            if(slides.length > 1) {
                setInterval(() => {
                    slides[current].classList.remove('active');
                    current = (current + 1) % slides.length;
                    slides[current].classList.add('active');
                }, 3000);
            }
        });
    </script>
</body>
</html>