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
// Tambahkan kode unik untuk transfer manual (misal 34 rupiah)
$nominal_with_unique = $donasi['nominal'] + 34;
$nominal_unique_display = 'Rp ' . number_format($nominal_with_unique, 0, ',', '.');

// Fungsi untuk memproses pembayaran berdasarkan metode yang dipilih
function processPembayaran($metode, $donasi) {
    // Update status pembayaran di database
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE donasi SET metode_pembayaran = ? WHERE token = ?");
        $stmt->execute([$metode, $donasi['token']]);
        
        // Di sini Anda bisa menambahkan logika tambahan untuk tiap metode pembayaran
        // Misalnya, untuk Virtual Account, QRIS, dll
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating payment method: " . $e->getMessage());
        return false;
    }
}

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
                    // Periksa terlebih dahulu apakah kolom bukti_transfer ada
                    try {
                        $checkCol = $pdo->query("SHOW COLUMNS FROM donasi LIKE 'bukti_transfer'");
                        $colExists = $checkCol->rowCount() > 0;
                        
                        if (!$colExists) {
                            // Tambahkan kolom jika belum ada
                            $pdo->exec("ALTER TABLE donasi ADD COLUMN bukti_transfer VARCHAR(255) NULL AFTER status");
                            error_log("Added bukti_transfer column to donasi table");
                        }
                    } catch (PDOException $e) {
                        error_log("Error checking/adding column: " . $e->getMessage());
                    }
                    
                    // Gunakan prepared statement untuk update
                    $stmt = $pdo->prepare("UPDATE donasi SET bukti_transfer = ?, status = 'pending' WHERE token = ?");
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

// Proses form jika ada POST request untuk memilih metode pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_pembayaran'])) {
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    
    // Proses pembayaran
    if (processPembayaran($metode, $donasi)) {
        // Simpan metode di session untuk digunakan setelah kembali
        $_SESSION['selected_payment'] = $metode;
        $_SESSION['donation_token'] = $donasi['token'];
        
        // Tidak perlu redirect, JavaScript akan menampilkan detail pembayaran
    } else {
        $error_message = "Gagal memproses pembayaran. Silakan coba lagi.";
    }
}

