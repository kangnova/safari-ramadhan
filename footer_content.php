    <footer style="background-color: #20B2AA; color: white; padding: 3rem 0;">
        <div id="kontak" style="max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; padding: 0 5%;">
            <?php
require_once 'koneksi.php';

// Ambil data profil
try {
    $query = "SELECT * FROM profil ORDER BY tgl_update DESC LIMIT 1";
    $stmt = $conn->query($query);
    $profil = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Terjadi kesalahan: " . $e->getMessage();
}
?>

<div class="footer-col">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">
        <?= htmlspecialchars($profil['judul'] ?? 'Tentang Safari Ramadhan') ?>
    </h3>
    <p style="line-height: 1.6; font-size: 0.9rem;">
        <?= nl2br(htmlspecialchars($profil['deskripsi'] ?? 'Informasi profil belum tersedia.')) ?>
    </p>
</div>
            
<div class="footer-col">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Kontak GNB</h3>
    <p style="line-height: 1.6; font-size: 0.9rem;">
        <?= nl2br(htmlspecialchars($profil['alamat'] ?? '')) ?><br><br>
        <?php if (!empty($profil['whatsapp'])): ?>
            WhatsApp: <a href="https://wa.me/<?= htmlspecialchars($profil['whatsapp']) ?>" 
                        style="color: inherit; text-decoration: none;">
                <?= substr_replace(htmlspecialchars($profil['whatsapp']), "-", 4, 0) ?>
            </a><br>
        <?php endif; ?>
        <?php if (!empty($profil['email'])): ?>
            Email: <a href="mailto:<?= htmlspecialchars($profil['email']) ?>" 
                     style="color: inherit; text-decoration: none;">
                <?= htmlspecialchars($profil['email']) ?>
            </a>
        <?php endif; ?>
    </p>
</div>
            
<div class="footer-col">
    <h3 style="margin-bottom: 1.5rem; font-size: 1.2rem;">Sosial Media</h3>
    <div class="social-links">
        <?php if (!empty($profil['facebook'])): ?>
            <a href="<?= strpos($profil['facebook'], 'http') === 0 ? htmlspecialchars($profil['facebook']) : 'https://facebook.com/' . htmlspecialchars($profil['facebook']) ?>" 
               target="_blank" class="social-icon fb">
                <i class='bx bxl-facebook'></i>
            </a>
        <?php endif; ?>

        <?php if (!empty($profil['twitter'])): ?>
            <a href="<?= strpos($profil['twitter'], 'http') === 0 ? htmlspecialchars($profil['twitter']) : 'https://twitter.com/' . htmlspecialchars($profil['twitter']) ?>" 
               target="_blank" class="social-icon twitter">
                <i class='bx bxl-twitter'></i>
            </a>
        <?php endif; ?>

        <?php if (!empty($profil['instagram'])): ?>
            <a href="<?= strpos($profil['instagram'], 'http') === 0 ? htmlspecialchars($profil['instagram']) : 'https://instagram.com/' . htmlspecialchars($profil['instagram']) ?>" 
               target="_blank" class="social-icon ig">
                <i class='bx bxl-instagram'></i>
            </a>
        <?php endif; ?>

        <?php if (!empty($profil['youtube'])): ?>
            <a href="<?= strpos($profil['youtube'], 'http') === 0 ? htmlspecialchars($profil['youtube']) : 'https://youtube.com/' . htmlspecialchars($profil['youtube']) ?>" 
               target="_blank" class="social-icon yt">
                <i class='bx bxl-youtube'></i>
            </a>
        <?php endif; ?>

        <?php if (!empty($profil['tiktok'])): ?>
            <a href="<?= strpos($profil['tiktok'], 'http') === 0 ? htmlspecialchars($profil['tiktok']) : 'https://tiktok.com/@' . htmlspecialchars($profil['tiktok']) ?>" 
               target="_blank" class="social-icon tt">
                <i class='bx bxl-tiktok'></i>
            </a>
        <?php endif; ?>
    </div>
    <!-- Tambahkan Statistik Pengunjung -->
    <?php
    // Ambil statistik pengunjung
    try {
        $query = "SELECT SUM(jumlah_kunjungan) as total_kunjungan, 
                 COUNT(DISTINCT DATE(last_visit)) as total_hari,
                 COUNT(DISTINCT halaman) as total_halaman
                 FROM statistik_pengunjung";
        $stmt = $conn->query($query);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Ambil kunjungan hari ini
        $query = "SELECT SUM(jumlah_kunjungan) as kunjungan_hari_ini 
                 FROM statistik_pengunjung 
                 WHERE DATE(last_visit) = CURDATE()";
        $stmt = $conn->query($query);
        $today = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
    ?>
    
    <div class="visitor-stats">
        <h4 style="font-size: 1rem; margin-bottom: 0.8rem;">Statistik Pengunjung</h4>
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                <span>Hari ini</span>
                <span><?= number_format($today['kunjungan_hari_ini'] ?? 0) ?></span>
            </li>
            <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                <span>Total Kunjungan</span>
                <span><?= number_format($stats['total_kunjungan'] ?? 0) ?></span>
            </li>
            <li style="margin-bottom: 0.5rem; display: flex; justify-content: space-between;">
                <span>Total Hari</span>
                <span><?= number_format($stats['total_hari'] ?? 0) ?></span>
            </li>
        </ul>
    </div>
</div>
</div>
        
        <div style="text-align: center; padding-top: 2rem; margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem;">
            2025 - Yayasan GNB
        </div>
    </footer>
