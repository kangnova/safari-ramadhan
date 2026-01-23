<?php
require_once 'koneksi.php';

// Cek jumlah pendaftar di database
try {
    $currentYear = date('Y');
    
    // Cek total lembaga safari (Current Year)
    $query = "SELECT COUNT(*) as total FROM lembaga WHERE YEAR(created_at) = :tahun";
    $stmt = $conn->prepare($query);
    $stmt->execute(['tahun' => $currentYear]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_lembaga = $result['total'];

    // Cek total pendaftar ifthar (Current Year)
    $query = "SELECT COUNT(*) as total FROM ifthar WHERE YEAR(created_at) = :tahun";
    $stmt = $conn->prepare($query);
    $stmt->execute(['tahun' => $currentYear]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_ifthar = $result['total'];

} catch (PDOException $e) {
    echo "<!-- Database Error: " . $e->getMessage() . " -->";
    $total_lembaga = 0;
    $total_ifthar = 0;
}

// Cek batas tanggal
$batas_tanggal = strtotime('2025-02-20');
$tanggal_sekarang = strtotime(date('Y-m-d'));
$is_expired = $tanggal_sekarang >= $batas_tanggal;

// Cek kuota
// Ambil setting quota
$stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'safari_quota'");
$stmtQ->execute();
$quotaSafari = (int)$stmtQ->fetchColumn();
if($quotaSafari == 0) $quotaSafari = 170;

$stmtQ = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'ifthar_quota'");
$stmtQ->execute();
$quotaIfthar = (int)$stmtQ->fetchColumn();
if($quotaIfthar == 0) $quotaIfthar = 200;

$is_safari_full = $total_lembaga >= $quotaSafari;
$is_ifthar_full = $total_ifthar >= $quotaIfthar;
?>

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
                    <p class="mb-0 text-muted"><small>Sisa kuota: ${<?= $quotaIfthar ?> - <?= $total_ifthar ?>}</small></p>
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
                    <p class="text-muted"><small>Sisa kuota: ${<?= $quotaSafari ?> - <?= $total_lembaga ?>}</small></p>
                    ${!isIftharFull ? `
                    <p>2. Form Ifthar 1000 Santri</p>
                    <p class="text-muted"><small>Sisa kuota: ${<?= $quotaIfthar ?> - <?= $total_ifthar ?>}</small></p>
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
                // Stay on form.php (Safari)
                // Need to remove disabled state if we allow proceed, BUT here we are inside logic that runs if not full?
                // Wait. form.php includes this ONLY IF quota is full.
                // Ah! The original logic of modal_form.php was to run on Index or as a Gatekeeper.
                
                // User Request: "jika batas kuota sudah tercapai maka tampilkan modal_form"
                // So this script runs ONLY when quota IS full (via form.php include).
                // But modal_form.php logic has an "else" block (Line 45 in replacement) for when it's NOT full.
                // If included in form.php inside `if($quotaFull)`, then `$is_safari_full` will be true.
                // So it will correctly fall into the first block (Expired or Full).
                
                // However, I should probably remove the "else" block or redundant checks if it's ONLY used for Quota Full now.
                // BUT, to keep it "dynamic" and potentially reusable, I'll leave the logic but rely on the variables.
                // Since $is_safari_full will be calculated at top of file, it should be correct.
                
                // One detail: In form.php, I disabled inputs.
                // If the user clicks "Back" or "Close", they stay on the disabled form. Correct.
                
                // What if they click "Safari Ramadhan" in the "Else" block? (Which shouldn't happen if quota is full).
                // They stay on form.php.
                
                // OK, logic holds.
            } else if (result.isDenied) {
                window.location.href = 'form_ifthar.php';
            } else {
                window.location.href = 'index.php';
            }
        });
    }
}
</script>