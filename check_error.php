<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . phpversion() . "<br>";

try {
    require_once 'koneksi.php';
    echo "Koneksi Database Berhasil!";
} catch (Exception $e) {
    echo "Error Koneksi: " . $e->getMessage();
}
?>
