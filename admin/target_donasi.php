<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Inisialisasi pesan
$message = '';
$messageType = '';

// Ambil data target donasi aktif
$stmt = $conn->query("SELECT * FROM target_donasi WHERE is_active = 1 LIMIT 1");
$targetDonasi = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika tidak ada data aktif, coba ambil data terakhir
if (!$targetDonasi) {
    $stmt = $conn->query("SELECT * FROM target_donasi ORDER BY id DESC LIMIT 1");
    $targetDonasi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika masih tidak ada data, buat data default
    if (!$targetDonasi) {
        $targetDonasi = [
            'id' => 0,
            'jumlah' => 0,
            'deskripsi' => '',
            'tanggal_mulai' => date('Y-m-d'),
            'tanggal_selesai' => date('Y-m-d', strtotime('+40 days')),
            'is_active' => 1
        ];
    }
}

// Proses update target donasi
if (isset($_POST['update'])) {
    $jumlah = str_replace(['Rp ', '.'], '', $_POST['jumlah']); // Hapus format Rupiah
    $jumlah = (float) str_replace(',', '.', $jumlah); // Konversi ke float
    $deskripsi = $_POST['deskripsi'];
    $tanggalMulai = $_POST['tanggal_mulai'];
    $tanggalSelesai = $_POST['tanggal_selesai'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        // Jika ada data, update
        if ($targetDonasi['id'] > 0) {
            $sql = "UPDATE target_donasi SET jumlah = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$jumlah, $deskripsi, $tanggalMulai, $tanggalSelesai, $isActive, $targetDonasi['id']]);
        } else {
            // Jika tidak ada data, insert baru
            $sql = "INSERT INTO target_donasi (jumlah, deskripsi, tanggal_mulai, tanggal_selesai, is_active) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$jumlah, $deskripsi, $tanggalMulai, $tanggalSelesai, $isActive]);
        }
        
        // Nonaktifkan target donasi lain jika yang ini aktif
        if ($isActive) {
            $sql = "UPDATE target_donasi SET is_active = 0 WHERE id != ? AND is_active = 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$targetDonasi['id'] > 0 ? $targetDonasi['id'] : $conn->lastInsertId()]);
        }
        
        $message = "Target donasi berhasil diperbarui.";
        $messageType = "success";
        
        // Refresh data setelah update
        $stmt = $conn->query("SELECT * FROM target_donasi WHERE is_active = 1 LIMIT 1");
        $newTarget = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newTarget) {
            $targetDonasi = $newTarget;
        } else if ($targetDonasi['id'] > 0) {
            // Jika tidak ada yang aktif, ambil yang baru diupdate
            $stmt = $conn->prepare("SELECT * FROM target_donasi WHERE id = ?");
            $stmt->execute([$targetDonasi['id']]);
            $targetDonasi = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Format angka dalam rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Hitung sisa hari kampanye
function hitungSisaHari($tanggalSelesai) {
    $sekarang = new DateTime();
    $selesai = new DateTime($tanggalSelesai);
    $selisih = $sekarang->diff($selesai);
    
    if ($selesai < $sekarang) {
        return 0; // Kampanye sudah selesai
    }
    
    return $selisih->days;
}

// Ambil total donasi sukses untuk perhitungan persentase
try {
    $stmt = $conn->query("SELECT SUM(nominal) as total FROM donasi WHERE status = 'success'");
    $totalDonasi = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    $totalDonasi = 0; // Default jika tabel donasi belum ada
}

// Hitung persentase pencapaian
$persentase = 0;
if ($targetDonasi['jumlah'] > 0) {
    $persentase = min(100, ($totalDonasi / $targetDonasi['jumlah']) * 100);
}

// Hitung sisa hari
$sisaHari = hitungSisaHari($targetDonasi['tanggal_selesai']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Donasi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

        
        .target-preview {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Target Donasi</h2>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Update Target Donasi</h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="jumlah" class="form-label">Target Jumlah Donasi</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" class="form-control" id="jumlah" name="jumlah" value="<?php echo number_format($targetDonasi['jumlah'], 0, ',', '.'); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo htmlspecialchars($targetDonasi['deskripsi']); ?></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $targetDonasi['tanggal_mulai']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="<?php echo $targetDonasi['tanggal_selesai']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $targetDonasi['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">Aktif</label>
                                </div>
                                
                                <button type="submit" name="update" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Simpan Target Donasi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Preview</h5>
                        </div>
                        <div class="card-body">
                            <div class="target-preview">
                                <h4>Ifthar Ramadhan</h4>
                                <h3 class="text-success mb-2"><?php echo formatRupiah($totalDonasi); ?></h3>
                                <p class="text-muted">Target Donasi <?php echo formatRupiah($targetDonasi['jumlah']); ?></p>
                                <div class="progress mb-2" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $persentase; ?>%"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-3">
                                    <div>
                                        <span class="fw-bold"><?php echo $persentase; ?>%</span>
                                        <span class="text-muted ms-1">Tercapai</span>
                                    </div>
                                    <div>
                                        <span class="fw-bold"><?php echo $sisaHari; ?></span>
                                        <span class="text-muted ms-1">Hari</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i> Preview ini menunjukkan tampilan target donasi di halaman utama berdasarkan data saat ini.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format input jumlah dengan format Rupiah
        document.getElementById('jumlah').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value === '') return;
            
            value = parseInt(value);
            this.value = new Intl.NumberFormat('id-ID').format(value);
        });
        
        // Validasi tanggal
        document.querySelector('form').addEventListener('submit', function(e) {
            const tanggalMulai = new Date(document.getElementById('tanggal_mulai').value);
            const tanggalSelesai = new Date(document.getElementById('tanggal_selesai').value);
            
            if (tanggalSelesai < tanggalMulai) {
                e.preventDefault();
                alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai!');
            }
        });
    </script>
</body>
</html>