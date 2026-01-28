<?php
session_start();
require_once 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['pengisi_id'])) {
    header("Location: login_p.php");
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT js.*, l.nama_lembaga, l.alamat, l.no_wa, l.share_loc,
               ps.nama_pendamping, p.no_hp as no_hp_pendamping
        FROM jadwal_safari js 
        JOIN lembaga l ON js.lembaga_id = l.id 
        LEFT JOIN pendamping_safari ps ON js.id = ps.jadwal_id
        LEFT JOIN pendamping p ON ps.pendamping_id = p.id
        WHERE js.pengisi = ? 
        ORDER BY js.tanggal ASC, js.jam ASC
    ");
    $stmt->execute([$_SESSION['pengisi_nama']]);
    $jadwal = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengisi - Safari Ramadhan 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Safari Ramadhan 2025</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Selamat datang, <?php echo $_SESSION['pengisi_nama']; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Ganti Password</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Jadwal Safari Ramadhan</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
    <tr>
        <th>No</th>
        <th>Tanggal</th>
        <th>Jam</th>
        <th>Lembaga</th>
        <th>Alamat</th>
        <th>Pendamping</th>
        <th>Maps</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
</thead>
<tbody>
    <?php if ($jadwal): ?>
        <?php foreach ($jadwal as $index => $row): ?>
            <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                <td><?php echo date('H:i', strtotime($row['jam'])); ?></td>
                <td><?php echo htmlspecialchars($row['nama_lembaga']); ?></td>
                <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                <td>
                    <?php echo htmlspecialchars($row['nama_pendamping'] ?? '-'); ?>
                    <?php if (!empty($row['no_hp_pendamping'])): ?>
                        <br>
                        <a href="https://wa.me/<?php echo $row['no_hp_pendamping']; ?>" target="_blank" class="text-success text-decoration-none">
                            <small><i class="bi bi-whatsapp"></i> <?php echo $row['no_hp_pendamping']; ?></small>
                        </a>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($row['share_loc'])): ?>
                        <a href="<?php echo htmlspecialchars($row['share_loc']); ?>" target="_blank" class="btn btn-secondary btn-sm">
                            <i class="bi bi-geo-alt-fill"></i> Maps
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?php 
                        echo $row['status'] == 'pending' ? 'bg-warning' : 
                            ($row['status'] == 'terlaksana' ? 'bg-success' : 'bg-danger'); 
                    ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </span>
                </td>
                <td>
    <?php
    // Mendapatkan waktu sekarang dalam zona waktu yang benar
    date_default_timezone_set('Asia/Jakarta');
    $now = new DateTime();
    $jadwalDateTime = new DateTime($row['tanggal'] . ' ' . $row['jam']);
    
    // Menambahkan 2 jam setelah jadwal
    $batasWaktu = clone $jadwalDateTime;
    $batasWaktu->modify('+2 hours');
    
    // Cek apakah sekarang adalah waktu yang tepat untuk laporan
    $isWaktuTepat = $now >= $jadwalDateTime && $now <= $batasWaktu;
    
    // Tampilkan button hanya jika waktu tepat dan status masih pending
    if($isWaktuTepat && $row['status'] === 'pending'): 
    ?>
        <button type="button" class="btn btn-primary btn-sm" 
                data-bs-toggle="modal" 
                data-bs-target="#laporModal<?php echo $row['id']; ?>">
            Lapor Admin
        </button>
    <?php endif; ?>

    <!-- Modal untuk setiap baris -->
    <div class="modal fade" id="laporModal<?php echo $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
    <h5 class="modal-title">Lapor Kunjungan - <?php echo htmlspecialchars($row['nama_lembaga']); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<form action="lapor_admin.php" method="POST">
    <div class="modal-body">
        <input type="hidden" name="jadwal_id" value="<?php echo $row['id']; ?>">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="status" class="form-label">Status Kunjungan <span class="text-danger">*</span></label>
                <select class="form-select" name="status" required>
                    <option value="">Pilih Status</option>
                    <option value="terlaksana">Terlaksana</option>
                    <option value="batal">Batal</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="jam_kedatangan" class="form-label">Jam Kedatangan <span class="text-danger">*</span></label>
                <input type="time" class="form-control" name="jam_kedatangan" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="jumlah_santri" class="form-label">Jumlah Santri yang Hadir <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="jumlah_santri" min="0" required>
            </div>
            <div class="col-md-6">
                <label for="jumlah_guru" class="form-label">Jumlah Guru yang Hadir <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="jumlah_guru" min="0" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="pesan_kesan" class="form-label">Pesan & Kesan Lembaga <span class="text-danger">*</span></label>
            <textarea class="form-control" name="pesan_kesan" rows="4" required 
                    placeholder="Tuliskan pesan dan kesan dari lembaga tentang kunjungan ini..."></textarea>
        </div>

        <div class="mb-3">
            <label for="keterangan" class="form-label">Keterangan Tambahan</label>
            <textarea class="form-control" name="keterangan" rows="3" 
                    placeholder="Tuliskan keterangan tambahan jika ada..."></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Nama Pendamping <span class="text-danger">*</span></label>
            
            <div id="pendampingContainer<?php echo $row['id']; ?>">
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="nama_pendamping[]" required
                           placeholder="Masukkan nama pendamping...">
                    <button class="btn btn-outline-secondary hapus-pendamping" type="button" disabled>
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>
            </div>
            
            <button type="button" class="btn btn-sm btn-success mt-2" 
                    onclick="tambahPendamping(<?php echo $row['id']; ?>)">
                <i class="bi bi-plus-circle"></i> Tambah Pendamping
            </button>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="submit" class="btn btn-primary">Kirim Laporan</button>
    </div>
