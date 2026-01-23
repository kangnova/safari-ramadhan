# Analisis & Rekomendasi Proyek Safari Ramadhan / Safari Ramadhan Project Analysis & Recommendations

Dokumen ini berisi analisis terhadap struktur kode saat ini, skema database, dan rekomendasi untuk pengembangan selanjutnya agar website lebih mudah dikelola (admin-friendly), ramah pengguna (mobile-friendly), dan terstruktur dengan baik untuk pengembangan masa depan.

*This document contains an analysis of the current code structure, database schema, and recommendations for future development to make the website easier to manage (admin-friendly), user-friendly (mobile-friendly), and well-structured for future scalability.*

---

## 1. Identifikasi Struktur File & Database / File Structure & Database Identification

### Struktur File Saat Ini / Current File Structure
Saat ini, proyek menggunakan struktur **Flat PHP** (prosedural), di mana logika bisnis, query database, dan tampilan (HTML) bercampur dalam satu file.
*Currently, the project uses a **Flat PHP** (procedural) structure, where business logic, database queries, and views (HTML) are mixed in single files.*

*   **Public Area (`/`)**: `index.php`, `form.php`, dll. Menggunakan *Inline CSS* dan *JavaScript*.
*   **Admin Area (`/admin`)**: Banyak file terpisah untuk setiap fitur (`berita.php`, `pengisi.php`, dll).
*   **Database (`/db`)**: Terdapat file `safariramadhan2025.sql`.

### Skema Database / Database Schema
Berdasarkan analisis kode (terutama `admin/pengisi.php` dan file SQL), berikut adalah tabel-tabel penting dalam sistem:
*Based on code analysis (especially `admin/pengisi.php` and SQL files), here are the important tables in the system:*

1.  **`lembaga`**: Data TPQ/Masjid (Nama, Alamat, Kontak).
2.  **`pengisi`**: Data Ustadz/Kakak Pengkisah.
    *   Kolom: `id`, `nama`, `username`, `password`, `no_hp`, `alamat`, `foto`, `status`.
    *   **Catatan Keamanan**: Password saat ini disimpan dalam bentuk teks biasa (plain text), yang sangat tidak aman.
3.  **`berita`**: Artikel atau update kegiatan.
4.  **`donasi`**: Pencatatan donasi masuk.
5.  **`hari_aktif`**: Jadwal hari aktif lembaga.
6.  **`materi_dipilih`**: Pilihan materi kajian lembaga.
7.  **`persetujuan_lembaga`**: Data persetujuan syarat & ketentuan.

---

## 2. Rekomendasi Pengelolaan Admin / Admin Management Recommendations

Untuk memudahkan admin dalam mengelola website, disarankan melakukan perbaikan berikut:
*To make it easier for admins to manage the website, the following improvements are recommended:*

### A. Dashboard Terpusat (Centralized Dashboard)
Buat halaman dashboard utama yang menampilkan ringkasan statistik secara *real-time*:
*Create a main dashboard page displaying real-time summary statistics:*
*   Jumlah Pendaftar Baru (New Registrants).
*   Total Donasi Terkumpul (Total Donations).
*   Jadwal Terdekat (Upcoming Schedules).
*   Grafik Kunjungan (Visitor Charts).

### B. Standardisasi Halaman Admin (Standardized Admin Pages)
Saat ini setiap halaman admin berdiri sendiri. Gunakan **Layout Template** (Header, Sidebar, Footer terpisah) agar jika ada perubahan menu, cukup ubah di satu file saja.
*Currently, each admin page stands alone. Use a **Layout Template** (Header, Sidebar, Footer separate) so that if there are menu changes, you only need to change one file.*

### C. Fitur Pencarian & Filter Canggih (Advanced Search & Filter)
Pada halaman tabel (seperti daftar lembaga atau donasi), tambahkan fitur pencarian (search box) dan filter (misal: filter berdasarkan kecamatan atau status pembayaran) menggunakan **DataTables** (library JS) yang sudah mulai digunakan namun perlu dioptimalkan.
*On table pages (like institution lists or donations), add search boxes and filters (e.g., filter by district or payment status) using **DataTables** (JS library) which is already started but needs optimization.*

