<?php
// donasi_form.php
// Halaman Form Donasi (Frontend)
// Logika pemrosesan data sekarang ditangani oleh process_donasi.php

session_start();
require_once 'koneksi.php';

// Generate dan simpan CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function untuk format rupiah (helper view)
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Ambil data program dari database
$programId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$programJudul = "Ifthar Ramadhan"; // Default

try {
    if ($programId > 0) {
        $stmt = $conn->prepare("SELECT judul FROM program_donasi WHERE id = ?");
        $stmt->execute([$programId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $programJudul = $row['judul'];
        }
    } else {
        // Jika tidak ada ID, ambil program aktif terbaru ATAU default
        $stmt = $conn->query("SELECT id, judul FROM program_donasi WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $programId = $row['id'];
            $programJudul = $row['judul'];
        }
    }
} catch (PDOException $e) {
    // Fallback default
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Donasi - <?= htmlspecialchars($programJudul) ?></title>
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
        }
        
        .page-container {
            max-width: 480px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            position: relative;
            padding-bottom: 80px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
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
        
        .back-button:hover {
            color: var(--turquoise);
        }
        
        .nominal-option {
            background: white;
            border: 2px solid var(--light-turquoise);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nominal-option:hover {
            background: var(--light-turquoise);
            border-color: var(--turquoise);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .nominal-option.selected {
            border-color: var(--orange);
            background: #FFF8EF;
        }
        
        .emoji {
            font-size: 24px;
            margin-right: 15px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-turquoise);
            border-radius: 50%;
        }
        
        .nominal-option.selected .emoji {
            background-color: var(--orange);
            color: white;
        }
        
        .nominal-text {
            flex-grow: 1;
            font-weight: 500;
            color: var(--dark-blue);
        }
        
        .form-section {
            margin-top: 30px;
            border-top: 8px solid var(--light-turquoise);
            padding-top: 20px;
        }
        
        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 2px solid var(--light-turquoise);
        }
        
        .form-control:focus {
            border-color: var(--turquoise);
            box-shadow: 0 0 0 0.25rem rgba(64, 224, 208, 0.25);
        }
        
        .bg-light {
            background-color: var(--light-turquoise) !important;
        }
        
        h5 {
            color: var(--dark-blue);
            border-left: 4px solid var(--orange);
            padding-left: 10px;
        }

        .success-toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
            max-width: 90%;
            border-left: 4px solid var(--orange);
        }

        .success-toast.show {
            opacity: 1;
        }

        .success-toast .icon {
            background: var(--orange);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .success-toast .icon i {
            color: white;
            font-size: 14px;
        }

        .success-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            max-width: 320px;
            width: 90%;
            z-index: 1100;
            display: none;
            border-top: 4px solid var(--turquoise);
        }

        .success-modal .loader {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--orange);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 51, 102, 0.5);
            z-index: 1050;
            display: none;
        }

        .error-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            border-top: 1px solid var(--light-turquoise);
        }
        
        .btn-outline-success {
            color: var(--dark-blue);
            border-color: var(--turquoise);
            background-color: white;
        }
        
        .btn-outline-success:hover {
            background-color: var(--turquoise);
            border-color: var(--dark-turquoise);
            color: white;
        }
        
        .btn-success {
            background-color: var(--orange);
            border-color: var(--orange);
        }
        
        .btn-success:hover {
            background-color: #E67A00;
            border-color: #E67A00;
        }
        
        .form-check-input:checked {
            background-color: var(--orange);
            border-color: var(--orange);
        }
        
        .text-muted {
            color: #6c757d !important;
        }
        
        #selected-nominal {
            background-color: var(--light-turquoise) !important;
            font-weight: bold;
            color: var(--dark-blue);
        }
        
        /* Tombol outline secondary untuk modal error */
        .btn-outline-secondary {
            color: var(--dark-blue);
            border-color: var(--dark-blue);
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--dark-blue);
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="header">
            <div class="d-flex align-items-center">
                <a href="index.php" class="back-button me-3">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h5 class="mb-0"><?= htmlspecialchars($programJudul) ?></h5>
            </div>
        </div>

        <!-- Content -->
        <form id="donationForm" class="p-4">
            <!-- Hidden CSRF Token & Program ID -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="program_id" value="<?php echo $programId; ?>">
            
            <!-- Nominal Section -->
            <div class="nominal-section">
    <h5 class="mb-4">Masukkan Nominal Donasi</h5>
    
    <?php
    // Array pemetaan emoji default berdasarkan nominal
    $defaultEmojis = [
        5000 => '👛',    // Dompet kecil
        10000 => '🪙',   // Koin
        20000 => '💵',   // Uang kertas
        50000 => '💴',   // Uang kertas lain
        100000 => '💰',  // Kantong uang
        200000 => '💸',  // Uang terbang
        300000 => '💱',  // Penukaran mata uang
        500000 => '💎',  // Berlian
        750000 => '💝',  // Hati dengan pita
        1000000 => '💍'  // Cincin
    ];
    
    // Ambil nominal donasi yang aktif dari database
    try {
        $stmt = $conn->query("SELECT * FROM nominal_donasi WHERE is_active = 1 ORDER BY urutan ASC");
        $nominalOptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Perbaiki emoji jika diperlukan
        foreach ($nominalOptions as &$option) {
            // Jika emoji kosong, tidak valid, atau karakter tanda tanya
            if (empty($option['emoji']) || $option['emoji'] == '?' || strpos($option['emoji'], '&#') !== false) {
                // Gunakan emoji default berdasarkan nominal jika tersedia
                if (isset($defaultEmojis[$option['nominal']])) {
                    $option['emoji'] = $defaultEmojis[$option['nominal']];
                } else {
                    // Jika tidak ada pemetaan khusus, gunakan emoji berdasarkan rentang nominal
                    if ($option['nominal'] < 10000) {
                        $option['emoji'] = '👛';
                    } elseif ($option['nominal'] < 50000) {
                        $option['emoji'] = '🪙';
                    } elseif ($option['nominal'] < 100000) {
                        $option['emoji'] = '💵';
                    } elseif ($option['nominal'] < 200000) {
                        $option['emoji'] = '💰';
                    } elseif ($option['nominal'] < 500000) {
                        $option['emoji'] = '💸';
                    } elseif ($option['nominal'] < 1000000) {
                        $option['emoji'] = '💎';
                    } else {
                        $option['emoji'] = '💍';
                    }
                }
            }
        }
        unset($option); // Lepaskan referensi
        
    } catch(PDOException $e) {
        // Jika tabel belum ada atau terjadi error, gunakan data default
        $nominalOptions = [
            ['nominal' => 5000, 'emoji' => '👛', 'deskripsi' => 'Donasi Mini'],
            ['nominal' => 20000, 'emoji' => '💵', 'deskripsi' => 'Donasi Kecil'],
            ['nominal' => 50000, 'emoji' => '💴', 'deskripsi' => 'Donasi Standard'],
            ['nominal' => 100000, 'emoji' => '💰', 'deskripsi' => 'Donasi Sedang'],
            ['nominal' => 200000, 'emoji' => '💸', 'deskripsi' => 'Donasi Plus'],
            ['nominal' => 500000, 'emoji' => '💎', 'deskripsi' => 'Donasi Besar'],
            ['nominal' => 1000000, 'emoji' => '💍', 'deskripsi' => 'Donasi Premium']
        ];
    }
    ?>
    
    <!-- Tampilkan pilihan nominal donasi dari database -->
    <?php foreach ($nominalOptions as $option): ?>
    <div class="nominal-option" onclick="selectNominal(<?php echo $option['nominal']; ?>, this)">
        <div class="emoji"><?php echo $option['emoji']; ?></div>
        <div class="nominal-text"><?php echo formatRupiah($option['nominal']); ?></div>
        <i class="fas fa-chevron-right text-muted"></i>
    </div>
    <?php endforeach; ?>
    
    <?php if (count($nominalOptions) == 0): ?>
    <!-- Fallback jika tidak ada data di database -->
    <div class="nominal-option" onclick="selectNominal(20000, this)">
        <div class="emoji">💵</div>
        <div class="nominal-text">Rp 20.000</div>
        <i class="fas fa-chevron-right text-muted"></i>
    </div>
    <div class="nominal-option" onclick="selectNominal(100000, this)">
        <div class="emoji">💰</div>
        <div class="nominal-text">Rp 100.000</div>
        <i class="fas fa-chevron-right text-muted"></i>
    </div>
    <div class="nominal-option" onclick="selectNominal(500000, this)">
        <div class="emoji">💎</div>
        <div class="nominal-text">Rp 500.000</div>
        <i class="fas fa-chevron-right text-muted"></i>
    </div>
    <?php endif; ?>
    
    <div class="nominal-option custom" onclick="enableCustomNominal()">
        <div class="emoji">✨</div>
        <div class="nominal-text">Nominal Lainnya</div>
        <i class="fas fa-chevron-right text-muted"></i>
    </div>
