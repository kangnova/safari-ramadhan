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

// Proses form submit untuk menambah atau mengupdate nominal donasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Jika ada id, berarti update
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        try {
            $stmt = $conn->prepare("UPDATE nominal_donasi SET nominal = ?, emoji = ?, deskripsi = ?, urutan = ?, is_active = ? WHERE id = ?");
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $stmt->execute([
                $_POST['nominal'], 
                $_POST['emoji'], 
                $_POST['deskripsi'], 
                $_POST['urutan'], 
                $is_active,
                $_POST['id']
            ]);
            $message = "Nominal donasi berhasil diupdate!";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    } 
    // Jika tidak ada id, berarti tambah baru
    else {
        try {
            $stmt = $conn->prepare("INSERT INTO nominal_donasi (nominal, emoji, deskripsi, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $stmt->execute([
                $_POST['nominal'], 
                $_POST['emoji'], 
                $_POST['deskripsi'], 
                $_POST['urutan'], 
                $is_active
            ]);
            $message = "Nominal donasi berhasil ditambahkan!";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Proses hapus nominal donasi
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM nominal_donasi WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $message = "Nominal donasi berhasil dihapus!";
        $messageType = "success";
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Toggle status aktif
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    try {
        $stmt = $conn->prepare("UPDATE nominal_donasi SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_GET['toggle']]);
        $message = "Status nominal donasi berhasil diubah!";
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
        $stmt = $conn->prepare("SELECT * FROM nominal_donasi WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Ambil semua data nominal donasi
try {
    $stmt = $conn->query("SELECT * FROM nominal_donasi ORDER BY urutan ASC");
    $nominalDonasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Error: " . $e->getMessage();
    $messageType = "danger";
    $nominalDonasi = [];
}

// Function untuk format rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Nominal Donasi - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>

        
        .nominal-preview {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            background-color: #f8f9fa;
        }
        
        .nominal-option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .nominal-option:hover {
            background-color: #f8f9fa;
        }
        
        .nominal-option.selected {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.1);
        }
        
        .emoji {
            font-size: 24px;
            margin-right: 15px;
            min-width: 40px;
            text-align: center;
        }
        
        .nominal-text {
            font-weight: 500;
            flex-grow: 1;
        }
        
        .nominal-item {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .drag-handle {
            cursor: move;
            padding: 5px;
            color: #777;
        }
        

    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid mt-4">
            <h2 class="mb-4">Manajemen Nominal Donasi</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Form Tambah/Edit Nominal Donasi -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php echo $editData ? 'edit' : 'plus-circle'; ?> me-2"></i>
                        <?php echo $editData ? 'Edit Nominal Donasi' : 'Tambah Nominal Donasi Baru'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <?php if($editData): ?>
                        <input type="hidden" name="id" value="<?php echo $editData['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nominal" class="form-label">Nominal</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" id="nominal" name="nominal" 
                                           value="<?php echo $editData ? $editData['nominal'] : ''; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="emoji" class="form-label">Emoji</label>
                                <input type="text" class="form-control" id="emoji" name="emoji" 
                                       value="<?php echo $editData ? htmlspecialchars($editData['emoji']) : ''; ?>" required>
                                <div class="form-text">Contoh: 💰, 💸, 💎, 💍</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <input type="text" class="form-control" id="deskripsi" name="deskripsi" 
                                       value="<?php echo $editData ? htmlspecialchars($editData['deskripsi']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="urutan" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="urutan" name="urutan" 
                                       value="<?php echo $editData ? $editData['urutan'] : count($nominalDonasi) + 1; ?>" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                   <?php echo (!$editData || $editData['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>
                        
                        <!-- Preview -->
                        <div class="nominal-preview">
                            <h6>Preview:</h6>
                            <div class="nominal-option">
                                <div class="emoji" id="preview-emoji"><?php echo $editData ? htmlspecialchars($editData['emoji']) : '💰'; ?></div>
                                <div class="nominal-text" id="preview-nominal"><?php echo $editData ? formatRupiah($editData['nominal']) : 'Rp 100.000'; ?></div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-<?php echo $editData ? 'save' : 'plus-circle'; ?> me-1"></i>
                                <?php echo $editData ? 'Update' : 'Simpan'; ?>
                            </button>
                            
                            <?php if($editData): ?>
                            <a href="nominal_donasi.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Batal
                            </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Daftar Nominal Donasi -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Daftar Nominal Donasi</h5>
                </div>
                <div class="card-body">
                    <?php if(count($nominalDonasi) > 0): ?>
                    <form action="" method="post" id="order-form">
                        <div id="nominal-list" class="mb-3">
                            <?php foreach($nominalDonasi as $item): ?>
                            <div class="nominal-item d-flex align-items-center" data-id="<?php echo $item['id']; ?>">
                                <div class="drag-handle me-2">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                <div class="me-3 emoji">
                                    <?php echo htmlspecialchars($item['emoji']); ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1"><?php echo formatRupiah($item['nominal']); ?></h5>
                                    <?php if(!empty($item['deskripsi'])): ?>
                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($item['deskripsi']); ?></p>
                                    <?php endif; ?>
                                    <input type="hidden" name="order[<?php echo $item['id']; ?>]" value="<?php echo $item['urutan']; ?>" class="order-input">
                                </div>
                                <div class="ms-auto d-flex">
                                    <a href="?toggle=<?php echo $item['id']; ?>" 
                                       class="btn btn-<?php echo $item['is_active'] ? 'success' : 'secondary'; ?> btn-sm me-2" 
                                       title="<?php echo $item['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>">
                                        <i class="fas fa-<?php echo $item['is_active'] ? 'eye' : 'eye-slash'; ?>"></i>
                                    </a>
                                    <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm me-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus nominal donasi ini?');" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" name="update_order" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Simpan Urutan
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info mb-0">
                        Belum ada nominal donasi. Silakan tambahkan nominal baru.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live preview saat input diubah
            const nominalInput = document.getElementById('nominal');
            const emojiInput = document.getElementById('emoji');
            const previewEmoji = document.getElementById('preview-emoji');
            const previewNominal = document.getElementById('preview-nominal');
            
            if (nominalInput && previewNominal) {
                nominalInput.addEventListener('input', function() {
                    const formatted = new Intl.NumberFormat('id-ID', { 
                        style: 'currency', 
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(this.value);
                    
                    previewNominal.textContent = formatted;
                });
            }
            
            if (emojiInput && previewEmoji) {
                emojiInput.addEventListener('input', function() {
                    previewEmoji.textContent = this.value;
                });
            }
            
            // Inisialisasi Sortable untuk drag & drop urutan
            const nominalList = document.getElementById('nominal-list');
            if (nominalList) {
                new Sortable(nominalList, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function() {
                        // Update nilai input urutan setelah drag & drop
                        const items = nominalList.querySelectorAll('.nominal-item');
                        items.forEach((item, index) => {
                            const id = item.getAttribute('data-id');
                            const input = item.querySelector('.order-input');
                            input.value = index + 1;
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>