</form>
</div>
        </div>
    </div>
</td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-center">Belum ada jadwal yang ditentukan</td>
        </tr>
    <?php endif; ?>
</tbody>
            </table>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ganti Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="update_password_p.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru (Min. 6 Karakter)</label>
                            <input type="password" class="form-control" name="new_password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" name="confirm_password" required minlength="6">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script untuk menambah dan menghapus pendamping -->
<script>
    function tambahPendamping(id) {
        // Membuat elemen input group baru
        const container = document.getElementById('pendampingContainer' + id);
        const newInputGroup = document.createElement('div');
        newInputGroup.className = 'input-group mb-2';
        
        // Membuat input pendamping
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.className = 'form-control';
        newInput.name = 'nama_pendamping[]';
        newInput.required = true;
        newInput.placeholder = 'Masukkan nama pendamping...';
        
        // Membuat tombol hapus
        const deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn btn-outline-danger hapus-pendamping';
        deleteButton.innerHTML = '<i class="bi bi-trash"></i> Hapus';
        deleteButton.onclick = function() {
            container.removeChild(newInputGroup);
            checkButtonStatus(id);
        };
        
        // Menambahkan input dan tombol ke input group
        newInputGroup.appendChild(newInput);
        newInputGroup.appendChild(deleteButton);
        
        // Menambahkan input group ke container
        container.appendChild(newInputGroup);
        
        // Mengaktifkan/menonaktifkan tombol hapus
        checkButtonStatus(id);
    }
    
    function checkButtonStatus(id) {
        // Mendapatkan semua tombol hapus
        const container = document.getElementById('pendampingContainer' + id);
        const deleteButtons = container.querySelectorAll('.hapus-pendamping');
        
        // Aktifkan semua tombol jika ada lebih dari satu input
        const shouldEnable = deleteButtons.length > 1;
        
        // Iterasi melalui semua tombol untuk mengaktifkan/menonaktifkan
        deleteButtons.forEach(button => {
            button.disabled = !shouldEnable;
        });
    }
</script>
</body>
</html>