// Jika metode pembayaran sudah dipilih sebelumnya
$selected_payment = isset($_SESSION['selected_payment']) ? $_SESSION['selected_payment'] : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Metode Pembayaran - Ifthar Ramadhan</title>
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
            color: #333;
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
            position: sticky;
            top: 0;
            z-index: 100;
            color: white;
        }
        
        .back-button {
            border: none;
            background: none;
            padding: 0;
            color: white;
            text-decoration: none;
        }
        
        .payment-summary {
            background: var(--turquoise);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .payment-option {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .payment-option:hover {
            background: var(--light-turquoise);
            border-color: var(--orange);
            transform: translateY(-2px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        
        .payment-option.selected {
            border-color: var(--orange);
            background: var(--yellow);
            box-shadow: 0 0 0 2px var(--orange);
        }
        
        .payment-option-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-blue);
        }
        
        .payment-option-description {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .payment-group {
            margin-bottom: 20px;
        }
        
        .payment-group-title {
            margin-bottom: 15px;
            font-weight: 600;
            color: var(--dark-blue);
            padding: 5px 0;
            border-bottom: 2px solid var(--orange);
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
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .payment-details {
            display: none;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
            margin-top: 20px;
            margin-bottom: 80px;
            border: 1px solid var(--turquoise);
        }
        
        .payment-methods-container {
            display: block;
        }
        
        .detail-section {
            margin-bottom: 15px;
        }
        
        .detail-title {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-blue);
        }
        
        .copy-button {
            background: var(--orange);
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            color: white;
            transition: all 0.2s ease;
        }
        
        .copy-button:hover {
            background: var(--yellow);
            transform: translateY(-1px);
        }
        
        .payment-qr {
            width: 250px;
            height: 250px;
            background-color: white;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--dark-blue);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .payment-qr img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .steps-list {
            padding-left: 20px;
        }
        
        .steps-list li {
            margin-bottom: 8px;
        }
        
        .upload-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px dashed var(--turquoise);
            border-radius: 10px;
            background-color: rgba(175, 251, 251, 0.2);
        }
        
        .bank-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 15px;
            background-color: white;
            padding: 5px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary, .btn-success {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover, .btn-success:hover {
            background-color: #002347;
            border-color: #002347;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-secondary {
            color: var(--dark-blue);
            border-color: var(--turquoise);
            transition: all 0.3s ease;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--turquoise);
            border-color: var(--turquoise);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-outline-success {
            color: var(--orange);
            border-color: var(--orange);
            transition: all 0.3s ease;
        }
        
        .btn-outline-success:hover {
            background-color: var(--orange);
            border-color: var(--orange);
            color: white;
            transform: translateY(-1px);
        }
        
        .btn-outline-primary {
            color: var(--dark-blue);
            border-color: var(--dark-blue);
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
            color: white;
            transform: translateY(-1px);
        }
        
        h4, h5 {
            color: var(--dark-blue);
        }
        
        .bank-info {
            flex: 1;
        }
        
        .alert-warning {
            background-color: rgba(255, 215, 0, 0.1);
            border-color: var(--yellow);
            color: #664d03;
        }
        
        .form-control:focus {
            border-color: var(--turquoise);
            box-shadow: 0 0 0 0.25rem rgba(64, 224, 208, 0.25);
        }
        
        /* Responsive styles */
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
            .payment-option {
                padding: 12px;
            }
            .payment-group-title {
                font-size: 0.95rem;
            }
            .payment-option-title {
                font-size: 0.9rem;
            }
            .payment-option-description {
                font-size: 0.8rem;
            }
            .detail-section {
                padding: 10px !important;
            }
            .steps-list li {
                font-size: 0.9rem;
            }
            .payment-qr {
                width: 200px;
                height: 200px;
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
                <span class="fw-bold">Pilih Metode Pembayaran</span>
                <div style="width: 24px;"><!-- Elemen kosong untuk keseimbangan --></div>
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
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <!-- Payment Methods Container -->
            <div class="payment-methods-container" id="paymentMethodsContainer">
                <!-- Payment Methods Form -->
                <form id="paymentForm" method="POST">
                    <!-- Transfer Bank -->
                    <?php
// Kode ini sebaiknya di tempatkan di bagian awal file, sebelum HTML
// Ambil data logo bank dari database
try {
    $stmt = $pdo->query("SELECT * FROM logo_bank ORDER BY nama_bank ASC");
    $bank_logos = $stmt->fetchAll();

    // Filter untuk memisahkan bank dan QRIS
    $banks = [];
    $qris = [];
    
    foreach ($bank_logos as $logo) {
        if (strtolower($logo['nama_bank']) === 'qris') {
            $qris[] = $logo;
        } else {
            $banks[] = $logo;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching logo data: " . $e->getMessage());
    $banks = [];
    $qris = [];
}

// Data rekening (untuk contoh)
$rekening_data = [
    'bsi' => '9191919235',
    'mandiri' => '1380015275477',
    'jateng' => '2138041240'
];
?>

<!-- REKENING -->
<div class="payment-group">
    <div class="payment-group-title">REKENING</div>
    
    <?php if (count($banks) > 0): ?>
        <?php foreach ($banks as $bank): 
            // Tentukan kode bank (untuk javascript)
            $bank_code = strtolower(preg_replace('/\s+/', '', $bank['nama_bank']));
            
            // Tentukan nomor rekening jika tersedia, jika tidak gunakan string kosong
            $rekening = isset($rekening_data[$bank_code]) ? $rekening_data[$bank_code] : '';
        ?>
        <div class="payment-option <?php echo $selected_payment == $bank_code ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('<?php echo $bank_code; ?>', this)">
            <img src="<?php echo $bank['gambar']; ?>" alt="<?php echo htmlspecialchars($bank['nama_bank']); ?> Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title"><?php echo htmlspecialchars($bank['nama_bank']); ?></div>
                <?php if ($rekening): ?>
                <div class="payment-option-description">💳 <?php echo htmlspecialchars($bank['nama_bank']); ?>: <?php echo $rekening; ?></div>
                <?php else: ?>
                <div class="payment-option-description">Silahkan pilih untuk detail rekening</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Fallback jika tidak ada data bank -->
        <div class="payment-option <?php echo $selected_payment == 'bsi' ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('bsi', this)">
            <img src="img/logo-bsi.png" alt="BSI Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title">BSI</div>
                <div class="payment-option-description">💳 BSI: 9191919235</div>
            </div>
        </div>
        
        <div class="payment-option <?php echo $selected_payment == 'mandiri' ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('mandiri', this)">
            <img src="img/logo-mandiri.png" alt="Mandiri Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title">MANDIRI</div>
                <div class="payment-option-description">💳 MANDIRI: 1380015275477</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- QRIS -->
<div class="payment-group">
    <?php if (count($qris) > 0): ?>
        <?php foreach ($qris as $qris_item): ?>
        <div class="payment-option <?php echo $selected_payment == 'qris' ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('qris', this)">
            <img src="<?php echo $qris_item['gambar']; ?>" alt="QRIS Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title">QRIS</div>
                <div class="payment-option-description">Untuk semua Bank dan eWallet di Indonesia yang mendukung QRIS</div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Jika QRIS tidak ada di database, tampilkan default -->
        <div class="payment-option <?php echo $selected_payment == 'qris' ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('qris', this)">
            <img src="img/logo-qris.png" alt="QRIS Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title">QRIS</div>
                <div class="payment-option-description">Untuk semua Bank dan eWallet di Indonesia yang mendukung QRIS</div>
            </div>
        </div>
    <?php endif; ?>
</div>
                    
                    <!-- Hidden input for selected payment method -->
                    <input type="hidden" name="metode_pembayaran" id="metode_pembayaran" value="<?php echo $selected_payment; ?>">
                </form>
            </div>
            
            <!-- BSI Transfer Payment Details -->
            <div class="payment-details" id="bsiPaymentDetails">
                <?php
    // Ambil data logo BSI dari array yang sudah difilter sebelumnya
    $bsi_logo = '';
    foreach ($banks as $bank) {
        if (strtolower(preg_replace('/\s+/', '', $bank['nama_bank'])) === 'bsi') {
            $bsi_logo = '' . $bank['gambar'];
            break;
        }
    }
    // Gunakan logo default jika tidak ditemukan di database
    if (empty($bsi_logo)) {
        $bsi_logo = "img/logo-bsi.png";
    }
    ?>
    <div class="d-flex align-items-center mb-4">
        <img src="<?php echo $bsi_logo; ?>" alt="BSI Logo" class="bank-logo me-3" style="width: 50px; height: 50px;">
        <div>
            <h4 class="mb-1">BSI</h4>
            <h5 class="text-secondary">Transfer Manual</h5>
        </div>
    </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Nomor Rekening</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong>9191919235</strong></div>
                        <button class="copy-button" onclick="copyToClipboard('9191919235')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Jumlah</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong><?php echo $nominal_unique_display; ?></strong></div>
                        <button class="copy-button" onclick="copyToClipboard('<?php echo $nominal_with_unique; ?>')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Perhatian</div>
                    <div class="alert alert-warning rounded">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Silakan transfer sesuai nominal yang tercantum di atas. Tiga digit kode unik akan didonasikan untuk campaign terkait.
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row mt-2">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8 fw-bold">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <!-- Upload Bukti Transfer -->
                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="detail-title mb-3">
                            <i class="fas fa-upload me-2"></i> Upload Bukti Transfer
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control border-secondary" name="bukti_transfer" accept="image/jpeg,image/png,application/pdf" required>
                        </div>
                        <button type="submit" name="submit_bukti" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Upload Bukti Transfer
                        </button>
                    </form>
                </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Bantuan</div>
                    <div>
                        <a href="https://wa.me/6285600030005" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Whatsapp
                        </a>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div class="mt-2">
                        <div class="bg-light p-2 rounded mb-2">Transfer Manual</div>
                        <ol class="steps-list">
                            <li>Pilih Transfer pada menu utama bank pilihan Anda. Transfer bisa dilakukan melalui ATM, SMS Banking, atau Internet Banking.</li>
                            <li>Masukkan nomor rekening di atas. Kemudian, masukkan nominal sesuai dengan jumlah yang tertera pada nominal diatas.</li>
                        </ol>
                    </div>
                </div>
            </div>
            <!-- Mandiri Transfer Payment Details -->
            <div class="payment-details" id="mandiriPaymentDetails">
                <div class="d-flex align-items-center mb-4">
    <?php
    // Ambil data logo Mandiri dari array yang sudah difilter sebelumnya
    $mandiri_logo = '';
    foreach ($banks as $bank) {
        if (strtolower(preg_replace('/\s+/', '', $bank['nama_bank'])) === 'mandiri') {
            $mandiri_logo = '' . $bank['gambar'];
            break;
        }
    }
    // Gunakan logo default jika tidak ditemukan di database
    if (empty($mandiri_logo)) {
        $mandiri_logo = "img/logo-mandiri.png";
    }
    ?>
    <img src="<?php echo $mandiri_logo; ?>" alt="Mandiri Logo" class="bank-logo me-3" style="width: 50px; height: 50px;">
    <div>
        <h4 class="mb-1">MANDIRI</h4>
        <h5 class="text-secondary">Transfer Manual</h5>
    </div>
</div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Nomor Rekening</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong>1380015275477</strong></div>
                        <button class="copy-button" onclick="copyToClipboard('1380015275477')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Jumlah</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong><?php echo $nominal_unique_display; ?></strong></div>
                        <button class="copy-button" onclick="copyToClipboard('<?php echo $nominal_with_unique; ?>')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Perhatian</div>
                    <div class="alert alert-warning rounded">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Silakan transfer sesuai nominal yang tercantum di atas. Tiga digit kode unik akan didonasikan untuk campaign terkait.
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row mt-2">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8 fw-bold">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <!-- Upload Bukti Transfer -->
                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="detail-title mb-3">
                            <i class="fas fa-upload me-2"></i> Upload Bukti Transfer
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control border-secondary" name="bukti_transfer" accept="image/jpeg,image/png,application/pdf" required>
                        </div>
                        <button type="submit" name="submit_bukti" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Upload Bukti Transfer
                        </button>
                    </form>
                </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Bantuan</div>
                    <div>
                        <a href="https://wa.me/6285600030005" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Whatsapp
                        </a>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div class="mt-2">
                        <div class="bg-light p-2 rounded mb-2">Transfer Manual</div>
                        <ol class="steps-list">
                            <li>Pilih Transfer pada menu utama bank pilihan Anda. Transfer bisa dilakukan melalui ATM, SMS Banking, atau Internet Banking.</li>
                            <li>Masukkan nomor rekening di atas. Kemudian, masukkan nominal sesuai dengan jumlah yang tertera pada nominal diatas.</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Bank Jateng Transfer Payment Details -->
            <div class="payment-details" id="jatengPaymentDetails">
                <div class="d-flex align-items-center mb-4">
    <?php
    // Ambil data logo Bank Jateng dari array yang sudah difilter sebelumnya
    $jateng_logo = '';
    foreach ($banks as $bank) {
        if (strtolower(preg_replace('/\s+/', '', $bank['nama_bank'])) === 'bankjateng' || 
            strtolower(preg_replace('/\s+/', '', $bank['nama_bank'])) === 'jateng') {
            $jateng_logo = '' . $bank['gambar'];
            break;
        }
    }
    // Gunakan logo default jika tidak ditemukan di database
    if (empty($jateng_logo)) {
        $jateng_logo = "img/logo-jateng.png";
    }
    ?>
    <img src="<?php echo $jateng_logo; ?>" alt="Bank Jateng Logo" class="bank-logo me-3" style="width: 50px; height: 50px;">
    <div>
        <h4 class="mb-1">BANK JATENG</h4>
        <h5 class="text-secondary">Transfer Manual</h5>
    </div>
</div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Nomor Rekening</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong>2138041240</strong></div>
                        <button class="copy-button" onclick="copyToClipboard('2138041240')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Jumlah</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2 p-2 bg-light rounded"><strong><?php echo $nominal_unique_display; ?></strong></div>
                        <button class="copy-button" onclick="copyToClipboard('<?php echo $nominal_with_unique; ?>')">
                            <i class="fas fa-copy me-1"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Perhatian</div>
                    <div class="alert alert-warning rounded">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Silakan transfer sesuai nominal yang tercantum di atas. Tiga digit kode unik akan didonasikan untuk campaign terkait.
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row mt-2">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8 fw-bold">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <!-- Upload Bukti Transfer -->
                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="detail-title mb-3">
                            <i class="fas fa-upload me-2"></i> Upload Bukti Transfer
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control border-secondary" name="bukti_transfer" accept="image/jpeg,image/png,application/pdf" required>
                        </div>
                        <button type="submit" name="submit_bukti" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane me-2"></i> Upload Bukti Transfer
                        </button>
                    </form>
                </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Bantuan</div>
                    <div>
                        <a href="https://wa.me/6285600030005" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Whatsapp
                        </a>
                    </div>
                </div>
                
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div class="mt-2">
                        <div class="bg-light p-2 rounded mb-2">Transfer Manual</div>
                        <ol class="steps-list">
                            <li>Pilih Transfer pada menu utama bank pilihan Anda. Transfer bisa dilakukan melalui ATM, SMS Banking, atau Internet Banking.</li>
                            <li>Masukkan nomor rekening di atas. Kemudian, masukkan nominal sesuai dengan jumlah yang tertera pada nominal diatas.</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- QRIS Payment Details -->
            <div class="payment-details" id="qrisPaymentDetails">
                <div class="d-flex align-items-center mb-4">
                    <img src="<?php echo $qris_item['gambar']; ?>" alt="QRIS Logo" class="bank-logo me-3" style="width: 50px; height: 50px;">
                    <div>
                        <h4 class="mb-1">QRIS</h4>
                        <h5 class="text-secondary">Untuk semua Bank dan eWallet di Indonesia yang mendukung QRIS</h5>
                    </div>
                </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Jumlah</div>
                    <div class="p-2 bg-light rounded text-center mt-2">
                        <strong class="fs-5"><?php echo $nominal_display; ?></strong>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <div class="payment-qr shadow">
                        <img src="img/qris/qris.jpg" alt="QRIS Payment Code" class="img-fluid">
                    </div>
                    <div class="mt-3">
                        <a href="img/qris" download="qris.jpg" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i> Download QRIS
                        </a>
                    </div>
                </div>
                
                <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row mt-2">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8 fw-bold">Ifthar Ramadhan</div>
                    </div>
                </div>
                <div class="detail-section p-3 bg-white rounded shadow-sm">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div class="mt-3">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-outline-secondary btn-sm" style="background-color: rgba(64, 224, 208, 0.1);">
                                <img src="img/logo-gopay.png" alt="Gopay" width="20" class="me-1"> Gopay
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" style="background-color: rgba(64, 224, 208, 0.1);">
                                <img src="img/logo-shopee.png" alt="Shopee" width="20" class="me-1"> Shopee
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" style="background-color: rgba(64, 224, 208, 0.1);">
                                <img src="img/logo-dana.png" alt="Dana" width="20" class="me-1"> Dana
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" style="background-color: rgba(64, 224, 208, 0.1);">
                                <img src="img/logo-ovo.png" alt="OVO" width="20" class="me-1"> OVO
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" style="background-color: rgba(64, 224, 208, 0.1);">
                                <img src="img/logo-bca.png" alt="BCA" width="20" class="me-1"> BCA
                            </button>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fas fa-info-circle me-2"></i> Langkah-langkah Pembayaran</strong>
                            </div>
                            <div class="card-body">
                                <ol class="steps-list">
                                    <li>Buka aplikasi e-wallet atau m-banking pilihan Anda</li>
                                    <li>Pilih menu Scan QRIS atau Scan QR</li>
                                    <li>Scan kode QR di atas atau download terlebih dahulu</li>
                                    <li>Masukkan nominal sesuai jumlah donasi</li>
                                    <li>Konfirmasi dan selesaikan pembayaran</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fixed Bottom -->
        <div class="fixed-bottom" id="bottomActionBar">
            <button type="submit" form="paymentForm" class="btn btn-success w-100 shadow" id="payBtn" 
                    <?php echo empty($selected_payment) ? 'disabled' : ''; ?>>
                <i class="fas fa-check-circle me-2"></i> Pilih Metode Pembayaran
            </button>
            
            <button type="button" class="btn btn-outline-secondary w-100 d-none shadow-sm" id="backToMethodsBtn" 
                    onclick="showPaymentMethods()">
                <i class="fas fa-arrow-left me-2"></i> Pilih Metode Lain
            </button>
        </div>
    </div>

    <script>
        // Fungsi untuk menangani pemilihan metode pembayaran
        function selectPaymentMethod(method, element) {
            // Remove selected class from all options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Update hidden input
            document.getElementById('metode_pembayaran').value = method;
            
            // Enable pay button
            document.getElementById('payBtn').disabled = false;
        }
        
        // Fungsi untuk menampilkan detail pembayaran
        function showPaymentDetails(method) {
            // Sembunyikan daftar metode pembayaran
            document.getElementById('paymentMethodsContainer').style.display = 'none';
            document.getElementById('payBtn').style.display = 'none';
            
            // Tampilkan tombol kembali
            document.getElementById('backToMethodsBtn').classList.remove('d-none');
            
            // Sembunyikan semua detail pembayaran
            document.querySelectorAll('.payment-details').forEach(details => {
                details.style.display = 'none';
            });
            
            // Tampilkan detail sesuai metode
            if (method === 'bsi') {
                document.getElementById('bsiPaymentDetails').style.display = 'block';
            } else if (method === 'mandiri') {
                document.getElementById('mandiriPaymentDetails').style.display = 'block';
            } else if (method === 'jateng') {
                document.getElementById('jatengPaymentDetails').style.display = 'block';
            } else if (method === 'qris') {
                document.getElementById('qrisPaymentDetails').style.display = 'block';
            } else {
                // Untuk metode lain, defaultnya tampilkan BSI (sebagai contoh)
                document.getElementById('bsiPaymentDetails').style.display = 'block';
            }
        }
        
        // Fungsi untuk kembali ke daftar metode pembayaran
        function showPaymentMethods() {
            // Tampilkan daftar metode pembayaran
            document.getElementById('paymentMethodsContainer').style.display = 'block';
            document.getElementById('payBtn').style.display = 'block';
            
            // Sembunyikan tombol kembali
            document.getElementById('backToMethodsBtn').classList.add('d-none');
            
            // Sembunyikan semua detail pembayaran
            document.querySelectorAll('.payment-details').forEach(details => {
                details.style.display = 'none';
            });
        }
        
        // Fungsi untuk menyalin teks ke clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Teks berhasil disalin');
            }).catch(err => {
                console.error('Error menyalin teks: ', err);
            });
        }
        
        // Cek apakah metode pembayaran sudah dipilih sebelumnya
        <?php if (!empty($selected_payment)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showPaymentDetails('<?php echo $selected_payment; ?>');
        });
        <?php endif; ?>
        
        // Event listener untuk form submit
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const method = document.getElementById('metode_pembayaran').value;
            
            // Kirim AJAX request untuk update database
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'metode_pembayaran=' + method
            }).then(response => {
                // Tampilkan detail pembayaran
                showPaymentDetails(method);
            }).catch(error => {
                console.error('Error:', error);
                // Tampilkan detail pembayaran meskipun error
                showPaymentDetails(method);
            });
        });
    </script>
</body>
</html>