### D. Perbaikan Keamanan (Security Improvements)
**Sangat Penting**: Ubah sistem penyimpanan password pengguna/pengisi agar menggunakan **Hashing** (misal: `password_hash()` di PHP). Jangan simpan password asli di database.
*Critical: Change the user/speaker password storage system to use **Hashing** (e.g., `password_hash()` in PHP). Do not store raw passwords in the database.*

---

## 3. Rekomendasi Website Ramah Gawai (Mobile-Friendly/Responsive)

Agar website nyaman diakses oleh publik melalui HP (Smartphone):
*To ensure the website is comfortable to access by the public via Smartphone:*

### A. Pisahkan CSS dan JavaScript (Separate CSS & JS)
Pindahkan kode CSS dari `index.php` ke file terpisah, misal `assets/css/style.css`. Ini akan mempercepat loading dan memudahkan perbaikan tampilan.
*Move CSS code from `index.php` to a separate file, e.g., `assets/css/style.css`. This will speed up loading and make visual fixes easier.*

### B. Gunakan Konsep "Mobile-First"
Desainlah CSS dengan mengutamakan tampilan HP terlebih dahulu, baru kemudian tablet dan desktop menggunakan Media Queries (`@media (min-width: 768px) { ... }`).
*Design CSS prioritizing the mobile view first, then tablet and desktop using Media Queries.*

### C. Optimasi Gambar (Image Optimization)
Pastikan gambar slider dan profil pengisi dikompresi agar ringan (gunakan format WebP jika memungkinkan) dan gunakan atribut `loading="lazy"` agar hemat kuota data pengguna.
*Ensure slider and speaker profile images are compressed (use WebP format if possible) and use the `loading="lazy"` attribute to save user data.*

---

## 4. Struktur File Masa Depan (Future Code Structure)

Untuk memudahkan pengembang di masa depan (scalability & maintainability), disarankan mengubah struktur folder menjadi lebih modern, misalnya mengadopsi pola MVC sederhana:
*To facilitate future developers (scalability & maintainability), it is recommended to change the folder structure to be more modern, for example adopting a simple MVC pattern:*

```text
/safari-ramadhan
├── /app
│   ├── /config          # Database connection (koneksi.php)
│   ├── /controllers     # Logika PHP (Handling form submit, data processing)
│   └── /models          # Query Database (SQL functions)
├── /assets
│   ├── /css             # File style.css
│   ├── /js              # File script.js
│   └── /img             # Gambar/Images
├── /views               # File tampilan HTML (Admin & Public)
│   ├── /admin           # Folder khusus view admin
│   └── /public          # Folder khusus view user
├── /vendor              # Library pihak ketiga (Composer dependencies)
├── index.php            # Entry point (Router sederhana)
└── .htaccess            # Pretty URL configuration
```

### Keuntungan (Benefits):
1.  **Separation of Concerns**: Kode logika tidak bercampur dengan tampilan HTML. Jika ingin ganti desain, logika tidak rusak. (*Logic code uses are not mixed with HTML views. If you want to change the design, the logic doesn't break.*)
2.  **Keamanan (Security)**: Folder `/app` bisa diproteksi agar tidak bisa diakses langsung via browser. (*The `/app` folder can be protected so it cannot be accessed directly via the browser.*)
3.  **Kemudahan Debugging**: Error lebih mudah dilacak karena file terorganisir rapi. (*Errors are easier to track because files are neatly organized.*)

---

## Kesimpulan / Conclusion

Langkah pertama yang disarankan adalah **merapikan aset (CSS/JS)** dan **memetakan ulang database** secara lengkap. Setelah itu, perlahan migrasi halaman Admin ke struktur yang lebih modular (menggunakan `include` untuk header/sidebar) sebelum melakukan perombakan total ke konsep MVC. Jangan lupa segera perbaiki **keamanan password**.

*The recommended first step is to **tidy up assets (CSS/JS)** and **re-map the complete database**. After that, slowly migrate Admin pages to a more modular structure (using `include` for header/sidebar) before doing a total overhaul to the MVC concept. Do not forget to immediately fix **password security**.*
