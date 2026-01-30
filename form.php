<?php
session_start();
require_once 'koneksi.php';
require_once 'hit_counter.php';

// Check Quota
$currentYear = date('Y');
$hijriYear = $currentYear - 579;
$stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_quota'");
$stmtQ->execute();
$quotaSafari = (int)$stmtQ->fetchColumn();
if($quotaSafari == 0) $quotaSafari = 170; // Hard fallback

$stmtC = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE YEAR(created_at) = :tahun");
$stmtC->execute(['tahun' => $currentYear]);
$currentCount = (int)$stmtC->fetchColumn();

$quotaFull = $currentCount >= $quotaSafari;

// Check Form Status
$stmtS = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_form_status'");
$stmtS->execute();
$formStatus = $stmtS->fetchColumn() ?: 'open';

// Admin Exception
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    if ($formStatus === 'closed') {
        echo '<div style="background: #ffc107; color: #000; padding: 10px; text-align: center; font-weight: bold;">MODE ADMIN: Form terlihat karena Anda login sebagai Admin (Status Asli: Closed)</div>';
        $formStatus = 'open';
    }
}

$stmtM = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_form_message'");
$stmtM->execute();
$formMessage = $stmtM->fetchColumn() ?: 'Mohon maaf, pendaftaran Safari Ramadhan saat ini sedang ditutup.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Safari Ramadhan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Override global styles for form specific needs */

        body {
            background-color: #f4f4f4; /* Match form page background style if needed */
        }
        
        .form-container {
            max-width: 800px;
            margin: 6rem auto 2rem; /* Adjusted top margin for fixed navbar */
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

        .form-notice {
            background: #f0f8ff;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            line-height: 1.6;
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
        input[type="password"],
        input[type="tel"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px; /* Mencegah auto-zoom di iOS */
            transition: border-color 0.3s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #20B2AA;
            box-shadow: 0 0 0 3px rgba(32, 178, 170, 0.1);
        }

        .checkbox-group, .radio-group {
            display: grid;
            gap: 0.8rem;
        }

        .checkbox-item, .radio-item {
            display: flex;
            align-items: center;
            gap: 10px; /* Jarak antara checkbox dan teks */
            padding: 5px 0; /* Area sentuh vertikal */
            cursor: pointer;
        }
        
        /* Memperbesar ukuran checkbox/radio untuk mobile */
        input[type="checkbox"], input[type="radio"] {
            width: 20px; 
            height: 20px;
            accent-color: #20B2AA;
            margin: 0;
        }

        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-back {
            background: #ddd;
            color: #333;
        }

        .btn-next, .btn-submit {
            background: #20B2AA;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .form-page {
            display: none;
        }

        .form-page.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 5px;
            background: #ddd;
            margin-bottom: 2rem;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #20B2AA;
            width: 50%;
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .form-container {
                margin: 6rem 1rem 1rem 1rem; /* Top margin increased to avoid navbar overlap */
                padding: 1.5rem;
            }
        }

        .error {
            border-color: red !important;
        }
        .form-group .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }
        .error ~ .error-message {
            display: block;
        }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="form-container">
        <h2 class="form-title">FORM PENGAJUAN SAFARI RAMADHAN <?= $hijriYear ?> H/<?= $currentYear ?></h2>

        <?php if ($formStatus === 'closed'): ?>
            <div class="alert alert-warning text-center" style="background-color: #fff3cd; color: #856404; padding: 2rem; border-radius: 10px; border: 1px solid #ffeeba;">
                <i class='bx bx-info-circle' style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h4>Pendaftaran Ditutup</h4>
                <p><?= nl2br(htmlspecialchars($formMessage)) ?></p>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-back" style="text-decoration: none; display: inline-block; margin-top: 10px;">Kembali ke Beranda</a>
                </div>
            </div>
        <?php else: ?>
            <div class="form-notice">
                Mohon maaf untuk sementara program ini dikhususkan untuk lembaga yang berlokasi di kota Klaten 🙏🏼
            </div>

            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        <?php endif; ?>

        <form id="safariForm" style="<?= $formStatus === 'closed' ? 'display: none;' : '' ?>">
            <!-- Page 1 -->
            <div class="form-page active" id="page1">
                <!-- 1. Nama Lembaga -->
                <div class="form-group">
                    <label class="required">1. Nama Lembaga</label>
                    <input type="text" required name="nama_lembaga">
                </div>

                <!-- 2. Alamat Lengkap -->
                <div class="form-group">
                    <label class="required">2. Alamat Lengkap Lembaga</label>
                    <div style="display: grid; gap: 10px;">
                        <div>
                            <label style="font-weight: normal; font-size: 0.9em;">Desa / Kelurahan</label>
                            <input type="text" required name="alamat" placeholder="Nama Desa">
                        </div>
                        <div>
                            <label style="font-weight: normal; font-size: 0.9em;">Kecamatan</label>
                            <select required name="kecamatan">
                                <option value="">Pilih Kecamatan</option>
                                <option value="bayat">Bayat</option>
                                <option value="cawas">Cawas</option>
                                <option value="ceper">Ceper</option>
                                <option value="delanggu">Delanggu</option>
                                <option value="gantiwarno">Gantiwarno</option>
                                <option value="jatinom">Jatinom</option>
                                <option value="jogonalan">Jogonalan</option>
                                <option value="juwiring">Juwiring</option>
                                <option value="kalikotes">Kalikotes</option>
                                <option value="karanganom">Karanganom</option>
                                <option value="karangdowo">Karangdowo</option>
                                <option value="karangnongko">Karangnongko</option>
                                <option value="kebonarum">Kebonarum</option>
                                <option value="kemalang">Kemalang</option>
                                <option value="klaten_selatan">Klaten Selatan</option>
                                <option value="klaten_tengah">Klaten Tengah</option>
                                <option value="klaten_utara">Klaten Utara</option>
                                <option value="manisrenggo">Manisrenggo</option>
                                <option value="ngawen">Ngawen</option>
                                <option value="pedan">Pedan</option>
                                <option value="polanharjo">Polanharjo</option>
                                <option value="prambanan">Prambanan</option>
                                <option value="trucuk">Trucuk</option>
                                <option value="tulung">Tulung</option>
                                <option value="wedi">Wedi</option>
                                <option value="wonosari">Wonosari</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight: normal; font-size: 0.9em;">Kabupaten</label>
                            <input type="text" value="Klaten" readonly style="background: #eee;">
                        </div>
                    </div>
                </div>

                <!-- 3. Jumlah Santri -->
                <div class="form-group">
                    <label class="required">3. Jumlah Santri</label>
                    <input type="number" required name="jumlah_santri">
                </div>

                <!-- 4. Hari Aktif -->
                <div class="form-group">
                    <label class="required">4. Hari Aktif Lembaga</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Senin"> Senin</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Selasa"> Selasa</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Rabu"> Rabu</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Kamis"> Kamis</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Jumat"> Jum'at</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Sabtu"> Sabtu</div>
                        <div class="checkbox-item"><input type="checkbox" name="hari_aktif[]" value="Minggu"> Ahad</div>
                    </div>
                </div>

                <!-- 5. Jam Aktif -->
                <div class="form-group">
                    <label class="required">5. Jam Aktif Lembaga</label>
                    <select required name="jam_aktif_select" id="jamAktifSelect" onchange="toggleJamLainnya(this)">
                        <option value="">Pilih Jam Aktif</option>
                        <option value="15:30 - 17:00 WIB">15:30 - 17:00 WIB</option>
                        <option value="16:00 - 17:00 WIB">16:00 - 17:00 WIB</option>
                        <option value="16:00 - 17:15 WIB">16:00 - 17:15 WIB</option>
                        <option value="16:00 - 17:30 WIB">16:00 - 17:30 WIB</option>
                        <option value="16:15 - 17:30 WIB">16:15 - 17:30 WIB</option>
                        <option value="16:30 - 17:30 WIB">16:30 - 17:30 WIB</option>
                        <option value="lainnya">Lainnya (Isi Manual)</option>
                    </select>
                    <input type="text" id="jamAktifManual" name="jam_aktif_manual" placeholder="Contoh: 15:00 - 17:00" style="display: none; margin-top: 10px;">
                    <!-- Hidden input to store final value -->
                    <input type="hidden" name="jam_aktif" id="jamAktifFinal">
                </div>

                <script>
                function toggleJamLainnya(select) {
                    const manualInput = document.getElementById('jamAktifManual');
                    const finalInput = document.getElementById('jamAktifFinal');
                    
                    if(select.value === 'lainnya') {
                        manualInput.style.display = 'block';
                        manualInput.required = true;
                        manualInput.value = '';
                        finalInput.value = '';
                    } else {
                        manualInput.style.display = 'none';
                        manualInput.required = false;
                        finalInput.value = select.value;
                    }
                }

                // Update final value on manual input change
                document.getElementById('jamAktifManual').addEventListener('input', function() {
                    document.getElementById('jamAktifFinal').value = this.value;
                });
                </script>

                <!-- 6. Pekan/Minggu Ke Berapa (Mapped to 'manfaat' DB column) -->
                <div class="form-group">
                    <label class="required">6. Pengajuan Pelaksanaan Safari Ramadhan</label>
                    <select required name="manfaat">
                        <option value="">Pilih Pekan</option>
                        <option value="Pekan 1 Ramadhan">Pekan 1 Ramadhan</option>
                        <option value="Pekan 2 Ramadhan">Pekan 2 Ramadhan</option>
                        <option value="Pekan 3 Ramadhan">Pekan 3 Ramadhan</option>
                        <option value="Pekan 4 Ramadhan">Pekan 4 Ramadhan</option>
                        <option value="Bebas">Bebas / Kapan Saja</option>
                    </select>
                </div>
                


                <div class="form-navigation">
                    <button type="button" class="btn btn-next">Berikutnya</button>
                </div>
            </div>

            <!-- Page 2 -->
            <div class="form-page" id="page2">
                <!-- 7. Nama PJ -->
                <div class="form-group">
                    <label class="required">7. Nama Penanggung Jawab</label>
                    <input type="text" required name="pj">
                </div>

                <!-- 8. No WA -->
                <div class="form-group">
                    <label class="required">8. No. WA PJ Lembaga</label>
                    <input type="tel" required name="no_wa" id="noWaInput" placeholder="Contoh: 628xxxxxxxxxx">
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">*Nomor akan otomatis diformat menjadi awalan 62</small>
                </div>

                <script>
                document.getElementById('noWaInput').addEventListener('input', function(e) {
                    // Hapus karakter selain angka
                    let val = this.value.replace(/[^0-9]/g, '');
                    
                    // Jika diawali 0, ganti dengan 62
                    if (val.startsWith('0')) {
                        val = '62' + val.substring(1);
                    }
                    
                    this.value = val;
                });

                document.getElementById('noWaInput').addEventListener('blur', function(e) {
                    let val = this.value;
                    // Jika tidak kosong dan tidak diawali 62, tambahkan 62 (asumsi user input 812...)
                    if (val.length > 0 && !val.startsWith('62')) {
                        this.value = '62' + val;
                    }
                });
                
                // Password Validation
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                const passwordError = document.getElementById('passwordError');

                function validatePassword() {
                    if (confirmPassword.value && password.value !== confirmPassword.value) {
                        passwordError.style.display = 'block';
                        confirmPassword.style.borderColor = 'red';
                    } else {
                        passwordError.style.display = 'none';
                        confirmPassword.style.borderColor = '#ddd'; // Reset to default
                    }
                }

                password.addEventListener('input', validatePassword);
                confirmPassword.addEventListener('input', validatePassword);
                </script>

                <!-- 9. Email -->
                <div class="form-group">
                    <label class="required">9. Email Aktif</label>
                    <input type="email" required name="email" placeholder="contoh@email.com">
                </div>

                <!-- 9b. Password -->
                <div class="form-group">
                    <label class="required">9b. Password (untuk login dashboard)</label>
                    <input type="password" required name="password" id="password" placeholder="Minimal 6 karakter" minlength="6">
                </div>

                <!-- 9c. Konfirmasi Password -->
                <div class="form-group">
                    <label class="required">9c. Konfirmasi Password</label>
                    <input type="password" required name="confirm_password" id="confirm_password" placeholder="Ulangi password">
                    <small id="passwordError" style="color: red; display: none;">Password tidak cocok!</small>
                </div>

                <!-- 10. Jabatan -->
                <div class="form-group">
                    <label class="required">10. Jabatan di Lembaga</label>
                    <select required name="jabatan">
                        <option value="">Pilih Jabatan</option>
                        <option value="TAKMIR MASJID">Takmir Masjid</option>
                        <option value="KOORDINATOR TPA">Koordinator TPA</option>
                        <option value="GURU TPA">Guru TPA</option>
                        <option value="LAINNYA">Lainnya</option>
                    </select>
                </div>

                <!-- 11. Materi -->
                <div class="form-group">
                    <label class="required">11. Materi Yang Diinginkan (Bisa pilih lebih dari 1)</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="berkisah"> Berkisah Islami
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="motivasi"> Motivasi dan Muhasabah
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="kajian"> Kajian Buka Puasa (Umum & Dewasa)
                        </div>
                    </div>
                </div>

                <!-- 12. Frekuensi -->
                <div class="form-group">
                    <label class="required">12. Berapa kali ingin didatangi Safari GNB?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="frekuensi" value="1" required> 1 kali
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="frekuensi" value="2"> 2 kali dengan materi berbeda
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="frekuensi" value="3"> 3 kali dengan materi berbeda
                        </div>
                    </div>
                </div>

                <!-- 13. Amplop Infaq (Ex-14) -->
                <div class="form-group">
                    <label class="required">13. Apakah Bersedia dititipi amplop infaq untuk tiap wali santri?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="ya" required> Bersedia
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="tidak"> Tidak Bersedia
                        </div>
                    </div>
                </div>

                <!-- 14. Share Lokasi (Ex-15) -->
                <div class="form-group">
                    <label class="required">14. Share Lokasi (Google Maps)</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" required name="share_loc" id="shareLocInput" placeholder="https://maps.google.com/..." style="flex: 1; min-width: 200px;">
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-sm" id="btnGetLoc" style="background: #20B2AA; color: white; display: flex; align-items: center; gap: 5px;">
                            <i class='bx bx-crosshair'></i> Ambil Lokasi Terkini (GPS)
                        </button>
                        <a href="https://www.google.com/maps" target="_blank" class="btn btn-sm btn-outline-secondary" style="display: flex; align-items: center; gap: 5px; text-decoration: none;">
                            <i class='bx bx-map-alt'></i> Buka Google Maps Manual
                        </a>
                    </div>
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                        *Klik <strong>"Ambil Lokasi Terkini"</strong> untuk mengisi otomatis sesuai posisi Anda saat ini.<br>
                        *Atau klik "Buka Google Maps Manual" untuk mencari lokasi, lalu salin link-nya ke sini.
                    </small>
                </div>

                <!-- 15. Duta GNB -->
                <div class="form-group">
                    <label class="required">15. Apakah Anda Bersedia Menjadi Duta GNB?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="duta_gnb" value="opsional" checked> Opsional (berarti kosong)
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="duta_gnb" value="bersedia"> Bersedia
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="duta_gnb" value="tidak_bersedia"> Tidak Bersedia
                        </div>
                    </div>
                </div>

                <!-- 16. Ketentuan & Persetujuan (Ex-15) -->
                <div class="form-group">
                    <label class="required">16. Ketentuan & Persetujuan</label>
                    <div class="notice-box">
                        <p><strong>PROGRAM SAFARI RAMADHAN INI GRATIS.</strong> Mohon PJ Lembaga menginformasikan kepada santri agar membawa Infaq Terbaiknya saja untuk mendukung program-program yayasan Guru Ngaji Berdaya secara keseluruhan.</p>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-info btn-sm text-white" style="background-color: #17a2b8;" id="btnReadMou" onclick="openMouModal()">
                            <i class='bx bx-book-open'></i> Baca Selengkapnya Isi MOU
                        </button>
                    </div>

                    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px;">
                        <label style="display: flex; gap: 10px; cursor: pointer; align-items: center;">
                            <input type="checkbox" id="agreeCheckbox" required style="width: 20px; height: 20px;" disabled>
                            <span>Kami telah membaca dan menyetujui Ketentuan Pelaksanaan Safari Ramadhan 1447 H</span>
                        </label>
                        <small id="agreeWarning" style="color: red; display: block; margin-top: 5px;">*Anda harus membaca dan mengunduh MOU terlebih dahulu sebelum bisa menyetujui.</small>
                    </div>
                    <input type="hidden" name="persetujuan" value="setuju">
                </div>

                <!-- MOU Modal -->
                <div id="mouModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
                    <div style="background: white; width: 90%; max-width: 800px; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
                        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #20B2AA; color: white;">
                            <h5 style="margin: 0;">PERJANJIAN KERJASAMA (MOU)</h5>
                            <button type="button" onclick="document.getElementById('mouModal').style.display='none'" style="border: none; background: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
                        </div>
                        <div style="padding: 20px; overflow-y: auto; text-align: left; line-height: 1.6;">
                            <div style="text-align: center;">
                                <img src="assets/img/mou_page_1.jpg?v=<?= time() ?>" alt="MOU Halaman 1" style="width: 100%; height: auto; margin-bottom: 20px; border: 1px solid #ddd;">
                                <img src="assets/img/mou_page_2.jpg?v=<?= time() ?>" alt="MOU Halaman 2" style="width: 100%; height: auto; border: 1px solid #ddd;">
                            </div>

                            <hr>
                            <div style="text-align: center; margin-top: 20px;">
                                <a href="assets/docs/mou_safari.pdf" download="MOU_Safari_Ramadhan.pdf" class="btn" id="btnDownloadMou" style="background: #20B2AA; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;" onclick="markMouDownloaded()">
                                    <i class='bx bx-download'></i> Download File PDF MOU
                                </a>
                            </div>
                        </div>
                        <div style="padding: 15px; border-top: 1px solid #eee; text-align: right;">
                            <button type="button" onclick="document.getElementById('mouModal').style.display='none'" class="btn" style="background: #6c757d; color: white; padding: 8px 20px; border: none; border-radius: 4px; cursor: pointer;">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-navigation">
                    <button type="button" class="btn btn-back">Kembali</button>
                    <button type="submit" class="btn btn-submit">Kirim</button>
                </div>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>

