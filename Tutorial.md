# Panduan Penggunaan Sistem Admin Safari Ramadhan & Ifthar

Selamat datang di sistem manajemen pendaftaran Safari Ramadhan dan Ifthar 1000 Santri. Dokumen ini berisi panduan langkah demi langkah untuk mengelola data pendaftar, mengatur kuota, dan fitur admin lainnya.

## 1. Login Admin
1.  Akses halaman admin melalui URL: `[domain-anda]/admin/login.php`.
2.  Masukkan **Username** dan **Password** admin Anda.
3.  Klik tombol **Login**.

## 2. Dashboard Utama
Setelah login, Anda akan diarahkan ke halaman **Dashboard**. Di sini Anda dapat melihat ringkasan cepat:
*   **Card Statistik**: Menampilkan total pendaftar Safari Ramadhan, Ifthar, Duta GNB, dan Pengisi.
*   **Pendaftar Terbaru**: Tabel ringkas yang menampilkan pendaftar yang baru saja masuk.
*   **Statistik per Kecamatan**: Grafik atau tabel jumlah lembaga per kecamatan.
*   **Menu Cepat**: Akses cepat ke fitur-fitur penting.

## 3. Mengelola Pendaftar Safari Ramadhan
Menu: **Data Pendaftar** (Sidebar) atau Card Safari di Dashboard.

### A. Melihat dan Memfilter Data
*   **Filter Kecamatan**: Di bagian atas tabel, gunakan dropdown "Pilih Kecamatan" untuk menampilkan lembaga dari kecamatan tertentu saja.
*   **Filter Tahun**: Gunakan dropdown "Pilih Tahun" untuk melihat data arsip tahun sebelumnya.
*   **Pencarian**: Gunakan kotak "Search" dikanan atas tabel untuk mencari nama lembaga atau pendaftar.

### B. Mengatur Kuota Pendaftaran
Fitur ini membatasi jumlah pendaftar secara otomatis.
1.  Lihat card **Pengaturan Kuota** di bagian atas halaman Pendaftar.
2.  Anda akan melihat status "Terisi: X / Batas: Y".
3.  Masukkan angka batas baru pada kolom input.
4.  Klik **Simpan**.
    *   *Catatan*: Jika kuota penuh, form pendaftaran di halaman depan akan otomatis tertutup dan muncul pesan pemberitahuan.

### C. Validasi Pendaftar
Pada tabel pendaftar, kolom **Aksi** memiliki tombol:
*   **Tombol Detail (Biru)**: Melihat data lengkap pendaftar.
*   **Tombol Setujui (Hijau)**: Mengubah status menjadi "Approved".
*   **Tombol Tolak (Merah)**: Mengubah status menjadi "Rejected".
*   **Tombol Hapus (Tong Sampah)**: Menghapus data pendaftar (Hati-hati, data tidak bisa dikembalikan).

### D. Export Data
*   Klik tombol **Export Excel** berwarna hijau di atas tabel untuk mengunduh data pendaftar dalam format Excel (.xls).

## 4. Mengelola Pendaftar Ifthar 1000 Santri
Menu: **Pendaftar Ifthar** (Sidebar) atau Card Ifthar di Dashboard.

### A. Fitur Utama
*   **Filter Tahun**: Anda bisa melihat data pendaftar tahun lalu dengan memilih tahun di dropdown.
*   **Pengaturan Kuota**: Sama seperti Safari Ramadhan, Anda bisa mengubah batas maksimal peserta Ifthar secara dinamis melalui card pengaturan di halaman ini.

### B. Validasi
*   Proses persetujuan (Approve/Reject) sama dengan menu Safari Ramadhan.
*   Perhatikan kolom "Jumlah Santri" untuk memastikan kapasitas mencukupi.

## 5. Fitur Tambahan

### A. Manajemen Berita
Menu: **Berita**
*   **Tambah Berita**: Klik "Tambah Berita", isi Judul, Konten, dan Upload Gambar.
*   **Edit/Hapus**: Gunakan tombol aksi pada daftar berita.

### B. Manajemen Galeri
Menu: **Galeri**
*   Upload foto-foto kegiatan terbaru agar tampil di halaman depan.

### C. Manajemen Slider (Hero Image)
Menu: **Kelola Slide**
*   Ganti gambar besar yang muncul di halaman utama (Home) melalui menu ini.
*   Pastikan ukuran gambar proporsional (landscape) agar tampilan bagus.

### D. Manajemen Pengisi (Ustadz/Ustadzah)
Menu: **Data Pengisi**
*   Tambahkan atau nonaktifkan profil pengisi acara/pendongeng.

### E. Pesan Masuk
Menu: **Pesan Masuk**
*   Melihat pesan yang dikirim pengunjung melalui formulir "Kontak Kami".

## 6. Ganti Password & Profil
1.  Klik menu **Profil** atau ikon profil di pojok kanan atas.
2.  Anda bisa mengubah Nama Lengkap, Username, dan Password baru.
3.  Pastikan menggunakan password yang kuat dan mudah diingat.

---
**Catatan Teknis**: 
*   Sistem secara otomatis mendeteksi input nomor HP dan mengubah format `08xxx` menjadi `628xxx` untuk memudahkan integrasi WhatsApp.
*   Tahun pada judul formulir pendaftaran berubah otomatis mengikuti tahun berjalan.
