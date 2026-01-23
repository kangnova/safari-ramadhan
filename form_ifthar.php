<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

// Check Quota Ifthar
$currentYear = date('Y');
try {
    // Get Quota
    $stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ifthar_quota'");
    $stmtQ->execute();
    $quotaIfthar = (int)$stmtQ->fetchColumn();
    if($quotaIfthar == 0) $quotaIfthar = 200; // Hard fallback

    // Get Current Count
    $stmtC = $conn->prepare("SELECT COUNT(*) FROM ifthar WHERE YEAR(created_at) = :tahun");
    $stmtC->execute(['tahun' => $currentYear]);
    $currentCount = (int)$stmtC->fetchColumn();

    $isFull = $currentCount >= $quotaIfthar;

} catch (PDOException $e) {
    $isFull = false;
    error_log("Error checking quota: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ifthar 1000 Santri</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 800px;
            margin: 6rem auto 2rem;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-title {
            color: #006400;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .form-description {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .benefit-container {
            margin-bottom: 2rem;
        }

        .benefit-item {
            display: flex;
            align-items: start;
            margin-bottom: 1rem;
        }

        .benefit-number {
            background: #20B2AA;
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: bold;
        }

        .required::after {
            content: " *";
            color: red;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            background: #20B2AA;
            color: white;
            width: 100%;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .error {
            border-color: red !important;
        }

        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

        .error ~ .error-message {
            display: block;
        }

        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php require_once 'navbar.php'; ?>
    <div class="form-container">
        <h2 class="form-title">PENDAFTARAN IFTHAR 1000 SANTRI <?= (date('Y') - 579) ?> H/<?= date('Y') ?></h2>
        
        <div class="form-description">
            <p>Bulan Ramadhan adalah waktu yang sangat dinanti oleh umat Muslim di seluruh dunia. Bulan yang penuh berkah dan menjadi waktu yang tepat untuk berbagi, mempererat tali silaturahmi dan saling membantu sesama.</p>
        </div>

        <div class="benefit-container">
            <h3 style="margin-bottom: 1rem;">Manfaat Kegiatan:</h3>
            <div class="benefit-item">
                <span class="benefit-number">1</span>
                <p>Memberikan pengalaman berbuka yang penuh kebahagiaan dan keberkahan kepada 1000 santri yang berpuasa</p>
            </div>
            <div class="benefit-item">
                <span class="benefit-number">2</span>
                <p>Wadah untuk menjalin hubungan baik antar lembaga TPA, santri, dan masyarakat sekitar</p>
            </div>
            <div class="benefit-item">
                <span class="benefit-number">3</span>
                <p>Menguatkan nilai-nilai kepedulian, solidaritas, dan berbagi yang menjadi ajaran Islam</p>
            </div>
        </div>

        <form id="iftharForm">
            <div class="form-group">
                <label class="required">Email</label>
                <input type="email" name="email" required>
                <div class="error-message">Email wajib diisi</div>
            </div>

            <div class="form-group">
                <label class="required">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required>
                <div class="error-message">Nama lengkap wajib diisi</div>
            </div>

            <div class="form-group">
                <label class="required">No HP</label>
                <input type="tel" name="no_hp" required pattern="[0-9]{10,13}">
                <div class="error-message">Nomor HP wajib diisi (contoh: 08123xxx atau 628123xxx)</div>
            </div>

            <div class="form-group">
                <label class="required">Asal Lembaga</label>
                <input type="text" name="asal_lembaga" required>
                <div class="error-message">Asal lembaga wajib diisi</div>
            </div>

            <div class="form-group">
                <label class="required">Jumlah Santri Ikut Ifthar 1000 Santri</label>
                <input type="number" name="jumlah_santri" required min="1">
                <div class="error-message">Jumlah santri wajib diisi</div>
            </div>

           <!-- <div class="form-group">
                <label>Pengajuan Santri Yatim Tidak Mampu</label>
                <textarea name="santri_yatim" rows="3" placeholder="Isikan jumlah santri dan nantinya akan melalui verifikasi dan konfirmasi GNB"></textarea>
            </div>-->

            <button type="submit" class="btn">Kirim Pendaftaran</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('iftharForm');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if(!validateForm()) return;

            const formData = new FormData(form);
            
            try {
                const response = await fetch('save_ifthar.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Pendaftaran Ifthar 1000 Santri berhasil dikirim',
                        icon: 'success',
                        confirmButtonColor: '#20B2AA'
                    }).then(() => {
                        form.reset();
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: result.message || 'Terjadi kesalahan saat mengirim data',
                        icon: 'error',
                        confirmButtonColor: '#20B2AA'
                    });
                }
            } catch(error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat mengirim data',
                    icon: 'error',
                    confirmButtonColor: '#20B2AA'
                });
            }
        });

        function validateForm() {
            let valid = true;
            const inputs = form.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                if(!input.value) {
                    valid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });

            return valid;
        }

        // Auto-format phone number
        const phoneInput = form.querySelector('input[name="no_hp"]');
        phoneInput.addEventListener('input', function(e) {
            let value = this.value;
            
            // Hapus karakter non-angka
            value = value.replace(/\D/g, '');
            
            // Cek awalan
            if (value.startsWith('0')) {
                value = '62' + value.substring(1);
            } else if (value.startsWith('8')) {
                value = '62' + value;
            }
            
            this.value = value;
        });
    </script>

    <?php if(isset($isFull) && $isFull): ?>
        <?php include 'modal_form.php'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Disable all inputs
                const inputs = document.querySelectorAll('input, textarea, button, select');
                inputs.forEach(input => {
                    input.disabled = true;
                    input.classList.add('disabled');
                });
                
                // Show floating message
                const formContainer = document.querySelector('.form-container');
                const warningDiv = document.createElement('div');
                warningDiv.className = 'alert alert-danger text-center mb-3';
                warningDiv.style.color = 'red';
                warningDiv.style.fontWeight = 'bold';
                warningDiv.innerHTML = 'Mohon maaf, kuota pendaftaran Ifthar 1000 Santri telah penuh.';
                formContainer.insertBefore(warningDiv, formContainer.firstChild);
            });
        </script>
    <?php endif; ?>

    <?php require_once 'footer.php'; ?>