<?php // include 'modal_form.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        let isSubmitting = false;
        const form = document.getElementById('safariForm');
        const pages = document.querySelectorAll('.form-page');
        const progress = document.querySelector('.progress');
        let currentPage = 0;

        // Handle page navigation
        document.querySelector('.btn-next').addEventListener('click', () => {
            if(validateForm(currentPage)) {
                pages[currentPage].classList.remove('active');
                currentPage++;
                pages[currentPage].classList.add('active');
                progress.style.width = `${(currentPage + 1) * 50}%`;
            }
        });

        document.querySelector('.btn-back').addEventListener('click', () => {
            pages[currentPage].classList.remove('active');
            currentPage--;
            pages[currentPage].classList.add('active');
            progress.style.width = `${(currentPage + 1) * 50}%`;
        });

        // Form validation
        function validateForm(page) {
            let valid = true;
            const inputs = pages[page].querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                if(!input.value) {
                    valid = false;
                    input.classList.add('error');
                }
            });

            if(page === 0) {
                const hariAktif = document.querySelectorAll('input[name="hari_aktif[]"]:checked');
                if(hariAktif.length === 0) {
                    valid = false;
                    Swal.fire({
                        title: 'Perhatian!',
                        text: 'Pilih minimal 1 hari aktif TPA',
                        icon: 'warning'
                    });
                }
            }

            if(page === 1) { // Validation for Page 2
                const pass = document.getElementById('password');
                const confirm = document.getElementById('confirm_password');
                const error = document.getElementById('passwordError');

                if(pass.value !== confirm.value) {
                    valid = false;
                    pass.classList.add('error');
                    confirm.classList.add('error');
                    error.style.display = 'block';
                    Swal.fire({
                        title: 'Password Tidak Cocok',
                        text: 'Mohon pastikan password dan konfirmasi password sama.',
                        icon: 'warning'
                    });
                } else {
                    pass.classList.remove('error');
                    confirm.classList.remove('error');
                    error.style.display = 'none';
                }
            }

            return valid;
        }

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if(!validateForm(currentPage)) {
                return;
            }

            // Show loading state
            Swal.fire({
                title: 'Sedang mengirim...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(this);

            fetch('save.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        title: 'Alhamdulillah!',
                        text: 'Data lembaga Anda berhasil terkirim. Jazakumullah Khairan telah mendaftar Safari Ramadhan. Tim kami akan segera menghubungi Anda melalui WhatsApp untuk konfirmasi jadwal. Mohon ditunggu ya ustadz/ustadzah... 😊',
                        icon: 'success',
                        confirmButtonColor: '#20B2AA',
                        confirmButtonText: 'OK, Siap!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php';
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Mohon Maaf',
                        text: data.message || 'Terjadi kesalahan saat menyimpan data',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Terjadi kesalahan sistem. Silakan coba lagi nanti.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        });

    </script>
    <?php if ($quotaFull): ?>
        <?php include 'modal_form.php'; ?>
        <script>
            // Disable form inputs immediately
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.querySelector('form');
                if(form) {
                    var inputs = form.querySelectorAll('input, select, textarea, button');
                    inputs.forEach(function(input) {
                        input.disabled = true;
                    });
                }
            });
        </script>
    <?php endif; ?>
    <script>
        // GPS / Geolocation Handling
        document.addEventListener('DOMContentLoaded', function() {
            const btnGetLoc = document.getElementById('btnGetLoc');
            const shareLocInput = document.getElementById('shareLocInput');

            if(btnGetLoc) {
                btnGetLoc.addEventListener('click', function() {
                    if (!navigator.geolocation) {
                        Swal.fire('Error', 'Browser Anda tidak mendukung Geolocation.', 'error');
                        return;
                    }
                    
                    // Show loading
                    const originalText = btnGetLoc.innerHTML;
                    btnGetLoc.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Mencari Lokasi...";
                    btnGetLoc.disabled = true;

                    navigator.geolocation.getCurrentPosition(function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Use higher precision format
                        const googleMapsLink = `https://www.google.com/maps?q=${lat},${lng}`;
                        
                        shareLocInput.value = googleMapsLink;
                        
                        btnGetLoc.innerHTML = "<i class='bx bx-check'></i> Lokasi Ditemukan!";
                        btnGetLoc.classList.remove('btn-submit'); // if any
                        btnGetLoc.style.background = '#28a745';
                        
                        setTimeout(() => {
                            btnGetLoc.innerHTML = originalText;
                            btnGetLoc.disabled = false;
                            btnGetLoc.style.background = '#20B2AA';
                        }, 2000);

                    }, function(error) {
                        console.error(error);
                        let msg = "Gagal mengambil lokasi.";
                        if(error.code == 1) msg = "Izin lokasi ditolak. Mohon izinkan akses lokasi di browser Anda.";
                        else if(error.code == 2) msg = "Lokasi tidak tersedia. Pastikan GPS aktif.";
                        else if(error.code == 3) msg = "Waktu permintaan habis.";
                        
                        Swal.fire('Gagal', msg, 'error');
                        btnGetLoc.innerHTML = originalText;
                        btnGetLoc.disabled = false;
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    });
                });
            }
        });
        // MOU Logic
        let mouRead = false;
        let mouDownloaded = false;

        function openMouModal() {
            document.getElementById('mouModal').style.display = 'flex';
            mouRead = true;
            checkMouStatus();
        }

        function markMouDownloaded() {
            mouDownloaded = true;
            checkMouStatus();
        }

        function checkMouStatus() {
            const agreeCheckbox = document.getElementById('agreeCheckbox');
            const agreeWarning = document.getElementById('agreeWarning');
            
            if (mouRead && mouDownloaded) {
                agreeCheckbox.disabled = false;
                if(agreeWarning) agreeWarning.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const agreeCheckbox = document.getElementById('agreeCheckbox');
            const submitBtn = document.querySelector('button[type="submit"]');

            if(agreeCheckbox && submitBtn) {
                // Initial state
                submitBtn.disabled = true; // Always disabled initially
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';

                agreeCheckbox.addEventListener('change', function() {
                    // Submit button only enabled if checkbox is checked AND MOU conditions met (which is implied because checkbox is disabled otherwise)
                    if (this.checked) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.5';
                        submitBtn.style.cursor = 'not-allowed';
                    }
                });
            }
        });
    </script>
</body>
</html>