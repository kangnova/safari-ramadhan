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
            $target = 'uploads/' . $newname;
            
            // Buat direktori jika belum ada
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Upload file
            if (move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $target)) {
                // Update database dengan file bukti transfer
                try {
                    $stmt = $pdo->prepare("UPDATE donasi SET bukti_transfer = ?, status = 'pending' WHERE token = ?");
                    $stmt->execute([$newname, $donasi['token']]);
                    
                    // Redirect ke halaman terima kasih
                    header('Location: terima_kasih.php?token=' . $donasi['token']);
                    exit;
                } catch (PDOException $e) {
                    $error_message = "Gagal menyimpan data bukti transfer.";
                }
            } else {
                $error_message = "Gagal mengupload file. Silakan coba lagi.";
            }
        } else {
            $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau PDF.";
        }
    }
}

// Proses form jika ada POST request untuk memilih metode pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['metode_pembayaran'])) {
    $metode = htmlspecialchars($_POST['metode_pembayaran']);
    
    // Proses pembayaran
    if (processPembayaran($metode, $donasi)) {
        // Untuk metode yang memerlukan redirect (seperti e-wallet)
        if (in_array($metode, ['gopay', 'shopeepay', 'qris'])) {
            // Simpan metode di session untuk digunakan setelah kembali
            $_SESSION['selected_payment'] = $metode;
            $_SESSION['donation_token'] = $donasi['token'];
            
            // Tidak perlu redirect, JavaScript akan menampilkan detail pembayaran
        } else {
            // Simpan metode di session untuk digunakan setelah kembali
            $_SESSION['selected_payment'] = $metode;
            $_SESSION['donation_token'] = $donasi['token'];
            
            // Tidak perlu redirect, JavaScript akan menampilkan detail pembayaran
        }
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
        
        .payment-option {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .payment-option:hover {
            background: #f8f9fa;
        }
        
        .payment-option.selected {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .payment-option-title {
            font-weight: 500;
            margin-bottom: 5px;
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
            color: #495057;
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
        }
        
        .payment-details {
            display: none;
            padding: 20px;
            border-radius: 10px;
            background-color: #f8f9fa;
            margin-top: 20px;
            margin-bottom: 80px;
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
        }
        
        .copy-button {
            background: #e9ecef;
            border: none;
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
        }
        
        .payment-qr {
            width: 200px;
            height: 200px;
            background-color: #f0f0f0;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
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
            border: 1px dashed #ddd;
            border-radius: 10px;
        }
        
        .bank-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 10px;
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
                <h5 class="mb-0">Pilih Metode Pembayaran</h5>
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
                    <!-- Manual Transfer -->
                    <div class="payment-group">
                        <div class="payment-group-title">Transfer Manual</div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'bsi' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('bsi', this)">
                            <div class="payment-option-title">BSI</div>
                            <div class="payment-option-description">Transfer Manual</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'bsi_ln' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('bsi_ln', this)">
                            <div class="payment-option-title">BSI KHUSUS LUAR NEGERI</div>
                            <div class="payment-option-description">TRANSFER MANUAL BSI KHUSUS LUAR NEGERI</div>
                        </div>
                    </div>
                    
                    <!-- Virtual Account -->
                    <div class="payment-group">
                        <div class="payment-group-title">Midtrans VA & Merchants</div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'bca_va' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('bca_va', this)">
                            <div class="payment-option-title">BCA</div>
                            <div class="payment-option-description">BCA Virtual Account</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'bni_va' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('bni_va', this)">
                            <div class="payment-option-title">BNI</div>
                            <div class="payment-option-description">BNI Virtual Account</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'mandiri_va' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('mandiri_va', this)">
                            <div class="payment-option-title">MANDIRI</div>
                            <div class="payment-option-description">Mandiri Virtual Account</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'bri_va' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('bri_va', this)">
                            <div class="payment-option-title">BRI</div>
                            <div class="payment-option-description">BRI Virtual Account</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'permata_va' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('permata_va', this)">
                            <div class="payment-option-title">PERMATA</div>
                            <div class="payment-option-description">Permata Virtual Account</div>
                        </div>
                    </div>
                    
                    <!-- E-Wallet -->
                    <div class="payment-group">
                        <div class="payment-group-title">E-Wallet</div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'gopay' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('gopay', this)">
                            <div class="payment-option-title">GoPay</div>
                            <div class="payment-option-description">Pembayaran melalui GoPay</div>
                        </div>
                        
                        <div class="payment-option <?php echo $selected_payment == 'shopeepay' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('shopeepay', this)">
                            <div class="payment-option-title">ShopeePay</div>
                            <div class="payment-option-description">Pembayaran melalui ShopeePay</div>
                        </div>
                    </div>
                    
                    <!-- QRIS -->
                    <div class="payment-group">
                        <div class="payment-option <?php echo $selected_payment == 'qris' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('qris', this)">
                            <div class="payment-option-title">QRIS</div>
                            <div class="payment-option-description">Untuk semua Bank dan eWallet di Indonesia yang mendukung QRIS</div>
                        </div>
                    </div>
                    
                    <!-- Other Banks -->
                    <div class="payment-group">
                        <div class="payment-option <?php echo $selected_payment == 'other_bank' ? 'selected' : ''; ?>" 
                             onclick="selectPaymentMethod('other_bank', this)">
                            <div class="payment-option-title">BANK Lainnya (Prima/ATM Bersama/Alto)</div>
                            <div class="payment-option-description">Bayar di jaringan ATM Bank Lainnya</div>
                        </div>
                    </div>
                    
                    <!-- Hidden input for selected payment method -->
                    <input type="hidden" name="metode_pembayaran" id="metode_pembayaran" value="<?php echo $selected_payment; ?>">
                </form>
            </div>
            
            <!-- BSI Transfer Payment Details -->
            <div class="payment-details" id="bsiPaymentDetails">
                <h4 class="mb-4">BSI</h4>
                <h5>Transfer Manual</h5>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Nomor Rekening</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2"><strong>8003004458</strong></div>
                        <button class="copy-button" onclick="copyToClipboard('8003004458')">Salin Nomor Rekening</button>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Jumlah</div>
                    <div class="d-flex align-items-center">
                        <div class="me-2"><strong><?php echo $nominal_unique_display; ?></strong></div>
                        <button class="copy-button" onclick="copyToClipboard('<?php echo $nominal_with_unique; ?>')">Salin Nominal</button>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Perhatian</div>
                    <div class="text-danger">
                        Silakan transfer sesuai nominal yang tercantum di atas. Tiga digit kode unik akan didonasikan untuk campaign terkait.
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row">
                        <div class="col-4">Nama Produk</div>
                        <div class="col-8">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <!-- Upload Bukti Transfer -->
                <div class="upload-section">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="detail-title mb-3">Upload Bukti Transfer</div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="bukti_transfer" accept="image/jpeg,image/png,application/pdf" required>
                        </div>
                        <button type="submit" name="submit_bukti" class="btn btn-primary w-100">Upload Bukti Transfer</button>
                    </form>
                </div>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Bantuan</div>
                    <div>
                        <a href="https://wa.me/628123456789" class="btn btn-outline-success btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Whatsapp
                        </a>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div>
                        <h6>Transfer Manual</h6>
                        <ol class="steps-list">
                            <li>Pilih Transfer pada menu utama bank pilihan Anda. Transfer bisa dilakukan melalui ATM, SMS Banking, atau Internet Banking.</li>
                            <li>Masukkan nomor rekening di atas. Kemudian, masukkan nominal sesuai dengan jumlah yang tertera pada nominal diatas.</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- GoPay Payment Details -->
            <div class="payment-details" id="gopayPaymentDetails">
                <h4 class="mb-4">GoPay</h4>
                <h5>Pembayaran melalui GoPay</h5>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Jumlah</div>
                    <div><strong><?php echo $nominal_display; ?></strong></div>
                </div>
                
                <div class="text-center">
                    <p>Scan kode QR berikut dengan aplikasi Gojek.</p>
                    <div class="payment-qr">
                        <i class="fas fa-qrcode fa-5x text-muted"></i>
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="#" class="btn btn-outline-success btn-sm">Buka Aplikasi Gojek</a>
                        <a href="#" class="btn btn-outline-primary btn-sm">Download QRIS</a>
                    </div>
                </div>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div>
                        <h6>GoPay</h6>
                        <ol class="steps-list">
                            <li>Buka aplikasi Gojek atau e-wallet lain Anda.</li>
                            <li>Pindai kode QR yang ada pada monitor Anda.</li>
                            <li>Periksa detail transaksi Anda pada aplikasi, lalu tap tombol Bayar.</li>
                            <li>Masukkan PIN Anda.</li>
                            <li>Transaksi Anda telah selesai.</li>
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- QRIS Payment Details -->
            <div class="payment-details" id="qrisPaymentDetails">
                <h4 class="mb-4">QRIS</h4>
                <h5>Untuk semua Bank dan eWallet di Indonesia yang mendukung QRIS</h5>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Jumlah</div>
                    <div><strong><?php echo $nominal_display; ?></strong></div>
                </div>
                
                <div class="text-center">
                    <div class="payment-qr">
                        <i class="fas fa-qrcode fa-5x text-muted"></i>
                    </div>
                    <div class="mt-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">Download QRIS</a>
                    </div>
                </div>
                
                <div class="detail-section mt-4">
                    <div class="detail-title">Detail Transaksi</div>
                    <div class="row">
                        <div class="col-4">Nama Program</div>
                        <div class="col-8">Ifthar Ramadhan</div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <div class="detail-title">Cara Pembayaran</div>
                    <div class="mb-3">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-outline-secondary btn-sm">Gopay</button>
                            <button class="btn btn-outline-secondary btn-sm">Shopee</button>
                            <button class="btn btn-outline-secondary btn-sm">Dana</button>
                            <button class="btn btn-outline-secondary btn-sm">OVO</button>
                            <button class="btn btn-outline-secondary btn-sm">BCA</button>
                        </div>
                        <ol class="steps-list">
                            <li>Klik Aplikasi</li>
                            <li>Scan</li>
                            <li>Masukkan Nominal</li>
                            <li>Selesai</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fixed Bottom -->
        <div class="fixed-bottom" id="bottomActionBar">
            <button type="submit" form="paymentForm" class="btn btn-success w-100" id="payBtn" 
                    <?php echo empty($selected_payment) ? 'disabled' : ''; ?>>
                Pilih Metode Pembayaran
            </button>
            
            <button type="button" class="btn btn-outline-secondary w-100 d-none" id="backToMethodsBtn" 
                    onclick="showPaymentMethods()">
                Pilih Metode Lain
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
            if (method === 'bsi' || method === 'bsi_ln') {
                document.getElementById('bsiPaymentDetails').style.display = 'block';
            } else if (method === 'gopay' || method === 'shopeepay') {
                document.getElementById('gopayPaymentDetails').style.display = 'block';
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