<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Meta Tags -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Safari Ramadhan 1446 H/2025 merupakan program dakwah Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah di berbagai TPQ Kota Klaten untuk memberikan pendidikan melalui kisah-kisah inspiratif.">
<meta property="og:title" content="Safari Ramadhan 1446 H/2025">
<meta property="og:description" content="Safari Ramadhan adalah program dakwah rutin Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah untuk menghadirkan kajian dan motivasi di TPQ Kota Klaten selama bulan Ramadhan (1-26 Maret 2025). Program ini memberikan pendidikan melalui kisah-kisah inspiratif kepada santri TPQ.">
<meta property="og:image" content="https://gnb.or.id/safariramadhan/img/img1.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:url" content="https://gnb.or.id/safariramadhan/">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Safari Ramadhan 1446 H/2025">
<meta name="twitter:description" content="Safari Ramadhan adalah program dakwah rutin Yayasan Guru Ngaji Berdaya bekerjasama dengan para pengkisah untuk menghadirkan kajian dan motivasi di TPQ Kota Klaten selama bulan Ramadhan (1-26 Maret 2025). Program ini memberikan pendidikan melalui kisah-kisah inspiratif kepada santri TPQ.">
<meta name="twitter:image" content="https://gnb.or.id/safariramadhan/img/img1.jpg">


