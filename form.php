<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';

// Check Quota
$currentYear = date('Y');
$stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_quota'");
$stmtQ->execute();
$quotaSafari = (int)$stmtQ->fetchColumn();
if($quotaSafari == 0) $quotaSafari = 170; // Hard fallback

$stmtC = $conn->prepare("SELECT COUNT(*) FROM lembaga WHERE YEAR(created_at) = :tahun");
$stmtC->execute(['tahun' => $currentYear]);
$currentCount = (int)$stmtC->fetchColumn();

$quotaFull = $currentCount >= $quotaSafari;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Safari Ramadhan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
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
        <h2 class="form-title">FORM PENGAJUAN SAFARI RAMADHAN 1446 H/2025</h2>
        <div class="form-notice">
            Mohon maaf untuk sementara program ini dikhususkan untuk lembaga yang berlokasi di kota Klaten 🙏🏼
        </div>

        <div class="progress-bar">
            <div class="progress"></div>
        </div>

        <form id="safariForm">
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
                    <label class="required">6. Pekan/Minggu Ke Berapa</label>
                    <select required name="manfaat">
                        <option value="">Pilih Pekan</option>
                        <option value="Pekan 1">Pekan 1</option>
                        <option value="Pekan 2">Pekan 2</option>
                        <option value="Pekan 3">Pekan 3</option>
                        <option value="Pekan 4">Pekan 4</option>
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
                </script>

                <!-- 9. Email -->
                <div class="form-group">
                    <label class="required">9. Email Aktif</label>
                    <input type="email" required name="email" placeholder="contoh@email.com">
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

                <!-- 13. Ketentuan & Persetujuan -->
                <div class="form-group">
                    <label class="required">13. Ketentuan & Persetujuan</label>
                    <div class="notice-box">
                        <p><strong>PROGRAM SAFARI RAMADHAN INI GRATIS.</strong> Mohon PJ Lembaga menginformasikan kepada santri agar membawa Infaq Terbaiknya saja untuk mendukung program-program yayasan Guru Ngaji Berdaya secara keseluruhan.</p>
                    </div>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="persetujuan" value="setuju" required> Setuju
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="persetujuan" value="belum"> Belum Setuju
                        </div>
                    </div>
                </div>

                <!-- 14. Amplop Infaq (Mapped to 'kesediaan_infaq') -->
                <div class="form-group">
                    <label class="required">14. Apakah Bersedia dititipi amplop infaq untuk tiap wali santri?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="ya" required> Bersedia
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="tidak"> Tidak Bersedia
                        </div>
                    </div>
                </div>

                <!-- 15. Share Lokasi -->
                <div class="form-group">
                    <label class="required">15. Share Lokasi (Google Maps)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" required name="share_loc" id="shareLocInput" placeholder="https://maps.google.com/..." style="flex: 1;">
                        <button type="button" class="btn btn-sm" id="openMapBtn" style="background: #20B2AA; color: white; white-space: nowrap;">
                            <i class='bx bx-map'></i> Pilih di Peta
                        </button>
                    </div>
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">*Klik tombol 'Pilih di Peta' atau tempel link Google Maps secara manual</small>
                </div>

                <!-- Map Modal -->
                <div id="mapModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
                    <div style="background: white; width: 90%; max-width: 600px; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
                        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <h5 style="margin: 0;">Pilih Lokasi Lembaga</h5>
                            <button type="button" id="closeMapBtn" style="border: none; background: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                        </div>
                        <div style="position: relative; flex: 1; min-height: 400px; height: 400px;">
                            <div id="map" style="width: 100%; height: 100%;"></div>
                            <!-- Search / GPS Controls -->
                            <div style="position: absolute; top: 10px; left: 50px; right: 10px; z-index: 1000; display: flex; gap: 5px;">
                                <button type="button" id="locateUserBtn" style="background: white; border: 2px solid rgba(0,0,0,0.2); border-radius: 4px; padding: 5px 10px; cursor: pointer;">
                                    <i class='bx bx-crosshair'></i> Lokasi Saya
                                </button>
                            </div>
                        </div>
                        <div style="padding: 15px; border-top: 1px solid #eee; text-align: right;">
                            <button type="button" id="confirmLocationBtn" class="btn btn-submit" style="width: auto;">
                                Pilih Lokasi Ini
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
    </script>
    <script>
        // Map Implementation
        document.addEventListener('DOMContentLoaded', function() {
            const mapModal = document.getElementById('mapModal');
            const openMapBtn = document.getElementById('openMapBtn');
            const closeMapBtn = document.getElementById('closeMapBtn');
            const confirmLocationBtn = document.getElementById('confirmLocationBtn');
            const locateUserBtn = document.getElementById('locateUserBtn');
            const shareLocInput = document.getElementById('shareLocInput');
            
            let map = null;
            let marker = null;
            let currentLat = -7.702; // Default Klaten
            let currentLng = 110.603;

            function initMap() {
                if (map !== null) return; // Already initialized

                map = L.map('map').setView([currentLat, currentLng], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                marker = L.marker([currentLat, currentLng], {
                    draggable: true
                }).addTo(map);

                // Update marker position on click
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    currentLat = e.latlng.lat;
                    currentLng = e.latlng.lng;
                });

                // Update coordinates when dragging ends
                marker.on('dragend', function(e) {
                    const position = marker.getLatLng();
                    currentLat = position.lat;
                    currentLng = position.lng;
                });
            }

            openMapBtn.addEventListener('click', function() {
                mapModal.style.display = 'flex';
                // Need to invalidate size after modal opens for map to render correctly
                setTimeout(() => {
                    initMap();
                    map.invalidateSize();
                    if (marker) {
                        map.panTo(marker.getLatLng());
                    }
                }, 300);
            });

            closeMapBtn.addEventListener('click', function() {
                mapModal.style.display = 'none';
            });
            
            // Close modal when clicking outside
            mapModal.addEventListener('click', function(e) {
                if (e.target === mapModal) {
                    mapModal.style.display = 'none';
                }
            });

            locateUserBtn.addEventListener('click', function() {
                if (!navigator.geolocation) {
                    alert("Browser Anda tidak mendukung Geolocation");
                    return;
                }
                
                locateUserBtn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Mencari...";
                
                navigator.geolocation.getCurrentPosition(function(position) {
                    currentLat = position.coords.latitude;
                    currentLng = position.coords.longitude;
                    
                    map.setView([currentLat, currentLng], 16);
                    marker.setLatLng([currentLat, currentLng]);
                    
                    locateUserBtn.innerHTML = "<i class='bx bx-crosshair'></i> Lokasi Saya";
                }, function(error) {
                    alert("Gagal mendapatkan lokasi: " + error.message);
                    locateUserBtn.innerHTML = "<i class='bx bx-crosshair'></i> Lokasi Saya";
                });
            });

            confirmLocationBtn.addEventListener('click', function() {
                // Generate Google Maps Link
                // Format: https://www.google.com/maps?q=lat,lng
                // Using 6 decimal places for precision
                const lat = parseFloat(currentLat).toFixed(6);
                const lng = parseFloat(currentLng).toFixed(6);
                
                const googleMapsLink = `https://www.google.com/maps?q=${lat},${lng}`;
                
                shareLocInput.value = googleMapsLink;
                mapModal.style.display = 'none';
            });
        });
    </script>
</body>
</html>