<?php
$host = 'localhost';
$username = 'gnborid_safariramadhan2025';
$password = 'gnborid_safariramadhan2025';
$database = 'gnborid_safariramadhan2025';

try {
    // Buat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    
    // Set mode error PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode array asosiatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Pastikan koneksi menggunakan UTF-8
    $pdo->exec("SET NAMES utf8");
    
} catch(PDOException $e) {
    // Log error koneksi untuk troubleshooting
    error_log("Koneksi database gagal: " . $e->getMessage());
    
    // Tampilkan pesan user-friendly (tidak menampilkan detail sensitif)
    die("Maaf, terjadi masalah saat menghubungkan ke database. Silakan coba lagi nanti.");
}

session_start();

// Fungsi untuk mendapatkan data donasi berdasarkan token
function getDonasiByToken($token) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM donasi WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching donation data: " . $e->getMessage());
        return false;
    }
}

// Periksa apakah token tersedia di URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redirect ke halaman donasi jika tidak ada token
    header('Location: donasi.php');
    exit;
}

// Ambil token dari URL dan bersihkan
$token = htmlspecialchars($_GET['token']);

// Dapatkan data donasi berdasarkan token
$donasi = getDonasiByToken($token);

// Jika donasi tidak ditemukan, redirect ke halaman donasi
if (!$donasi) {
    header('Location: donasi.php?error=invalid_token');
    exit;
}

// Format nominal donasi untuk ditampilkan
$nominal_display = 'Rp ' . number_format($donasi['nominal'], 0, ',', '.');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terima Kasih - Ifthar Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .page-container {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            position: relative;
            padding-bottom: 80px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .header {
            padding: 15px;
            border-bottom: 1px solid var(--turquoise);
            background: var(--dark-blue);
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .success-icon {
            font-size: 80px;
            color: var(--orange);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .success-icon:hover {
            transform: scale(1.1);
            color: var(--yellow);
        }
        
        .thank-you-container {
            text-align: center;
            padding: 40px 20px;
        }
        
        .thank-you-container h3 {
            color: var(--dark-blue);
            font-weight: 700;
        }
        
        .detail-card {
            background: var(--turquoise);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            color: white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .detail-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .fixed-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 480px;
            margin: 0 auto;
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: var(--orange);
            border-color: var(--orange);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--yellow);
            border-color: var(--yellow);
            color: #333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .badge.bg-warning {
            background-color: var(--yellow) !important;
            color: #333;
        }
        
        /* Tambahan styling untuk responsivitas */
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
            
            .fixed-bottom {
                border-radius: 0 0 15px 15px;
            }
        }
        
        @media (max-width: 576px) {
            .success-icon {
                font-size: 60px;
                margin-bottom: 15px;
            }
            
            .thank-you-container {
                padding: 30px 15px;
            }
            
            .detail-card {
                padding: 15px;
                margin-top: 20px;
            }
            
            .fixed-bottom {
                padding: 10px;
            }
            
            .btn-primary {
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center p-2">
                <div style="width: 24px;"></div>
                <h5 class="mb-0">Terima Kasih</h5>
                <div style="width: 24px;"></div>
            </div>
        </div>

        <!-- Content -->
        <div class="thank-you-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="mb-3">Terima Kasih</h3>
            <p class="text-muted mb-4">
                Donasi Anda telah kami terima. Bukti transfer Anda sedang kami verifikasi.
                Terima kasih atas partisipasi Anda dalam program Ifthar Ramadhan.
            </p>
            
            <div class="detail-card">
                <h5 class="mb-3">Detail Donasi</h5>
                
                <div class="detail-item">
                    <span>Nama</span>
                    <span class="fw-bold"><?php echo $donasi['is_anonim'] ? 'Anonim' : htmlspecialchars($donasi['nama_donatur']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span>Nominal</span>
                    <span class="fw-bold"><?php echo $nominal_display; ?></span>
                </div>
                
                <div class="detail-item">
                    <span>Metode Pembayaran</span>
                    <span class="fw-bold"><?php echo htmlspecialchars($donasi['metode_pembayaran']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span>Status</span>
                    <span class="badge bg-warning">Menunggu Verifikasi</span>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="small text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Status donasi akan diperbarui setelah tim kami melakukan verifikasi.
                </p>
            </div>
        </div>

        <!-- Fixed Bottom -->
        <div class="fixed-bottom">
            <a href="donasi.php" class="btn btn-primary w-100">
                <i class="fas fa-home me-2"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>