</div>

            <!-- Form Section -->
            <div class="form-section">
                <div class="mb-4">
                    <h5 class="mb-3">Nominal Donasi Anda</h5>
                    <div class="form-control bg-light d-flex align-items-center">
                        <span>Rp</span>
                        <input type="text" name="nominal" id="selected-nominal" class="border-0 bg-light w-75 ms-1" 
                               value="100.000" required>
                    </div>
                    <div class="text-muted mt-2">
                        Bantu para penerima manfaat dengan donasi minimal Rp20.000
                    </div>
                    <div id="nominal-error" class="error-feedback d-none">
                        Nominal donasi minimum adalah Rp20.000
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="mb-3">Data Diri</h5>
                    <div class="mb-3">
                        <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                        <div id="nama-error" class="error-feedback d-none">
                            Nama harus diisi
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="anonim" class="form-check-input" id="anonymous">
                        <label class="form-check-label" for="anonymous">
                            Sembunyikan nama saya (Anonim)
                        </label>
                    </div>
                    <div class="mb-3">
                        <input type="tel" name="whatsapp" class="form-control" placeholder="Nomor Whatsapp Aktif" required>
                        <div id="whatsapp-error" class="error-feedback d-none">
                            Nomor WhatsApp tidak valid
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Alamat Email Aktif" required>
                        <div id="email-error" class="error-feedback d-none">
                            Format email tidak valid
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Fixed Bottom -->
        <div class="fixed-bottom">
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success" style="width: 50px;">
                    <i class="fas fa-shopping-cart"></i>
                </button>
                <button type="submit" form="donationForm" class="btn btn-success flex-grow-1">
                    Lanjutkan Pembayaran
                </button>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="success-toast" id="successToast">
        <div class="icon">
            <i class="fas fa-check"></i>
        </div>
        <span id="toastMessage"></span>
    </div>

    <!-- Success Modal -->
    <div class="modal-backdrop" id="modalBackdrop"></div>
    <div class="success-modal" id="successModal">
        <div class="loader"></div>
        <h5 class="mb-3">Data Berhasil Disimpan</h5>
        <p class="text-muted mb-0">Mengalihkan ke halaman pembayaran...</p>
    </div>

    <!-- Error Modal -->
    <div class="success-modal" id="errorModal">
        <div class="text-danger mb-3">
            <i class="fas fa-exclamation-circle" style="font-size: 40px;"></i>
        </div>
        <h5 class="mb-3">Terjadi Kesalahan</h5>
        <p class="text-muted mb-3" id="errorMessage"></p>
        <button class="btn btn-outline-secondary" onclick="hideErrorModal()">Tutup</button>
    </div>

    <script>
        // Inisialisasi - Pilih opsi pertama secara default
        document.addEventListener('DOMContentLoaded', function() {
            const firstOption = document.querySelector('.nominal-option');
            if (firstOption) {
                firstOption.classList.add('selected');
            }
        });

        function showToast(message) {
            const toast = document.getElementById('successToast');
            document.getElementById('toastMessage').textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 5000);
        }

        function enableCustomNominal() {
            document.querySelectorAll('.nominal-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            document.querySelector('.nominal-option.custom').classList.add('selected');
            
            const input = document.getElementById('selected-nominal');
            input.value = '';
            input.readOnly = false;
            input.focus();
            
            document.querySelector('.form-section').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Format input nominal jika custom
        document.getElementById('selected-nominal').addEventListener('input', function(e) {
            // Hapus semua karakter kecuali angka
            let value = this.value.replace(/\D/g, '');
            
            // Format dengan ribuan separator
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
            }
            
            this.value = value;
        });

        function selectNominal(amount, element) {
            document.querySelectorAll('.nominal-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            element.classList.add('selected');
            
            const input = document.getElementById('selected-nominal');
            const formattedAmount = new Intl.NumberFormat('id-ID').format(amount);
            input.value = formattedAmount;
            input.readOnly = true;
            
            showToast(`Kamu memilih Rp ${formattedAmount}`);
            
            document.querySelector('.form-section').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        function showSuccessModal() {
            document.getElementById('modalBackdrop').style.display = 'block';
            document.getElementById('successModal').style.display = 'block';
            document.getElementById('errorModal').style.display = 'none';
        }

        function hideSuccessModal() {
            document.getElementById('modalBackdrop').style.display = 'none';
            document.getElementById('successModal').style.display = 'none';
        }

        function showErrorModal(message) {
            document.getElementById('modalBackdrop').style.display = 'block';
            document.getElementById('successModal').style.display = 'none';
            document.getElementById('errorModal').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }

        function hideErrorModal() {
            document.getElementById('modalBackdrop').style.display = 'none';
            document.getElementById('errorModal').style.display = 'none';
        }

        // Reset semua pesan error
        function resetErrors() {
            document.querySelectorAll('.error-feedback').forEach(el => {
                el.classList.add('d-none');
            });
        }

        // Validasi form di sisi klien
        function validateForm() {
            resetErrors();
            let isValid = true;
            
            // Validasi nominal
            const nominal = document.getElementById('selected-nominal').value.replace(/\D/g, '');
            if (nominal === '' || parseInt(nominal) < 20000) {
                document.getElementById('nominal-error').classList.remove('d-none');
                isValid = false;
            }
            
            // Validasi nama
            const nama = document.querySelector('input[name="nama"]').value.trim();
            if (nama === '') {
                document.getElementById('nama-error').classList.remove('d-none');
                isValid = false;
            }
            
            // Validasi WhatsApp
            const whatsapp = document.querySelector('input[name="whatsapp"]').value.replace(/\D/g, '');
            if (whatsapp === '' || whatsapp.length < 10 || whatsapp.length > 15) {
                document.getElementById('whatsapp-error').classList.remove('d-none');
                isValid = false;
            }
            
            // Validasi email
            const email = document.querySelector('input[name="email"]').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '' || !emailRegex.test(email)) {
                document.getElementById('email-error').classList.remove('d-none');
                isValid = false;
            }
            
            return isValid;
        }

        // Di bagian submit form
        document.getElementById('donationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validasi form sebelum submit
            if (!validateForm()) {
                return;
            }
            
            try {
                showSuccessModal();
                
                const formData = new FormData(this);
                const response = await fetch('process_donasi.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // Periksa status HTTP response terlebih dahulu
                if (!response.ok) {
                    throw new Error(`Terjadi kesalahan server: ${response.status} ${response.statusText}`);
                }
                
                // Periksa tipe konten respons
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // Jika bukan JSON, tampilkan error
                    const text = await response.text();
                    console.error('Respons server bukan JSON:', text);
                    throw new Error('Format respons server tidak valid');
                }
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Tampilkan pesan sukses
                    document.querySelector('#successModal h5').textContent = 'Data Berhasil Disimpan';
                    document.querySelector('#successModal p').textContent = 'Mengalihkan ke halaman pembayaran...';
                    
                    // Redirect setelah 2 detik
                    setTimeout(() => {
                        window.location.href = `payment.php?token=${result.token}`;
                    }, 2000);
                } else {
                    hideSuccessModal();
                    showErrorModal(result.message || 'Terjadi kesalahan yang tidak diketahui');
                }
                
            } catch (error) {
                hideSuccessModal();
                showErrorModal('Terjadi kesalahan: ' + error.message);
                console.error('Detail error:', error);
            }
        });
    </script>
</body>
</html>