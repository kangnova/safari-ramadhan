<?php
session_start();
require_once '../koneksi.php';
// Cek autentikasi
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Pesan feedback
$message = '';
$messageType = '';

// Proses form submit untuk menambah atau mengupdate paket donasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Jika ada id, berarti update
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        try {
            $stmt = $conn->prepare("UPDATE paket_donasi SET judul = ? WHERE id = ?");
            $stmt->execute([$_POST['judul'], $_POST['id']]);
            $message = "Paket donasi berhasil diupdate!";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    } 
    // Jika tidak ada id, berarti tambah baru
    else {
        try {
            $stmt = $conn->prepare("INSERT INTO paket_donasi (judul) VALUES (?)");
            $stmt->execute([$_POST['judul']]);
            $message = "Paket donasi berhasil ditambahkan!";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Proses hapus paket donasi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM paket_donasi WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Paket donasi berhasil dihapus!";
        $messageType = "success";
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Ambil data untuk editing jika ada parameter id
$editData = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM paket_donasi WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Ambil semua data paket donasi
try {
    $stmt = $conn->query("SELECT * FROM paket_donasi ORDER BY tanggal_update DESC");
    $paketDonasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $messageType = "danger";
    $paketDonasi = [];
}

// Function untuk format rupiah (konsistensi dengan template)
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Paket Donasi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Manajemen Paket Donasi</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah/Edit Paket Donasi -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $editData ? 'edit' : 'plus-circle'; ?> me-2"></i>
                        <?php echo $editData ? 'Edit Paket Donasi' : 'Tambah Paket Donasi Baru'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <?php if($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Paket Donasi</label>
                            <input type="text" class="form-control" id="judul" name="judul" 
                                   value="<?php echo $editData ? htmlspecialchars($editData['judul']) : ''; ?>" required>
                        </div>
                        
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?php echo $editData ? 'save' : 'plus-circle'; ?> me-1"></i>
                                <?php echo $editData ? 'Update' : 'Simpan'; ?>
                            </button>
                            
                            <?php if($editData): ?>
                            <a href="profil_donasi.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Daftar Paket Donasi -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Paket Donasi</h5>
                </div>
                <div class="card-body">
                    <?php if(count($paketDonasi) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Judul</th>
                                    <th>Tanggal Update</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($paketDonasi as $paket): ?>
                                <tr>
                                    <td><?php echo $paket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($paket['judul']); ?></td>
                                    <td><?php echo $paket['tanggal_update']; ?></td>
                                    <td>
                                        <a href="profil_donasi.php?edit=<?php echo $paket['id']; ?>" class="btn btn-primary btn-sm me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="profil_donasi.php?delete=<?php echo $paket['id']; ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus paket donasi ini?');" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        Belum ada paket donasi. Silakan tambahkan paket baru.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>