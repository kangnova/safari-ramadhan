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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_nominal'])) {
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

// Proses update urutan
if (isset($_POST['update_order'])) {
    try {
        if (isset($_POST['order']) && is_array($_POST['order'])) {
            $stmt = $conn->prepare("UPDATE nominal_donasi SET urutan = ? WHERE id = ?");
            foreach ($_POST['order'] as $id => $order) {
                // Urutan dari SortableJS bisa 0-based, kita tambah 1 agar human friendly
                // Atau cukup ambil value yang dikirim hidden input (sudah +1 di JS)
                $stmt->execute([$order, $id]);
            }
            $message = "Urutan berhasil diperbarui!";
            $messageType = "success";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
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
        $message = "Status berhasil diubah!";
        $messageType = "success";
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
        .page-header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-custom {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            overflow: hidden;
        }
        
        .table-custom th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
        }
        
        .drag-handle {
            cursor: move;
            color: #adb5bd;
            transition: color 0.2s;
        }
        
        .drag-handle:hover {
            color: #495057;
        }
        
        .emoji-cell {
            font-size: 1.5rem;
            width: 50px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        
        <!-- Header & Action -->
        <div class="page-header">
            <div>
                <h3 class="mb-0 fw-bold text-primary">Manajemen Nominal Donasi</h3>
                <p class="text-muted mb-0 mt-1">Atur pilihan nominal donasi yang muncul di halaman donasi.</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nominalModal" onclick="resetForm()">
                <i class="fas fa-plus-circle me-2"></i>Tambah Nominal
            </button>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Table View -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <form action="" method="post" id="orderForm">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="nominalTable">
                            <thead class="bg-light">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th width="80" class="text-center">Emoji</th>
                                    <th>Nominal</th>
                                    <th>Deskripsi</th>
                                    <th width="100" class="text-center">Status</th>
                                    <th width="150" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="sortableList">
                                <?php if(count($nominalDonasi) > 0): ?>
                                    <?php foreach($nominalDonasi as $item): ?>
                                    <tr data-id="<?php echo $item['id']; ?>" 
                                        data-nominal="<?php echo $item['nominal']; ?>"
                                        data-emoji="<?php echo htmlspecialchars($item['emoji']); ?>"
                                        data-deskripsi="<?php echo htmlspecialchars($item['deskripsi']); ?>"
                                        data-urutan="<?php echo $item['urutan']; ?>"
                                        data-active="<?php echo $item['is_active']; ?>">
                                        
                                        <td class="text-center">
                                            <div class="drag-handle py-2"><i class="fas fa-grip-vertical"></i></div>
                                            <!-- Hidden input for order -->
                                            <input type="hidden" name="order[<?php echo $item['id']; ?>]" value="<?php echo $item['urutan']; ?>" class="order-input">
                                        </td>
                                        <td class="text-center emoji-cell"><?php echo $item['emoji']; ?></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?php echo formatRupiah($item['nominal']); ?></span>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo !empty($item['deskripsi']) ? htmlspecialchars($item['deskripsi']) : '-'; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="?toggle=<?php echo $item['id']; ?>" class="badge rounded-pill bg-<?php echo $item['is_active'] ? 'success' : 'secondary'; ?> text-decoration-none">
                                                <?php echo $item['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editNominal(this)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus nominal ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data nominal donasi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(count($nominalDonasi) > 0): ?>
                    <div class="p-3 bg-light border-top text-end">
                        <button type="submit" name="update_order" class="btn btn-success btn-sm">
                            <i class="fas fa-sort-amount-down me-2"></i>Simpan Urutan
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="nominalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Nominal Donasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="nominal_id">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nominal</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control" name="nominal" id="input_nominal" required placeholder="Contoh: 100000">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Emoji</label>
                                <input type="text" class="form-control text-center" name="emoji" id="input_emoji" required placeholder="💰">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-bold">Urutan</label>
                                <input type="number" class="form-control" name="urutan" id="input_urutan" value="<?php echo count($nominalDonasi) + 1; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi / Label</label>
                            <input type="text" class="form-control" name="deskripsi" id="input_deskripsi" placeholder="Contoh: Paket Sedekah Subuh">
                        </div>
                        
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="input_active" checked>
                            <label class="form-check-label" for="input_active">Aktifkan Pilihan Ini</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit_nominal" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // SortableJS init
        const el = document.getElementById('sortableList');
        if (el) {
            new Sortable(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    // Update hidden input urutan
                    const items = el.querySelectorAll('tr');
                    items.forEach((item, index) => {
                        const input = item.querySelector('.order-input');
                        if (input) input.value = index + 1;
                    });
                }
            });
        }

        // JS for handling Modal Edit/Add
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Tambah Nominal Donasi';
            document.getElementById('nominal_id').value = '';
            document.getElementById('input_nominal').value = '';
            document.getElementById('input_emoji').value = '';
            document.getElementById('input_deskripsi').value = '';
            document.getElementById('input_urutan').value = '<?php echo count($nominalDonasi) + 1; ?>';
            document.getElementById('input_active').checked = true;
        }

        function editNominal(btn) {
            const tr = btn.closest('tr');
            const data = tr.dataset;
            
            document.getElementById('modalTitle').textContent = 'Edit Nominal Donasi';
            document.getElementById('nominal_id').value = data.id;
            document.getElementById('input_nominal').value = data.nominal;
            document.getElementById('input_emoji').value = data.emoji;
            document.getElementById('input_deskripsi').value = data.deskripsi;
            document.getElementById('input_urutan').value = data.urutan;
            document.getElementById('input_active').checked = (data.active == '1');
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('nominalModal'));
            modal.show();
        }
    </script>
</body>
</html>