# Update Log - Pendaftar Login System

Berikut adalah daftar perubahan yang telah dilakukan setelah push terakhir (commit `281022f`).

## 1. Database Schema
- **Tabel `lembaga`**:
    - Menambahkan kolom `password` (VARCHAR 255) untuk login.
    - Menambahkan kolom `last_login` (TIMESTAMP).
    - Set password default `bismillah` untuk data lama.
- **Tabel `jadwal_safari`**:
    - Menambahkan kolom `bukti_kegiatan` (TEXT) untuk menyimpan path foto laporan (support multi-file JSON).
- **Tabel `pesan_kontak` (Baru)**:
    - Membuat tabel baru untuk menyimpan pesan dari lembaga ke admin.

## 2. Authentication (Login & Register)
- **`login_p.php`**: Update logika login untuk mendukung **Dual Login** (Pengisi via Username / Lembaga via Email).
- **`form.php`**: Menambahkan input **Password** dan **Konfirmasi Password** pada formulir pendaftaran.
- **`save.php`**: Update untuk menyimpan password yang di-hash ke database saat registrasi baru.

## 3. Dashboard Lembaga (`dashboard_l.php`) - [BARU]
Membuat halaman dashboard khusus untuk pendaftar/lembaga dengan fitur:
- **Jadwal Safari**: Menampilkan daftar jadwal kunjungan.
- **Lapor Kegiatan**: 
    - Tombol lapor aktif saat jadwal tiba.
    - Modal form laporan dengan input jumlah santri, guru, pesan kesan.
    - **Upload Bukti**: Input file mendukung **Multiple Upload** (banyak foto sekaligus).
- **Profil**: Fitur edit data lembaga (`update_lembaga.php`) dan ganti password (`update_password_l.php`).
- **Hubungi Admin**:
    - Formulir kirim pesan langsung (`kirim_pesan.php`).
    - Tombol alternatif chat via WhatsApp.

## 4. Logic & Helpers
- **`lapor_admin.php`**: 
    - Update izin akses (bisa diakses oleh session `lembaga_id`).
    - Implementasi penanganan upload file multiple (disimpan sebagai JSON path).
- **`logout.php`**: Redirect ke `login_p.php`.
- **Script Update Schema** (dijalankan sekali):
    - `update_jadwal_schema.php`
    - `update_schema_phase2.php`
    - `check_jadwal.php` (telah dihapus/tidak perlu di-commit).

## 5. File Baru
- `dashboard_l.php`
- `update_lembaga.php`
- `update_password_l.php`
- `kirim_pesan.php`
- `update_jadwal_schema.php`
- `update_schema_phase2.php`
