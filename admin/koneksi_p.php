<?php
$host = 'localhost';
$user = 'gnborid_safariramadhan2025';  // sesuaikan dengan username database Anda
$pass = 'gnborid_safariramadhan2025';      // sesuaikan dengan password database Anda
$db   = 'gnborid_safariramadhan2025';

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>