<title>Safari Ramadhan 1446 H/2025</title>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const galleryData = {
                'palestina': [
                    { img: 'img/gbr4.jpeg', title: 'Bantuan Makanan Palestina' },
                    { img: 'img/gbr2.jpg', title: 'Distribusi Bantuan' },
                    { img: 'img/gbr3.jpg', title: 'Program Bantuan' }
                ],
                'ambulance': [
                    { img: 'img/gbr2.jpg', title: 'Ambulance Gratis' },
                    { img: 'img/gbr4.jpeg', title: 'Layanan 24 Jam' },
                    { img: 'img/gbr3.jpg', title: 'Mobil Jenazah' }
                ],
                'modal': [
                    { img: 'img/gbr3.jpg', title: 'Modal Usaha' },
                    { img: 'img/gbr2.jpg', title: 'Pemberdayaan UMKM' },
                    { img: 'img/gbr4.jpeg', title: 'Bantuan Wirausaha' }
                ]
            };

            const tabs = document.querySelectorAll('.gallery-tab');
            const galleryContainer = document.querySelector('.gallery-container');

            function createGalleryItem(data) {
                return `
                    <div class="gallery-item">
                        <img src="${data.img}" alt="${data.title}" class="gallery-image">
                        <div class="gallery-overlay">
                            <h3>${data.title}</h3>
                        </div>
                    </div>
                `;
            }

            function updateGallery(category) {
                const items = galleryData[category].map(createGalleryItem).join('');
                galleryContainer.innerHTML = items;
                
                // Tambahkan animasi fade
                const galleryItems = document.querySelectorAll('.gallery-item');
                galleryItems.forEach((item, index) => {
                    item.style.opacity = '0';
                    setTimeout(() => {
                        item.style.opacity = '1';
                    }, index * 200);
                });
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Hapus kelas active dari semua tab
                    tabs.forEach(t => t.classList.remove('active'));
                    // Tambah kelas active ke tab yang diklik
                    this.classList.add('active');
                    
                    // Update gallery sesuai kategori
                    const category = this.dataset.category;
                    updateGallery(category);
                });
            });

            // Set tab pertama sebagai active dan tampilkan gallerynya
            const firstCategory = tabs[0].dataset.category;
            tabs[0].classList.add('active');
            updateGallery(firstCategory);
        });
    </script>

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
        }

        .logo img {
            height: 40px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #20B2AA;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
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
            background-color: #FF5252;
        }

        /* Hero Section Styles yang dimodifikasi */
        .hero {
            position: relative;
            height: 600px;
            overflow: hidden;
        }

        .slider {
            position: relative;
            height: 100%;
            display: flex;
            transition: transform 0.5s ease-in-out;
        }

        .slide {
            min-width: 100%;
            height: 100%;
        }

        .slide-content {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .slide-content img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Tombol navigasi */
        .slider-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            transform: translateY(-50%);
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 10;
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.7);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: background-color 0.3s;
        }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.9);
        }

        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background-color: white;
            transform: scale(1.2);
        }

        /* Program Section */
        .program {
            padding: 4rem 5%;
            background-color: #f5f5f5;
        }

        .program-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            color: #20B2AA;
            font-size: 2rem;
            font-weight: bold;
        }

        .program-title::before,
        .program-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30px;
            height: 30px;
            background-color: #48D1CC;
            transform: rotate(45deg);
        }

        .program-title::before {
            left: 35%;
        }

        .program-title::after {
            right: 35%;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-content {
            padding: 1.5rem;
            text-align: center;
        }

        .card-title {
            color: #20B2AA;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .card-button {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: linear-gradient(135deg, #20B2AA, #48D1CC);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            transition: transform 0.3s ease;
        }

        .card-button:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: 1fr;
            }
            
            .program-title::before,
            .program-title::after {
                display: none;
            }
        }
        /* Profile */
        .profile-section {
            padding: 4rem 5%;
            background: linear-gradient(135deg, #ffffff, #f5f5f5);
            color: #20B2AA;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .profile-video {
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .profile-video iframe {
            width: 100%;
            aspect-ratio: 16/9;
            border: none;
        }

        .profile-content {
            padding: 2rem;
        }

        .profile-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: #20B2AA;
        }

        .profile-text {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .profile-btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            background-color: #20B2AA;
            color: #ffffff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }

        .profile-btn:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
        /* Gallery */
        
        .gallery-section {
            padding: 4rem 5%;
            background-color: #f5f5f5;
        }

        .gallery-title {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            color: #20B2AA;
            font-size: 2rem;
        }

        .gallery-title::before,
        .gallery-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30px;
            height: 30px;
            background-color: #48D1CC;
            transform: rotate(45deg);
        }

        .gallery-title::before {
            left: 35%;
        }

        .gallery-title::after {
            right: 35%;
        }

        .gallery-tabs {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .gallery-tab {
            padding: 0.5rem 1.5rem;
            background: transparent;
            border: 2px solid #20B2AA;
            color: #20B2AA;
            cursor: pointer;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .gallery-tab.active {
            background: #20B2AA;
            color: white;
        }

        .gallery-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .gallery-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            height: 250px;
        }

        .gallery-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover .gallery-image {
            transform: scale(1.1);
        }

        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            color: white;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .gallery-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .gallery-tabs {
                flex-direction: column;
                align-items: center;
            }
        }
        .gallery-item {
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        /* Berita */
        
        .news-section {
            padding: 4rem 5%;
            background-color: #f5f5f5;
        }

        .news-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            color: #20B2AA;
            font-size: 2rem;
        }

        .news-title::before,
        .news-title::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30px;
            height: 30px;
            background-color: #48D1CC;
            transform: rotate(45deg);
        }

        .news-title::before {
            left: 35%;
        }

        .news-title::after {
            right: 35%;
        }

        .news-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        @media (max-width: 992px) {
            .news-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .news-container {
                grid-template-columns: 1fr;
            }
        }

        .news-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .news-card:hover {
            transform: translateY(-5px);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .news-content {
            padding: 1.5rem;
        }

        .news-date {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .news-headline {
            color: #333;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            font-weight: bold;
        }

        .news-excerpt {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .news-btn {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            background-color: #20B2AA;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
        }

        .news-btn:hover {
            background-color: #48D1CC;
        }

        @media (max-width: 768px) {
            .news-container {
                grid-template-columns: 1fr;
            }
        }

        
        .sponsor-section {
            padding: 2rem 0;
            background: white;
            overflow: hidden;
        }

        .sponsor-track {
            display: flex;
            animation: scroll 30s linear infinite;
            width: calc(200px * 20);
        }

        .sponsor-item {
            width: 200px;
            padding: 1rem;
            flex-shrink: 0;
        }

        .sponsor-item img {
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }

        .sponsor-item img:hover {
            filter: grayscale(0%);
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-200px * 10));
            }
        }

        .sponsor-container {
            margin: 0 auto;
            overflow: hidden;
            position: relative;
            width: 100%;
        }

        .sponsor-container::before,
        .sponsor-container::after {
            content: "";
            position: absolute;
            top: 0;
            width: 100px;
            height: 100%;
            z-index: 2;
        }

        .sponsor-container::before {
            left: 0;
            background: linear-gradient(to right, white, transparent);
        }

        .sponsor-container::after {
            right: 0;
            background: linear-gradient(to left, white, transparent);
        }
        
        .section-title {
    text-align: center;
    position: relative;
    color: #20B2AA;
    padding: 20px 0;
    font-weight: 600;
    margin-bottom: 30px;
    font-size: 2em;
}

