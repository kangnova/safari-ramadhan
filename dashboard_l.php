<?php
session_start();
require_once 'koneksi.php';

// Cek status login
if (!isset($_SESSION['lembaga_id'])) {
    header("Location: login_p.php");
    exit();
}

$id_lembaga = $_SESSION['lembaga_id'];
$success_msg = '';
$error_msg = '';

// Handle Session Messages
if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}

try {
    // 1. Ambil Data Lembaga
    $stmt = $conn->prepare("SELECT * FROM lembaga WHERE id = ?");
    $stmt->execute([$id_lembaga]);
    $lembaga = $stmt->fetch();

    if (!$lembaga) {
        session_destroy();
        header("Location: login_p.php");
        exit();
    }

    // 2. Ambil Jadwal Safari
    $stmtJadwal = $conn->prepare("
        SELECT js.*, p.nama as nama_pengisi 
        FROM jadwal_safari js 
        LEFT JOIN pengisi p ON js.pengisi = p.nama
        WHERE js.lembaga_id = ? 
        ORDER BY js.tanggal ASC
    ");
    $stmtJadwal->execute([$id_lembaga]);
    $jadwal_list = $stmtJadwal->fetchAll();

} catch (PDOException $e) {
    $error_msg = "Database Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Lembaga - Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .card-menu {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">Safari Ramadhan (Lembaga)</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link text-white">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($lembaga['nama_lembaga']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Message -->
        <div class="alert alert-success border-0 shadow-sm">
            <h4 class="alert-heading">Ahlan Wa Sahlan!</h4>
            <p>Selamat datang di Dashboard Lembaga <strong><?= htmlspecialchars($lembaga['nama_lembaga']) ?></strong>.</p>
            <hr>
            <p class="mb-0 small">Gunakan dashboard ini untuk memantau jadwal, melaporkan kegiatan, dan mengelola profil lembaga Anda.</p>
        </div>

        <!-- Alerts -->
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show"><?= $success_msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?= $error_msg ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Column: Jadwal & Main Features -->
            <div class="col-lg-8">
                <!-- Section Jadwal -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-calendar-event text-primary"></i> Jadwal Safari Ramadhan Anda</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jadwal_list)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x display-4"></i>
                                <p class="mt-2">Belum ada jadwal yang ditentukan oleh Admin.</p>
                                <small>Mohon menunggu konfirmasi via WhatsApp.</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tanggal & Jam</th>
                                            <th>Pengisi / Muballigh</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($jadwal_list as $row): 
                                            // Status Logic
                                            // Status Logic
                                            $status_cls = 'warning';
                                            if ($row['status'] == 'terlaksana') {
                                                $status_cls = 'success';
                                            } elseif ($row['status'] == 'batal') {
                                                $status_cls = 'danger';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                                <small class="text-muted"><?= date('H:i', strtotime($row['jam'])) ?> WIB</small>
                                            </td>
                                            <td>
                                                <?= $row['pengisi'] ? htmlspecialchars($row['pengisi']) : '<span class="text-muted fst-italic">Belum ditentukan</span>' ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_cls ?>"><?= ucfirst($row['status']) ?></span>
                                            </td>
                                            <td>
                                                <?php if($row['status'] == 'pending'): ?>
                                                    <!-- Tombol Lapor (Visible h-2 to h+2 jam) - Simplified logic used in dashboard_p.php -->
                                                    <?php
                                                    date_default_timezone_set('Asia/Jakarta');
                                                    $now = new DateTime();
                                                    $sch = new DateTime($row['tanggal'] . ' ' . $row['jam']);
                                                    // Allow reporting from schedule time until +4 hours for simplicity
                                                    $limit = clone $sch; $limit->modify('+4 hours'); 
                                                    
                                                    // Enable button if NOW >= Schedule Time
                                                    if ($now >= $sch): 
                                                    ?>
                                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#laporModal<?= $row['id'] ?>">
                                                            <i class="bi bi-pencil-square"></i> Lapor
                                                        </button>
                                                    <?php else: ?>
                                                        <small class="text-muted"><i class="bi bi-clock"></i> Belum mulai</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-success"><i class="bi bi-check-circle"></i> Selesai</span>
                                                <?php endif; ?>

                                                <!-- Modal Lapor -->
                                                <div class="modal fade" id="laporModal<?= $row['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <form action="lapor_admin.php" method="POST" enctype="multipart/form-data">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Laporan Kegiatan</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="jadwal_id" value="<?= $row['id'] ?>">
                                                                    <input type="hidden" name="redirect_to" value="dashboard_l.php">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Status Pelaksanaan</label>
                                                                        <select name="status" class="form-select" required>
                                                                            <option value="terlaksana">Terlaksana</option>
                                                                            <option value="batal">Batal / Tidak Terlaksana</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="row g-2">
                                                                        <div class="col-6 mb-3">
                                                                            <label class="form-label">Jml Santri</label>
                                                                            <input type="number" name="jumlah_santri" class="form-control" required placeholder="0">
                                                                        </div>
                                                                        <div class="col-6 mb-3">
                                                                            <label class="form-label">Jml Guru</label>
                                                                            <input type="number" name="jumlah_guru" class="form-control" required placeholder="0">
                                                                        </div>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Bukti Kegiatan (Foto)</label>
                                                                        <input type="file" name="bukti_kegiatan[]" class="form-control" accept=".jpg,.jpeg,.png,.pdf" multiple required>
                                                                        <small class="text-muted">Bisa upload lebih dari 1 foto. Format: JPG, PNG, PDF. Maks 2MB/file.</small>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Pesan & Kesan</label>
                                                                        <textarea name="pesan_kesan" class="form-control" rows="3" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                    <button type="submit" class="btn btn-primary">Kirim Laporan</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Profile & Menu -->
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="bi bi-building fs-1 text-secondary"></i>
                            </div>
                        </div>
                        <h5 class="card-title"><?= htmlspecialchars($lembaga['nama_lembaga']) ?></h5>
                        <p class="text-muted small mb-2"><?= htmlspecialchars($lembaga['alamat']) ?></p>
                        <span class="badge bg-info text-dark"><?= htmlspecialchars($lembaga['kecamatan']) ?></span>
                        
                        <hr>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil"></i> Edit Profil
                            </button>
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="bi bi-key"></i> Ganti Password
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contact Admin Card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-envelope"></i> Hubungi Admin</h5>
                    </div>
                    <div class="card-body">
                        <form action="kirim_pesan.php" method="POST" class="mb-3">
                            <div class="mb-2">
                                <input type="text" name="subjek" class="form-control form-control-sm" placeholder="Subjek / Judul Pesan" required>
                            </div>
                            <div class="mb-2">
                                <textarea name="pesan" class="form-control form-control-sm" rows="3" placeholder="Tulis pesan Anda disini..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="bi bi-send"></i> Kirim Pesan
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <span class="text-muted small">Atau hubungi via WhatsApp:</span>
                            <div class="d-grid mt-2">
                                <a href="https://wa.me/6285741000999?text=Assalamu'alaikum%20Admin%20Safari%20Ramadhan,%20saya%20dari%20Lembaga%20<?= urlencode($lembaga['nama_lembaga']) ?>%20ingin%20bertanya..." target="_blank" class="btn btn-success btn-sm">
                                    <i class="bi bi-whatsapp"></i> Chat WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Donasi Card -->
                <div class="card shadow-sm bg-gradient text-white" style="background: linear-gradient(45deg, #198754, #20c997);">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-heart-fill"></i> Ingin Berdonasi?</h5>
                        <p class="card-text small">Dukung program dakwah kami.</p>
                        <a href="donasi.php" class="btn btn-light btn-sm w-100 text-success fw-bold">Donasi Sekarang</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="update_lembaga.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profil Lembaga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lembaga</label>
                            <input type="text" name="nama_lembaga" class="form-control" value="<?= htmlspecialchars($lembaga['nama_lembaga']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Penanggung Jawab</label>
                            <input type="text" name="pj" class="form-control" value="<?= htmlspecialchars($lembaga['penanggung_jawab']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No. WhatsApp</label>
                            <input type="text" name="no_wa" id="noWaInputEdit" class="form-control" value="<?= htmlspecialchars($lembaga['no_wa']) ?>" required>
                            <small class="text-muted d-block" style="font-size: 0.8em;">*Otomatis diawali 62</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" required><?= htmlspecialchars($lembaga['alamat']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="update_password_l.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ganti Password</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Simpan Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script formatting WA untuk Edit Profil
        document.addEventListener('DOMContentLoaded', function() {
            const waInput = document.getElementById('noWaInputEdit');
            if(waInput) {
                waInput.addEventListener('input', function(e) {
                    let val = this.value.replace(/[^0-9]/g, '');
                    if (val.startsWith('0')) {
                        val = '62' + val.substring(1);
                    }
                    this.value = val;
                });
                
                waInput.addEventListener('blur', function(e) {
                    let val = this.value;
                    if (val.length > 0 && !val.startsWith('62')) {
                        this.value = '62' + val;
                    }
                });
            }
        });
    </script>
</body>
</html>
