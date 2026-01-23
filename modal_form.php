<?php
require_once 'koneksi.php';

// Cek jumlah pendaftar di database
try {
    // Cek total lembaga safari
    $query = "SELECT COUNT(*) as total FROM lembaga";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_lembaga = $result['total'];

    // Cek total pendaftar ifthar
    $query = "SELECT COUNT(*) as total FROM ifthar";
    $stmt = $conn->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_ifthar = $result['total'];

} catch (PDOException $e) {
    $total_lembaga = 0;
    $total_ifthar = 0;
}

// Cek batas tanggal
$batas_tanggal = strtotime('2025-02-20');
$tanggal_sekarang = strtotime(date('Y-m-d'));
$is_expired = $tanggal_sekarang >= $batas_tanggal;

// Cek kuota
$is_safari_full = $total_lembaga >= 170;
$is_ifthar_full = $total_ifthar >= 43;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Safari Ramadhan & Ifthar 1000 Santri</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        .swal2-popup {
            font-size: 1rem !important;
        }
        .text-left {
            text-align: left !important;
        }
    </style>
</head>
<body>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    showFormOptions();
});

function showFormOptions() {
    const isExpired = <?php echo $is_expired ? 'true' : 'false' ?>;
    const isSafariFull = <?php echo $is_safari_full ? 'true' : 'false' ?>;
    const isIftharFull = <?php echo $is_ifthar_full ? 'true' : 'false' ?>;
    
    if (isExpired || isSafariFull) {
        // Jika Safari sudah tutup tapi Ifthar masih buka
        if (!isIftharFull) {
            Swal.fire({
                title: 'Pendaftaran Ifthar 1000 Santri',
                html: `
                    <p class="mb-4">Pendaftaran Safari Ramadhan telah ditutup.</p>
                    <p>Silakan melanjutkan ke pendaftaran Ifthar 1000 Santri.</p>
                    <p class="mb-0 text-muted"><small>Sisa kuota: ${200 - <?= $total_ifthar ?>}</small></p>
                `,
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#20B2AA',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Lanjutkan ke Form Ifthar',
                cancelButtonText: 'Tutup'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'form_ifthar.php';
                } else {
                    window.location.href = 'index.php';
                }
            });
        } else {
            // Jika kedua program sudah penuh
            Swal.fire({
                title: 'Pendaftaran Ditutup',
                html: 'Mohon maaf, kuota pendaftaran untuk Safari Ramadhan dan Ifthar 1000 Santri telah penuh.',
                icon: 'info',
                confirmButtonColor: '#d33',
                confirmButtonText: 'Tutup'
            }).then(() => {
                window.location.href = 'index.php';
            });
        }
    } else {
        // Jika masih dalam periode pendaftaran Safari
        Swal.fire({
            title: 'Pilih Form Pendaftaran',
            html: `
                <div class="text-left">
                    <p class="mb-3">Silakan pilih form pendaftaran:</p>
                    <p>1. Form Safari Ramadhan 1446 H/2025</p>
                    <p class="text-muted"><small>Sisa kuota: ${170 - <?= $total_lembaga ?>}</small></p>
                    ${!isIftharFull ? `
                    <p>2. Form Ifthar 1000 Santri</p>
                    <p class="text-muted"><small>Sisa kuota: ${200 - <?= $total_ifthar ?>}</small></p>
                    ` : ''}
                </div>
            `,
            icon: 'question',
            showDenyButton: !isIftharFull,
            showCancelButton: true,
            confirmButtonText: 'Safari Ramadhan',
            denyButtonText: 'Ifthar 1000 Santri',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#20B2AA',
            denyButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'form.php';
            } else if (result.isDenied) {
                window.location.href = 'form_ifthar.php';
            } else {
                window.location.href = 'index.php';
            }
        });
    }
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>