<?php
// File: file_cek_status_bayar.php
// Koneksi ke database
require_once 'koneksi.php';

// Mulai session
session_start();

$token = '';
$donasi = null;
$error_message = '';
$success = false;

// Jika form sudah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_token'])) {
    $token = htmlspecialchars($_POST['token']);
    
    // Validasi token
    if (empty($token)) {
        $error_message = "Silakan masukkan token donasi Anda.";
    } else {
        // Cari donasi berdasarkan token
        try {
            $stmt = $conn->prepare("SELECT * FROM donasi WHERE token = ?");
            $stmt->execute([$token]);
            $donasi = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donasi) {
                $error_message = "Token donasi tidak ditemukan. Silakan periksa kembali.";
            } else {
                $success = true;
            }
        } catch (PDOException $e) {
            error_log("Error fetching donation data: " . $e->getMessage());
            $error_message = "Terjadi kesalahan saat mengambil data. Silakan coba lagi.";
        }
    }
}

// Format nominal donasi untuk ditampilkan
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Fungsi untuk menampilkan status dalam format yang mudah dibaca
function getStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark">Menunggu Konfirmasi</span>';
        case 'success':
            return '<span class="badge bg-success">Berhasil</span>';
        case 'failed':
            return '<span class="badge bg-danger">Gagal</span>';
        default:
            return '<span class="badge bg-secondary">Belum Diproses</span>';
    }
}

// Fungsi untuk menampilkan waktu yang mudah dibaca
function formatWaktu($datetime) {
    $time = new DateTime($datetime);
    return $time->format('d M Y, H:i') . ' WIB';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Donasi - Ifthar Ramadhan</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            position: sticky;
            top: 0;
            width: 100%;
            max-width: 480px;
            z-index: 1000;
            background: var(--dark-blue);
            color: white;
            border-bottom: 1px solid var(--turquoise);
        }
        
        .header a, .header button, .header span {
            color: white;
        }

        .content {
            padding: 20px;
            margin-bottom: 30px;
        }

        /* Button styles */
        .back-button {
            border: none;
            background: none;
            padding: 10px;
            color: white;
            text-decoration: none;
        }

        /* Content styling */
        h4 {
            color: var(--dark-blue);
            margin-top: 20px;
        }
        
        .btn-primary {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
        }
        
        .btn-primary:hover {
            background-color: #00254d;
            border-color: #00254d;
        }
        
        .btn-success {
            background-color: var(--orange) !important;
            border-color: var(--orange) !important;
        }
        
        .btn-success:hover {
            background-color: #e67a00 !important;
            border-color: #e67a00 !important;
        }
        
        .btn-outline-success {
            color: var(--orange);
            border-color: var(--orange);
        }
        
        .btn-outline-success:hover {
            background-color: var(--orange);
            border-color: var(--orange);
            color: white;
        }
        
        .btn-outline-secondary {
            color: var(--dark-blue);
            border-color: var(--turquoise);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--turquoise);
            border-color: var(--turquoise);
            color: var(--dark-blue);
        }
        
        .btn-outline-primary {
            color: var(--dark-blue);
            border-color: var(--dark-blue);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
        }
        
        .form-control:focus {
            border-color: var(--turquoise);
            box-shadow: 0 0 0 0.25rem rgba(64, 224, 208, 0.25);
        }
        
        /* Donation card styles */
        .donation-card {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .token-display {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-bottom: 15px;
            word-break: break-all;
            border-left: 3px solid var(--turquoise);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6c757d;
        }
        
        .donation-card h5 {
            color: var(--dark-blue);
            border-bottom: 2px solid var(--yellow);
            padding-bottom: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        /* Alert styling */
        .alert-success {
            background-color: rgba(255, 215, 0, 0.2);
            border-color: var(--yellow);
            color: #856404;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
        }
        
        /* Badge styling */
        .badge.bg-success {
            background-color: var(--orange) !important;
        }
        
        .badge.bg-warning {
            background-color: var(--yellow) !important;
        }
        
        /* Responsive adjustments */
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
        }
        
        @media (max-width: 576px) {
            .content {
                padding: 15px;
            }
            
            .donation-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex justify-content-between align-items-center p-3">
                <a href="donasi.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <span class="fw-bold">Cek Status Donasi</span>
                <div style="width: 24px;"><!-- Elemen kosong untuk keseimbangan --></div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="mb-4">
                <h4 class="mb-3">Cek Status Donasi</h4>
                <p>Masukkan token donasi untuk melihat status donasi Anda.</p>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="token" class="form-label">Token Donasi</label>
                        <input type="text" class="form-control" id="token" name="token" 
                               placeholder="Contoh: INV-TRX-20250221678587" 
                               value="<?php echo htmlspecialchars($token); ?>" required>
                        <div class="form-text">
                            Token donasi dapat ditemukan di email konfirmasi atau riwayat donasi Anda.
                        </div>
                    </div>
                    <button type="submit" name="cek_token" class="btn btn-primary w-100">Cek Status</button>
                </form>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success && $donasi): ?>
                <div class="donation-card">
                    <h5 class="mb-3">Informasi Donasi</h5>
                    
                    <div class="token-display">
                        <?php echo htmlspecialchars($donasi['token']); ?>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Status</div>
                        <div><?php echo getStatusLabel($donasi['status']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Donatur</div>
                        <div>
                            <?php echo $donasi['is_anonim'] ? 'Anonim' : htmlspecialchars($donasi['nama_donatur']); ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Nominal</div>
                        <div class="fw-bold"><?php echo formatRupiah($donasi['nominal']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Tanggal</div>
                        <div><?php echo formatWaktu($donasi['created_at']); ?></div>
                    </div>
                    
                    <?php if ($donasi['bukti_transfer']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Bukti Transfer</div>
                            <div>
                                <a href="img/bukti_transfer/<?php echo htmlspecialchars($donasi['bukti_transfer']); ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> Lihat Bukti
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="detail-row">
                            <div class="detail-label">Bukti Transfer</div>
                            <div>
                                <span class="text-danger">
                                    <i class="fas fa-times-circle me-1"></i> Belum diupload
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <?php if ($donasi['status'] !== 'success' && !$donasi['bukti_transfer']): ?>
                            <a href="upload_bukti_bayar.php?token=<?php echo $donasi['token']; ?>" 
                               class="btn btn-success w-100">
                                <i class="fas fa-upload me-2"></i> Upload Bukti Transfer
                            </a>
                        <?php elseif ($donasi['status'] !== 'success'): ?>
                            <a href="upload_bukti_kemudian.php?token=<?php echo $donasi['token']; ?>" 
                               class="btn btn-outline-success w-100">
                                <i class="fas fa-sync-alt me-2"></i> Update Bukti Transfer
                            </a>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                Donasi Anda telah berhasil diverifikasi. Terima kasih atas kontribusi Anda!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="donasi.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>