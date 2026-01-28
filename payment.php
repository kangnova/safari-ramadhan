<?php
require_once 'koneksi.php';

// Gunakan variabel $conn dari koneksi.php dan alias ke $pdo agar kompatibel dengan kode yang ada
$pdo = $conn;

// Set default fetch mode array asosiatif (jika belum di set di koneksi.php)
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Pastikan koneksi menggunakan UTF-8
$pdo->exec("SET NAMES utf8");

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
<!-- Transfer Bank -->
    <?php
    // Ambil data logo bank dari database
    try {
        // Ambil semua metode pembayaran yang aktif, urutkan berdasarkan urutan dan nama
        $stmt = $pdo->query("SELECT * FROM logo_bank WHERE is_active = 1 ORDER BY urutan ASC, nama_bank ASC");
        $payment_methods = $stmt->fetchAll();

        // Filter berdasarkan kategori
        $banks = [];
        $ewallets = [];
        $qris = [];
        
        foreach ($payment_methods as $method) {
            if ($method['kategori'] == 'bank') {
                $banks[] = $method;
            } elseif ($method['kategori'] == 'ewallet') {
                $ewallets[] = $method;
            } elseif ($method['kategori'] == 'qris') {
                $qris[] = $method;
            } else {
                // Fallback untuk data lama atau kategori tak dikenal, anggap bank
                if (strtolower($method['nama_bank']) === 'qris') {
                    $qris[] = $method;
                } else {
                    $banks[] = $method;
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error fetching payment methods: " . $e->getMessage());
        $banks = [];
        $ewallets = [];
        $qris = [];
    }
    ?>

    <!-- REKENING BANK -->
    <?php if (count($banks) > 0): ?>
    <div class="payment-group">
        <div class="payment-group-title">TRANSFER BANK</div>
        
        <?php foreach ($banks as $bank): 
            $bank_code = 'method_' . $bank['id']; 
            $rekening = $bank['nomor_rekening'];
            // Jika nomor rekening kosong di db, coba gunakan data hardcode lama sebagai fallback (opsional)
        ?>
        <div class="payment-option <?php echo $selected_payment == $bank_code ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('<?php echo $bank_code; ?>', this)">
            <img src="<?php echo $bank['gambar']; ?>" alt="<?php echo htmlspecialchars($bank['nama_bank']); ?> Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title"><?php echo htmlspecialchars($bank['nama_bank']); ?></div>
                <?php if ($rekening): ?>
                <div class="payment-option-description">💳 No. Rek: <?php echo htmlspecialchars($rekening); ?></div>
                <?php else: ?>
                <div class="payment-option-description">Silahkan pilih untuk detail rekening</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- E-WALLET -->
    <?php if (count($ewallets) > 0): ?>
    <div class="payment-group">
        <div class="payment-group-title">E-WALLET</div>
        
        <?php foreach ($ewallets as $wallet): 
            $wallet_code = 'method_' . $wallet['id']; 
            $nomor = $wallet['nomor_rekening'];
        ?>
        <div class="payment-option <?php echo $selected_payment == $wallet_code ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('<?php echo $wallet_code; ?>', this)">
            <img src="<?php echo $wallet['gambar']; ?>" alt="<?php echo htmlspecialchars($wallet['nama_bank']); ?> Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title"><?php echo htmlspecialchars($wallet['nama_bank']); ?></div>
                <?php if ($nomor): ?>
                <div class="payment-option-description">📱 No: <?php echo htmlspecialchars($nomor); ?></div>
                <?php else: ?>
                <div class="payment-option-description">Silahkan pilih untuk instruksi pembayaran</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- QRIS -->
    <?php if (count($qris) > 0): ?>
    <div class="payment-group">
        <div class="payment-group-title">QRIS</div>
        <?php foreach ($qris as $qris_item): 
             $qris_code = 'method_' . $qris_item['id'];
        ?>
        <div class="payment-option <?php echo $selected_payment == $qris_code ? 'selected' : ''; ?>" 
             onclick="selectPaymentMethod('<?php echo $qris_code; ?>', this)">
            <img src="<?php echo $qris_item['gambar']; ?>" alt="QRIS Logo" class="bank-logo">
            <div class="bank-info">
                <div class="payment-option-title">QRIS</div>
                <div class="payment-option-description">Scan QR code menggunakan e-wallet pilihan Anda</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Hidden input for selected payment method -->
    <input type="hidden" name="metode_pembayaran" id="metode_pembayaran" value="<?php echo $selected_payment; ?>">
</form>
</div>

<!-- Payment Details Sections (Dynamic) -->
<?php 
// Gabungkan semua metode untuk loop detail section
$all_methods = array_merge($banks, $ewallets, $qris);

foreach ($all_methods as $method): 
    $method_code = 'method_' . $method['id'];
    $is_qris = ($method['kategori'] == 'qris' || strtolower($method['nama_bank']) === 'qris');
?>
<div class="payment-details" id="<?php echo $method_code; ?>PaymentDetails">
    <div class="d-flex align-items-center mb-4">
        <img src="<?php echo $method['gambar']; ?>" alt="<?php echo htmlspecialchars($method['nama_bank']); ?> Logo" class="bank-logo me-3" style="width: 50px; height: 50px;">
        <div>
            <h4 class="mb-1"><?php echo htmlspecialchars($method['nama_bank']); ?></h4>
            <h5 class="text-secondary"><?php echo $is_qris ? 'Scan QR Code' : 'Transfer Manual'; ?></h5>
        </div>
    </div>
    
    <?php if (!$is_qris): ?>
        <!-- Tampilan untuk Bank/E-Wallet -->
        <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
            <div class="detail-title">Nomor Rekening / Tujuan</div>
            <div class="d-flex align-items-center">
                <div class="me-2 p-2 bg-light rounded"><strong><?php echo htmlspecialchars($method['nomor_rekening']); ?></strong></div>
                <button class="copy-button" onclick="copyToClipboard('<?php echo htmlspecialchars($method['nomor_rekening']); ?>')">
                    <i class="fas fa-copy me-1"></i> Salin
                </button>
            </div>
            <?php if (!empty($method['atas_nama'])): ?>
            <div class="mt-2 text-muted small">A.N: <?php echo htmlspecialchars($method['atas_nama']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="detail-section p-3 bg-white rounded shadow-sm">
            <div class="detail-title">Jumlah Transfer</div>
            <div class="d-flex align-items-center">
                <div class="me-2 p-2 bg-light rounded"><strong><?php echo $nominal_unique_display; ?></strong></div>
                <button class="copy-button" onclick="copyToClipboard('<?php echo $nominal_with_unique; ?>')">
                    <i class="fas fa-copy me-1"></i> Salin
                </button>
            </div>
        </div>

    <?php else: ?>
        <!-- Tampilan untuk QRIS -->
        <div class="payment-qr">
            <!-- Tampilkan gambar QRIS yang diupload sebagai logo/gambar bank di database -->
            <!-- Jika gambar terlalu kecil (icon), mungkin perlu handling khusus, tapi asumsi user upload gambar QR Code -->
            <img src="<?php echo $method['gambar']; ?>" alt="QRIS Code">
        </div>
        
        <div class="detail-section p-3 bg-white rounded shadow-sm text-center">
            <div class="detail-title">Total Pembayaran</div>
            <div class="h4 text-primary"><?php echo $nominal_unique_display; ?></div>
        </div>
    <?php endif; ?>
    
    <div class="detail-section p-3 bg-white rounded shadow-sm">
        <div class="detail-title">Perhatian</div>
        <div class="alert alert-warning rounded">
            <i class="fas fa-exclamation-circle me-2"></i>
            Silakan transfer sesuai nominal yang tercantum (termasuk kode unik) agar verifikasi dapat berjalan otomatis.
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
                <i class="fas fa-paper-plane me-2"></i> Kirim Bukti Transfer
            </button>
        </form>
    </div>
    
    <div class="detail-section mt-4 p-3 bg-white rounded shadow-sm">
        <div class="detail-title">Bantuan</div>
        <div>
            <a href="https://wa.me/6281234567890" class="btn btn-outline-success btn-sm">
                <i class="fab fa-whatsapp me-1"></i> Whatsapp Admin
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
            
            
</div>
        
        <!-- Fixed Bottom -->
        <div class="fixed-bottom" id="bottomActionBar">
            <button type="button" class="btn btn-success w-100 shadow" id="payBtn" 
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
            // ID detail sekarang menggunakan format: method_{id}PaymentDetails
            const detailId = method + 'PaymentDetails';
            const detailElement = document.getElementById(detailId);
            
            if (detailElement) {
                detailElement.style.display = 'block';
            } else {
                console.error('Detail pembayaran tidak ditemukan untuk: ' + method);
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
                // Fallback untuk browser yang tidak support clipboard API
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Teks berhasil disalin');
                } catch (err) {
                    console.error('Fallback clipboard error:', err);
                }
                document.body.removeChild(textArea);
            });
        }
        
        // Cek apakah metode pembayaran sudah dipilih sebelumnya
        <?php if (!empty($selected_payment)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            showPaymentDetails('<?php echo $selected_payment; ?>');
        });
        <?php endif; ?>
        
        // Event listener untuk form submit
        // Event listener untuk tombol bayar
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('payBtn');
            if (btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const methodInput = document.getElementById('metode_pembayaran');
                    const method = methodInput ? methodInput.value : '';
                    
                    if (!method) {
                        alert('Silakan pilih metode pembayaran terlebih dahulu.');
                        return;
                    }
                    
                    // Show loading state
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                    btn.disabled = true;
                    
                    // Kirim AJAX request untuk update database
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'metode_pembayaran=' + encodeURIComponent(method)
                    }).then(response => {
                        // Tampilkan detail pembayaran
                        showPaymentDetails(method);
                    }).catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan koneksi: ' + error);
                        // Tampilkan detail pembayaran meskipun error (fallback agar user tetap bisa transfer)
                        showPaymentDetails(method);
                    }).finally(() => {
                        // Restore button text
                        btn.innerHTML = originalText;
                    });
                });
            } else {
                console.error('Tombol payBtn tidak ditemukan');
            }
        });
    </script>
</body>
</html>