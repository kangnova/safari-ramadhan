<?php
session_start();
require_once '../koneksi.php';

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Handle file upload dan insert/update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                // Upload gambar
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    $target_dir = "../img/slides/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;

                    // Cek ekstensi file
                    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    if (!in_array($file_extension, $allowed)) {
                        throw new Exception('Hanya file JPG, JPEG, PNG, GIF & WEBP yang diperbolehkan');
                    }

                    // Upload file
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        // Insert ke database
                        $query = "INSERT INTO hero_slides (judul, deskripsi, gambar, link, urutan, aktif) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([
                            $_POST['judul'],
                            $_POST['deskripsi'],
                            $new_filename,
                            $_POST['link'],
                            $_POST['urutan'],
                            $_POST['aktif']
                        ]);
                        $_SESSION['success'] = "Slide berhasil ditambahkan";
                    } else {
                        throw new Exception('Gagal mengupload file');
                    }
                } else {
                    throw new Exception('Pilih gambar untuk diupload');
                }
            } else if ($_POST['action'] == 'edit') {
                // Update data
                $id = $_POST['id'];
                
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    // Jika ada upload gambar baru
                    $target_dir = "../img/slides/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;

                    // Cek ekstensi file
                    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                    if (!in_array($file_extension, $allowed)) {
                        throw new Exception('Hanya file JPG, JPEG, PNG, GIF & WEBP yang diperbolehkan');
                    }

                    // Upload file
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        // Hapus gambar lama
                        $stmt = $conn->prepare("SELECT gambar FROM hero_slides WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_image = $stmt->fetchColumn();
                        if ($old_image && file_exists("../img/slides/" . $old_image)) {
                            unlink("../img/slides/" . $old_image);
                        }

                        // Update database dengan gambar baru
                        $query = "UPDATE hero_slides SET judul = ?, deskripsi = ?, gambar = ?, link = ?, urutan = ?, aktif = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([
                            $_POST['judul'],
                            $_POST['deskripsi'],
                            $new_filename,
                            $_POST['link'],
                            $_POST['urutan'],
                            $_POST['aktif'],
                            $id
                        ]);
                    } else {
                        throw new Exception('Gagal mengupload file');
                    }
                } else {
                    // Update tanpa gambar baru
                    $query = "UPDATE hero_slides SET judul = ?, deskripsi = ?, link = ?, urutan = ?, aktif = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $_POST['judul'],
                        $_POST['deskripsi'],
                        $_POST['link'],
                        $_POST['urutan'],
                        $_POST['aktif'],
                        $id
                    ]);
                }
                $_SESSION['success'] = "Slide berhasil diupdate";
            } else if ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                // Hapus gambar
                $stmt = $conn->prepare("SELECT gambar FROM hero_slides WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists("../img/slides/" . $image)) {
                    unlink("../img/slides/" . $image);
                }

                // Hapus data dari database
                $query = "DELETE FROM hero_slides WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$id]);
                $_SESSION['success'] = "Slide berhasil dihapus";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: kelola_slide.php');
    exit();
}

// Ambil data slider
$slides = [];
try {
    $query = "SELECT * FROM hero_slides ORDER BY urutan ASC";
    $stmt = $conn->query($query);
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Slide - Admin Safari Ramadhan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <?php require_once 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3">Kelola Slide Hero Section</h1>
                <p class="text-muted">Kelola gambar, judul, dan deskripsi untuk slider halaman utama</p>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlideModal">
                    <i class="bi bi-plus-circle"></i> Tambah Slide
                </button>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Data Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="slideTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Konten</th>
                                <th>Link</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($slides as $slide): 
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <img src="../img/slides/<?= htmlspecialchars($slide['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($slide['judul']) ?>"
                                         class="img-thumbnail"
                                         style="max-width: 120px;">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($slide['judul']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars(substr($slide['deskripsi'], 0, 100)) ?>...</small>
                                </td>
                                <td><small class="text-muted"><?= htmlspecialchars($slide['link'] ?? '-') ?></small></td>
                                <td><?= $slide['urutan'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $slide['aktif'] == 1 ? 'success' : 'secondary' ?>">
                                        <?= $slide['aktif'] == 1 ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info edit-btn mb-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editSlideModal"
                                            data-id="<?= $slide['id'] ?>"
                                            data-judul="<?= htmlspecialchars($slide['judul']) ?>"
                                            data-deskripsi="<?= htmlspecialchars($slide['deskripsi']) ?>"
                                            data-link="<?= htmlspecialchars($slide['link'] ?? '') ?>"
                                            data-urutan="<?= $slide['urutan'] ?>"
                                            data-aktif="<?= $slide['aktif'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-btn mb-1"
                                            data-id="<?= $slide['id'] ?>"
                                            data-judul="<?= htmlspecialchars($slide['judul']) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Slide Modal -->
    <div class="modal fade" id="addSlideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Slide Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    <input type="text" class="form-control" name="judul" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="deskripsi" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Link CTA (Opsional)</label>
                                    <input type="text" class="form-control" name="link" placeholder="Contoh: #program">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar</label>
                                    <input type="file" class="form-control" name="gambar" accept="image/*" required>
                                    <small class="text-muted d-block mt-1">Disarankan ukuran 1200x600px</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" class="form-control" name="urutan" value="0" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="aktif">
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Slide Modal -->
    <div class="modal fade" id="editSlideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Slide</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    <input type="text" class="form-control" name="judul" id="edit_judul" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi</label>
                                    <textarea class="form-control" name="deskripsi" id="edit_deskripsi" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Link CTA (Opsional)</label>
                                    <input type="text" class="form-control" name="link" id="edit_link">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Gambar</label>
                                    <input type="file" class="form-control" name="gambar" accept="image/*">
                                    <small class="text-muted d-block mt-1">Kosongkan jika tidak ingin mengubah</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Urutan</label>
                                    <input type="number" class="form-control" name="urutan" id="edit_urutan" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="aktif" id="edit_aktif">
                                        <option value="1">Aktif</option>
                                        <option value="0">Nonaktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form id="deleteForm" action="" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            $('#slideTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                },
                "order": [[4, "asc"]] // Order by Urutan
            });

            // Edit Slide
            $('.edit-btn').click(function() {
                const id = $(this).data('id');
                const judul = $(this).data('judul');
                const deskripsi = $(this).data('deskripsi');
                const link = $(this).data('link');
                const urutan = $(this).data('urutan');
                const aktif = $(this).data('aktif');

                $('#edit_id').val(id);
                $('#edit_judul').val(judul);
                $('#edit_deskripsi').val(deskripsi);
                $('#edit_link').val(link);
                $('#edit_urutan').val(urutan);
                $('#edit_aktif').val(aktif);
            });

            // Delete Slide
            $('.delete-btn').click(function() {
                const id = $(this).data('id');
                const judul = $(this).data('judul');

                Swal.fire({
                    title: 'Hapus Slide?',
                    html: `Anda yakin ingin menghapus slide <strong>${judul}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#delete_id').val(id);
                        $('#deleteForm').submit();
                    }
                });
            });

            // Auto-dismiss alerts
            window.setTimeout(function() {
                $(".alert").fadeTo(500, 0).slideUp(500, function(){
                    $(this).remove(); 
                });
            }, 3000);
        });
    </script>
</body>
</html>