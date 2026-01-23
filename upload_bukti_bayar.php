<?php
// File: upload_bukti_kemudian.php
// Koneksi ke database
require_once 'koneksi.php';

// Mulai session
session_start();

// Periksa apakah token tersedia di URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redirect ke halaman donasi jika tidak ada token
    header('Location: donasi.php');
    exit;
}

// Ambil token dari URL dan bersihkan
$token = htmlspecialchars($_GET['token']);

// Fungsi untuk mendapatkan data donasi berdasarkan token
function getDonasiByToken($token) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM donasi WHERE token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching donation data: " . $e->getMessage());
        return false;
    }
}

// Dapatkan data donasi berdasarkan token
$donasi = getDonasiByToken($token);

// Jika donasi tidak ditemukan, redirect ke halaman donasi
if (!$donasi) {
    header('Location: donasi.php?error=invalid_token');
    exit;
}

// Format nominal donasi untuk ditampilkan
$nominal_display = 'Rp ' . number_format($donasi['nominal'], 0, ',', '.');

// Proses form jika ada POST request untuk submit bukti transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bukti'])) {
    // Proses upload bukti transfer
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $filename = $_FILES['bukti_transfer']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Validasi tipe file
        if (in_array(strtolower($filetype), $allowed)) {
            // Buat nama file unik
            $newname = 'bukti_' . $donasi['token'] . '_' . date('YmdHis') . '.' . $filetype;
            
            // Buat direktori img/bukti_transfer jika belum ada
            $upload_dir = 'img/bukti_transfer';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $error_message = "Gagal membuat direktori upload. Silakan hubungi administrator.";
                    error_log("Failed to create directory: $upload_dir");
                }
            }
            
            $target = $upload_dir . '/' . $newname;
            
            // Upload file
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target)) {
                // Update database dengan file bukti transfer
                try {
                    // Gunakan prepared statement untuk update
                    $stmt = $conn->prepare("UPDATE donasi SET bukti_transfer = ?, status = 'pending' WHERE token = ?");
                    $result = $stmt->execute([$newname, $donasi['token']]);
                    
                    if ($result) {
                        // Redirect ke halaman terima kasih
                        header('Location: terima_kasih.php?token=' . $donasi['token']);
                        exit;
                    } else {
                        error_log("Failed to update database. PDO Error: " . implode(", ", $stmt->errorInfo()));
                        $error_message = "Gagal menyimpan data ke database. Silakan coba lagi.";
                    }
                } catch (PDOException $e) {
                    error_log("Database error in bukti_transfer update: " . $e->getMessage());
                    $error_message = "Gagal menyimpan data bukti transfer. Error database.";
                }
            } else {
                error_log("Failed to move uploaded file from temp to target: " . $target);
                $error_message = "Gagal mengupload file. Silakan coba lagi.";
            }
        } else {
            $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.";
        }
    } else {
        $error_code = $_FILES['bukti_transfer']['error'] ?? 'unknown';
        error_log("File upload error. Error code: $error_code");
        $error_message = "Terjadi kesalahan saat upload file. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Bukti Transfer - Ifthar Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-container {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            position: relative;
            padding-bottom: 80px;
        }
        
        .header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            background: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .back-button {
            border: none;
            background: none;
            padding: 0;
            color: inherit;
            text-decoration: none;
        }
        
        .payment-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .detail-section {
            margin-bottom: 15px;
        }
        
        .detail-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .upload-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px dashed #ddd;
            border-radius: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex align-items-center">
                <a href="donasi.php" class="back-button me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h5 class="mb-0">Upload Bukti Transfer</h5>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="d-flex justify-content-between mb-2">
                    <div>Total Donasi</div>
                    <div class="fw-bold"><?php echo $nominal_display; ?></div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>Donatur</div>
                    <div><?php echo $donasi['is_anonim'] ? 'Anonim' : htmlspecialchars($donasi['nama_donatur']); ?></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <div>Status</div>
                    <div>
                        <?php 
                        $statusClass = '';
                        switch ($donasi['status']) {
                            case 'pending':
                                $statusClass = 'status-pending';
                                $statusText = 'Menunggu Konfirmasi';
                                break;
                            case 'success':
                                $statusClass = 'status-success';
                                $statusText = 'Berhasil';
                                break;
                            case 'failed':
                                $statusClass = 'status-failed';
                                $statusText = 'Gagal';
                                break;
                            default:
                                $statusClass = 'status-pending';
                                $statusText = 'Menunggu Pembayaran';
                        }
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if ($donasi['bukti_transfer']): ?>
                <div class="detail-section">
                    <div class="detail-title">Bukti Transfer Saat Ini</div>
                    <div class="text-center my-3">
                        <img src="img/bukti_transfer/<?php echo htmlspecialchars($donasi['bukti_transfer']); ?>" 
                             alt="Bukti Transfer" class="img-fluid" style="max-height: 300px; border-radius: 8px;">
                    </div>
                    <?php if ($donasi['status'] != 'success'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bukti transfer Anda sedang diverifikasi. Terima kasih atas kesabaran Anda.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($donasi['status'] != 'success'): ?>
                <!-- Upload Bukti Transfer -->
                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="detail-title mb-3">
                            <?php echo $donasi['bukti_transfer'] ? 'Update Bukti Transfer' : 'Upload Bukti Transfer'; ?>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="bukti_transfer" 
                                  accept="image/jpeg,image/png,application/pdf" required>
                            <div class="form-text">
                                Upload bukti transfer Anda dalam format JPG, JPEG, PNG, atau PDF.
                            </div>
                        </div>
                        <button type="submit" name="submit_bukti" class="btn btn-primary w-100">
                            <?php echo $donasi['bukti_transfer'] ? 'Update Bukti Transfer' : 'Upload Bukti Transfer'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="detail-section mt-4">
                <div class="detail-title">Bantuan</div>
                <div>
                    <p>Jika Anda mengalami kesulitan dalam mengunggah bukti transfer atau memiliki pertanyaan, 
                       silakan hubungi tim dukungan kami:</p>
                    <a href="https://wa.me/6285600030005" class="btn btn-outline-success btn-sm">
                        <i class="fab fa-whatsapp me-1"></i> Hubungi via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>