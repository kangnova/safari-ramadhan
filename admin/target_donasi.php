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

// Ambil konfigurasi target donasi aktif
// Kita asumsikan hanya ada 1 record konfigurasi di tabel target_donasi yang kita pakai
$stmt = $conn->query("SELECT * FROM target_donasi ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Jika belum ada config, buat dummy object
if (!$config) {
    $config = ['id' => 0, 'program_id' => 0, 'is_active' => 1];
}

// Proses update konfigurasi
if (isset($_POST['update_config'])) {
    $programId = $_POST['program_id'];
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    try {
        if ($config['id'] > 0) {
            // Update existing
            $sql = "UPDATE target_donasi SET program_id = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$programId, $isActive, $config['id']]);
        } else {
            // Insert new
            $sql = "INSERT INTO target_donasi (program_id, is_active, jumlah, deskripsi, tanggal_mulai, tanggal_selesai) VALUES (?, ?, 0, '', NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$programId, $isActive]);
            
            // Refresh id
            $config['id'] = $conn->lastInsertId();
        }
        
        $message = "Konfigurasi Program Unggulan berhasil diperbarui.";
        $messageType = "success";
        
        // Refresh config data
        $stmt = $conn->prepare("SELECT * FROM target_donasi WHERE id = ?");
        $stmt->execute([$config['id']]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $message = "Terjadi kesalahan: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Ambil daftar semua program donasi untuk dropdown
$stmt = $conn->query("SELECT * FROM program_donasi ORDER BY created_at DESC");
$programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil detail program yang terpilih untuk preview
$selectedProgram = null;
if ($config['program_id']) {
    foreach ($programs as $p) {
        if ($p['id'] == $config['program_id']) {
            $selectedProgram = $p;
            break;
        }
    }
}

// Helper untuk format rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Helper sisa hari
function hitungSisaHari($tanggalSelesai) {
    $sekarang = new DateTime();
    $selesai = new DateTime($tanggalSelesai);
    if ($selesai < $sekarang) return 0;
    return $sekarang->diff($selesai)->days;
}

// Hitung statistik donasi untuk program terpilih
$totalDonasi = 0;
$persentase = 0;
$sisaHari = 0;

if ($selectedProgram) {
    try {
        // Fetch real donation total for this program
        $stmt = $conn->prepare("SELECT SUM(nominal) as total FROM donasi WHERE status = 'success' AND program_id = ?");
        $stmt->execute([$selectedProgram['id']]);
        $totalDonasi = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Calculate percentage
        if ($selectedProgram['target_nominal'] > 0) {
            $persentase = min(100, ($totalDonasi / $selectedProgram['target_nominal']) * 100);
        }
        
        $sisaHari = hitungSisaHari($selectedProgram['tanggal_selesai']);
        
    } catch (PDOException $e) {
        // Silent error
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program Unggulan - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
        <h2 class="mb-4">Pengaturan Program Unggulan</h2>
        <p class="text-muted">Pilih program donasi yang akan ditampilkan sebagai target utama di halaman donasi.</p>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Pilih Program</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="mb-4">
                                <label for="program_id" class="form-label fw-bold">Program Donasi</label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">-- Pilih Program --</option>
                                    <?php foreach ($programs as $program): ?>
                                    <option value="<?php echo $program['id']; ?>" <?php echo ($config['program_id'] == $program['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program['judul']); ?> 
                                        (Target: <?php echo formatRupiah($program['target_nominal']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Data detail (judul, deskripsi, target) diambil otomatis dari data Program Donasi.</div>
                            </div>
                            
                            <div class="mb-4 form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?php echo ($config['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Aktifkan Fitur Target Donasi</label>
                            </div>
                            
                            <button type="submit" name="update_config" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Pengaturan
                            </button>
                            <a href="program_donasi.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-edit me-2"></i>Kelola Data Program
                            </a>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-muted mb-3"><i class="fas fa-eye me-2"></i>Preview Singkat</h6>
                        
                        <?php if ($selectedProgram): ?>
                            <div class="bg-white p-3 rounded border">
                                <h5 class="text-primary mb-1"><?php echo htmlspecialchars($selectedProgram['judul']); ?></h5>
                                <div class="text-muted small mb-3">Status: <span class="badge bg-<?php echo ($selectedProgram['status'] == 'active') ? 'success' : 'secondary'; ?>"><?php echo ucfirst($selectedProgram['status']); ?></span></div>
                                
                                <div class="mb-2 d-flex justify-content-between">
                                    <span>Terkumpul:</span>
                                    <span class="fw-bold text-success"><?php echo formatRupiah($totalDonasi); ?></span>
                                </div>
                                <div class="mb-3 d-flex justify-content-between">
                                    <span>Target:</span>
                                    <span class="fw-bold"><?php echo formatRupiah($selectedProgram['target_nominal']); ?></span>
                                </div>
                                
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $persentase; ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><?php echo number_format($persentase, 1); ?>% tercapai</span>
                                    <span><?php echo $sisaHari; ?> hari lagi</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Belum ada program yang dipilih.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>