-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Feb 2025 pada 01.57
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `safariramadhan2025`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `hari_aktif`
--

CREATE TABLE `hari_aktif` (
  `id` int(11) NOT NULL,
  `lembaga_id` int(11) DEFAULT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `lembaga`
--

CREATE TABLE `lembaga` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nama_lembaga` varchar(200) NOT NULL,
  `alamat` text NOT NULL,
  `kecamatan` varchar(50) NOT NULL,
  `jumlah_santri` int(11) NOT NULL,
  `jam_aktif` varchar(50) NOT NULL,
  `penanggung_jawab` varchar(100) NOT NULL,
  `jabatan` enum('TAKMIR MASJID','DIREKTUR TPA','GURU TPA','LAINNYA') NOT NULL,
  `no_wa` varchar(15) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `materi_dipilih`
--

CREATE TABLE `materi_dipilih` (
  `id` int(11) NOT NULL,
  `lembaga_id` int(11) DEFAULT NULL,
  `materi` enum('Berkisah Islami','Motivasi & Muhasabah','Kajian Buka Bersama') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `persetujuan_lembaga`
--

CREATE TABLE `persetujuan_lembaga` (
  `id` int(11) NOT NULL,
  `lembaga_id` int(11) DEFAULT NULL,
  `frekuensi_kunjungan` enum('1','2','3') NOT NULL,
  `persetujuan_ketentuan` tinyint(1) NOT NULL,
  `duta_gnb` tinyint(1) NOT NULL,
  `kesediaan_infaq` tinyint(1) NOT NULL,
  `manfaat` enum('sangat','cukup','kurang') NOT NULL,
  `pemahaman_kerjasama` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `hari_aktif`
--
ALTER TABLE `hari_aktif`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lembaga_id` (`lembaga_id`);

--
-- Indeks untuk tabel `lembaga`
--
ALTER TABLE `lembaga`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `materi_dipilih`
--
ALTER TABLE `materi_dipilih`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lembaga_id` (`lembaga_id`);

--
-- Indeks untuk tabel `persetujuan_lembaga`
--
ALTER TABLE `persetujuan_lembaga`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lembaga_id` (`lembaga_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `hari_aktif`
--
ALTER TABLE `hari_aktif`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `lembaga`
--
ALTER TABLE `lembaga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `materi_dipilih`
--
ALTER TABLE `materi_dipilih`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `persetujuan_lembaga`
--
ALTER TABLE `persetujuan_lembaga`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `hari_aktif`
--
ALTER TABLE `hari_aktif`
  ADD CONSTRAINT `hari_aktif_ibfk_1` FOREIGN KEY (`lembaga_id`) REFERENCES `lembaga` (`id`);

--
-- Ketidakleluasaan untuk tabel `materi_dipilih`
--
ALTER TABLE `materi_dipilih`
  ADD CONSTRAINT `materi_dipilih_ibfk_1` FOREIGN KEY (`lembaga_id`) REFERENCES `lembaga` (`id`);

--
-- Ketidakleluasaan untuk tabel `persetujuan_lembaga`
--
ALTER TABLE `persetujuan_lembaga`
  ADD CONSTRAINT `persetujuan_lembaga_ibfk_1` FOREIGN KEY (`lembaga_id`) REFERENCES `lembaga` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
