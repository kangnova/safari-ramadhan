<?php
require_once 'koneksi.php';
require_once 'hit_counter.php';
// ... kode lainnya ...
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Safari Ramadhan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 800px;
            margin: 2rem auto;
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
        select,
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .checkbox-group {
            display: grid;
            gap: 0.5rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
                margin: 1rem;
                padding: 1rem;
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
    <!-- Tambahkan SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">FORM PENGAJUAN SAFARI RAMADHAN 1446 H/2025</h2>
        <div class="form-notice">
            Mohon maaf untuk sementara program ini dikhususkan untuk lembaga yang berlokasi di kota Klaten 🙏🏼
        </div>

        <div class="progress-bar">
            <div class="progress"></div>
        </div>
<?php
require_once 'koneksi.php';

// Cek jumlah lembaga
try {
    $query = "SELECT COUNT(*) as total FROM lembaga";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_lembaga = $result['total'];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
        <form id="safariForm">
            <!-- Page 1 -->
            <div class="form-page active" id="page1">
                <div class="form-group">
                    <label class="required">Email</label>
                    <input type="email" required name="email">
                </div>

                <div class="form-group">
                    <label class="required">Nama Lembaga</label>
                    <input type="text" required name="nama_lembaga">
                </div>

                <div class="form-group">
                    <label class="required">Alamat Lengkap Lembaga</label>
                    <textarea required name="alamat" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="required">Kecamatan</label>
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

                <div class="form-group">
                    <label class="required">Jumlah Santri</label>
                    <input type="number" required name="jumlah_santri">
                </div>

                <div class="form-group">
                    <label class="required">Hari Aktif TPA (silahkan pilih lebih dari 1)</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Senin"> Senin
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Selasa"> Selasa
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Rabu"> Rabu
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Kamis"> Kamis
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Jumat"> Jum'at
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Sabtu"> Sabtu
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="hari_aktif[]" value="Minggu"> Ahad
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="required">Jam Aktif TPA</label>
                    <input type="text" required name="jam_aktif" placeholder="Contoh: 16:00 - 17:30">
                </div>

                <div class="form-group">
                    <label class="required">Penanggung Jawab</label>
                    <input type="text" required name="pj">
                </div>

                <div class="form-group">
                    <label class="required">Jabatan di Lembaga</label>
                    <select required name="jabatan">
                        <option value="">Pilih Jabatan</option>
                        <option value="TAKMIR MASJID">TAKMIR MASJID</option>
                        <option value="DIREKTUR TPA">DIREKTUR TPA</option>
                        <option value="GURU TPA">GURU TPA</option>
                        <option value="LAINNYA">Lainnya</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="required">Nomor WA PJ Lembaga</label>
                    <input type="tel" required name="no_wa" placeholder="Contoh: 08xxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label class="required">Materi yang Diinginkan (Bisa Pilih Lebih dari 1)</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="berkisah"> Berkisah Islami
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="motivasi"> Motivasi & Muhasabah
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" name="materi[]" value="kajian"> Kajian Buka Bersama
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Berapa kali ingin didatangi safari GNB?</label>
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

                <div class="form-group">
                    <label class="required">KETENTUAN & PERSETUJUAN</label>
                    <div class="notice-box">
                        <p><strong>PROGRAM SAFARI RAMADHAN INI GRATIS</strong>. Mohon PJ lembaga menyampaikan kepada santri agar membawa Infaq terbaiknya saja untuk mendukung program program yayasan Guru Ngaji Berdaya secara keseluruhan.</p>
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
                <div class="form-navigation">
                    <button type="button" class="btn btn-next">Berikutnya</button>
                </div>
            </div>

            <!-- Page 2 -->
            <div class="form-page" id="page2">
                <div class="form-group">
                    <label class="required">DUTA GNB</label>
                    <div class="notice-box" style="background: #f5f5f5; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        <p>GNB membuka Pendaftaran sebagai <strong>DUTA GNB</strong>, yaitu tangan kanan GNB untuk membantu merealisasikan Program Program yang diselenggarakan oleh GNB, Seperti Penggalangan dan Pentasyarufan Wakaf Qur'an, Zakat, Pemberdayaan Guru Ngaji, dll kepada orang orang yang berhak menerima manfaat dari program tersebut didaerah anda. apakah antum bersedian menjadi DUTA GNB untuk menyampaikan kebaikan dari para donatur? (jawaban anda ini tidak mempengaruhi Program Safari)</p>
                    </div>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="duta_gnb" value="bersedia" required> Bersedia
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="duta_gnb" value="belum"> Belum bersedia
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ketentuan dan Informasi</label>
                    <div class="notice-box" style="background: #f5f5f5; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                        <h4>Beberapa ketentuan yang perlu diperhatikan:</h4>
                        <ol style="margin-left: 1.5rem; margin-top: 0.5rem;">
                            <li>Setelah data ini terkirim, tim GNB akan menyeleksi lembaga mana saja yang akan dikunjungi tim Safari Ramadhan Berkisah</li>
                            <li>GBN akan membawa Doorprize untuk Lembaga yang terpilih (bukan untuk semua lembaga yang dikunjungi)</li>
                            <li>Pihak lembaga yang akan dikunjungi menyiapkan kotak Infaq dan mengumumkan kepada santri untuk membawa INFAQ terbaik untuk Donasi Dakwah GNB</li>
                            <li>Pihak lembaga yang akan dikunjungi dimohon menyiapkan sound yang baik</li>
                        </ol>

                        <h4 style="margin-top: 1rem;">Susunan Acara:</h4>
                        <ol style="margin-left: 1.5rem;">
                            <li>Pembukaan - MC dari Lembaga</li>
                            <li>Tilawah - Santri (jika ada)</li>
                            <li>Sambutan Takmir / Ketua Lembaga (jika ada)</li>
                            <li>Acara Inti - Kisah Islami oleh Juru Kisah GNB/motivasi/kajian</li>
                            <li>Penutup</li>
                        </ol>

                        <p style="margin-top: 1rem;">Jika ada informasi yang kurang jelas bisa menghubungi <strong>085726999969</strong></p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Apakah anda bersedia mengerahkan santri agar menyiapkan Infaq terbaik untuk kelangsungan dakwah GNB?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="ya" required> Ya
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="kesediaan_infaq" value="tidak"> Tidak
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Apakah acara seperti ini bermanfaat?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="manfaat" value="sangat" required> Sangat Bermanfaat
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="manfaat" value="cukup"> Bermanfaat
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="manfaat" value="kurang"> Kurang Bermanfaat
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Apakah anda telah memahami semua hak dan kewajiban dari kerjasama ini?</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="pemahaman" value="ya" required> Ya
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="pemahaman" value="tidak"> Tidak
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

    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
   const form = document.getElementById('safariForm');
   const pages = document.querySelectorAll('.form-page');
   const progress = document.querySelector('.progress');
   let currentPage = 0;

   document.querySelector('.btn-next').addEventListener('click', () => {
       // Validasi form page 1 sebelum next
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

   function validateForm(page) {
       let valid = true;
       const inputs = pages[page].querySelectorAll('input[required], select[required], textarea[required]');
       
       inputs.forEach(input => {
           if(!input.value) {
               valid = false;
               input.classList.add('error');
           }
       });

       // Validasi checkbox hari aktif
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

   form.addEventListener('submit', async (e) => {
       e.preventDefault();
       
       if(!validateForm(currentPage)) return;

       const formData = new FormData(form);
       try {
           const response = await fetch('save.php', {
               method: 'POST',
               body: formData
           });
           
           const result = await response.json();
           
           if(result.success) {
               Swal.fire({
                   title: 'Berhasil!',
                   text: result.message,
                   icon: 'success',
                   confirmButtonText: 'OK'
               }).then(() => {
                   form.reset();
                   window.location.reload();
               });
           } else {
               Swal.fire({
                   title: 'Gagal!',
                   text: result.message,
                   icon: 'error'
               });
           }
       } catch(error) {
           Swal.fire({
               title: 'Error!',
               text: 'Terjadi kesalahan saat mengirim data',
               icon: 'error'
           });
       }
   });
</script>

<!--kuota pendaftar-->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cek jumlah lembaga
    const totalLembaga = <?= $total_lembaga ?>;
    const MAX_LEMBAGA = 150;
    
    if(totalLembaga >= MAX_LEMBAGA) {
        Swal.fire({
            title: 'Mohon Maaf',
            html: `
                <p>Kuota pendaftaran Safari Ramadhan 2025 sudah mencapai batas maksimal (${MAX_LEMBAGA} lembaga).</p>
                <p>Untuk informasi lebih lanjut silahkan hubungi admin:</p>
                <p><strong>WhatsApp:</strong> <a href="https://wa.me/6285726999969" target="_blank">085726999969</a></p>
            `,
            icon: 'warning',
            allowOutsideClick: false,
            confirmButtonColor: '#20B2AA',
            confirmButtonText: 'Tutup'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'index.php';
            }
        });

        // Sembunyikan form
        document.getElementById('safariForm').style.display = 'none';
    }
});

// Modifikasi event submit form
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const totalLembaga = <?= $total_lembaga ?>;
    const MAX_LEMBAGA = 150;
    
    if(totalLembaga >= MAX_LEMBAGA) {
        Swal.fire({
            title: 'Mohon Maaf',
            html: `
                <p>Kuota pendaftaran Safari Ramadhan 2025 sudah mencapai batas maksimal (${MAX_LEMBAGA} lembaga).</p>
                <p>Untuk informasi lebih lanjut silahkan hubungi admin:</p>
                <p><strong>WhatsApp:</strong> <a href="https://wa.me/6285726999969" target="_blank">085726999969</a></p>
            `,
            icon: 'warning',
            confirmButtonColor: '#20B2AA',
            confirmButtonText: 'Tutup'
        });
        return;
    }
    
    if(!validateForm(currentPage)) return;

    const formData = new FormData(form);
    try {
        const response = await fetch('save.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if(result.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: result.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                form.reset();
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Gagal!',
                text: result.message,
                icon: 'error'
            });
        }
    } catch(error) {
        Swal.fire({
            title: 'Error!',
            text: 'Terjadi kesalahan saat mengirim data',
            icon: 'error'
        });
    }
});
</script>
</body>
</html>