<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav class="navbar">
    <div class="logo">
        <a href="index.php">
            <img src="img/logo.png" alt="GURU NGAJI BERDAYA" style="height: 40px;">
        </a>
    </div>

    <div class="hamburger">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <ul class="nav-links">
        <li><a href="index.php" <?= $current_page == 'index' ? 'class="active"' : '' ?>>Home</a></li>
        <li><a href="index.php#program" <?= $current_page == 'program' ? 'class="active"' : '' ?>>Program</a></li>
        <li><a href="index.php#gallery" <?= $current_page == 'gallery' ? 'class="active"' : '' ?>>Gallery</a></li>
        <li><a href="index.php#berita" <?= $current_page == 'berita' ? 'class="active"' : '' ?>>Berita</a></li>
        <li><a href="index.php#kontak" <?= $current_page == 'kontak' ? 'class="active"' : '' ?>>Kontak</a></li>
        <li class="cta-button"><a href="jadwal_safariramadhan.php" class="donate-btn" <?= $current_page == 'jadwal_safariramadhan' ? 'class="active"' : '' ?>>Jadwal Safari</a></li>
        <li class="cta-button"><a href="form.php" class="donate-btn daftar-online">Daftar Online</a></li>
        <li class="cta-button"><a href="donasi.php" class="donate-btn donasi">Donasi</a></li>
    </ul>
</nav>

<script>
// Hamburger Menu Functionality
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

// Close menu when clicking a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        document.querySelector('.hamburger').classList.remove('active');
        document.querySelector('.nav-links').classList.remove('active');
    });
});
</script>