.section-title::before,
.section-title::after {
    content: '◆';
    color: #20B2AA;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}

.section-title::before {
    left: 20%;
}

.section-title::after {
    right: 20%;
}

@media (max-width: 768px) {
    .section-title::before,
    .section-title::after {
        display: none;
    }
}


.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 5%;
    background: white;
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
}

.nav-links a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: #20B2AA;
}

.donate-btn {
    background: #20B2AA;
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.3s;
}

.donate-btn:hover {
    opacity: 0.9;
}

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

    .donate-btn {
        display: none;
    }

    .hamburger.active span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
    }

    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }

    .hamburger.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -7px);
    }
}

/*hero statis*/
@media (max-width: 768px) {
   .hero {
       height: 300px;
   }

   .slider-nav,
   .slider-dots {
       display: none;
   }

   .slider {
       transform: none !important;
   }

   .slide {
       display: none;
   }

   .slide:first-child {
       display: block;
   }

   .slide-content img {
       height: 300px;
       object-fit: cover;
       object-position: center;
   }
}

/*navbar mobile*/
.navbar {
   padding: 0.5rem 3%;
}

.logo img {
   height: 30px;
}

@media (max-width: 768px) {
   .navbar {
       padding: 0.5rem 1rem;
   }
   
   .logo img {
       height: 25px;
   }

   .nav-links {
       top: 47px;
       padding: 0.5rem;
   }

   .nav-links a {
       padding: 0.5rem;
       width: 100%;
       display: block;
       text-align: center;
   }

   .nav-links li {
       width: 100%;
       border-bottom: 1px solid #eee;
   }

   .nav-links li:last-child {
       border: none;
   }

   .hamburger {
       transform: scale(0.8);
   }
}

/*footer mobile*/
@media (max-width: 768px) {
   footer {
       padding: 2rem 0;
   }

   footer > div {
       grid-template-columns: 1fr !important;
       gap: 1.5rem !important;
       padding: 0 1rem !important;
   }

   .footer-col {
       text-align: center;
   }

   .footer-col h3 {
       font-size: 1.1rem !important;
       margin-bottom: 1rem !important;
   }

   .footer-col p {
       font-size: 0.85rem !important;
   }

   .footer-col div {
       justify-content: center;
   }

   .footer-col a {
       width: 30px !important;
       height: 30px !important;
       font-size: 0.8rem !important;
   }
}
    </style>
</head>
<body>
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
        <li><a href="index.html">Home</a></li>
        <li><a href="#program">Program</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#news">Berita</a></li>
        <li><a href="#contact">Kontak</a></li>
    </ul>
    <a href="form.html" class="donate-btn">Daftar Online</a>
</nav>