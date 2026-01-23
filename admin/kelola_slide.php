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
                    $target_dir = "../img/slider/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;

                    // Cek ekstensi file
                    $allowed = array('jpg', 'jpeg', 'png');
                    if (!in_array($file_extension, $allowed)) {
                        throw new Exception('Hanya file JPG, JPEG & PNG yang diperbolehkan');
                    }

                    // Upload file
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        // Insert ke database
                        $query = "INSERT INTO slider (judul, gambar, urutan, status) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([
                            $_POST['judul'],
                            $new_filename,
                            $_POST['urutan'],
                            $_POST['status']
                        ]);
                        $_SESSION['success'] = "Slide berhasil ditambahkan";
                    } else {
                        throw new Exception('Gagal mengupload file');
                    }
                }
            } else if ($_POST['action'] == 'edit') {
                // Update data
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
                    // Jika ada upload gambar baru
                    $target_dir = "../img/slider/";
                    $file_extension = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;

                    // Cek ekstensi file
                    $allowed = array('jpg', 'jpeg', 'png');
                    if (!in_array($file_extension, $allowed)) {
                        throw new Exception('Hanya file JPG, JPEG & PNG yang diperbolehkan');
                    }

                    // Upload file
                    if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                        // Hapus gambar lama
                        $stmt = $conn->prepare("SELECT gambar FROM slider WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        $old_image = $stmt->fetchColumn();
                        if ($old_image && file_exists("../img/slider/" . $old_image)) {
                            unlink("../img/slider/" . $old_image);
                        }

                        // Update database dengan gambar baru
                        $query = "UPDATE slider SET judul = ?, gambar = ?, urutan = ?, status = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([
                            $_POST['judul'],
                            $new_filename,
                            $_POST['urutan'],
                            $_POST['status'],
                            $_POST['id']
                        ]);
                    } else {
                        throw new Exception('Gagal mengupload file');
                    }
                } else {
                    // Update tanpa gambar baru
                    $query = "UPDATE slider SET judul = ?, urutan = ?, status = ? WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        $_POST['judul'],
                        $_POST['urutan'],
                        $_POST['status'],
                        $_POST['id']
                    ]);
                }
                $_SESSION['success'] = "Slide berhasil diupdate";
            } else if ($_POST['action'] == 'delete') {
                // Hapus gambar
                $stmt = $conn->prepare("SELECT gambar FROM slider WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists("../img/slider/" . $image)) {
                    unlink("../img/slider/" . $image);
                }

                // Hapus data dari database
                $query = "DELETE FROM slider WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_POST['id']]);
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
    $query = "SELECT * FROM slider ORDER BY urutan ASC";
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
    <title>Kelola Slider - Admin Safari Ramadhan</title>
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
                <h1 class="h3">Kelola Slider</h1>
                <p class="text-muted">Kelola gambar slider untuk halaman utama website</p>
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
                    <table class="table table-hover" id="slideTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Gambar</th>
                                <th>Judul</th>
                                <th>Urutan</th>
                                <th>Status</th>
                                <th>Update</th>
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
                                    <img src="../img/slider/<?= htmlspecialchars($slide['gambar']) ?>" 
                                         alt="<?= htmlspecialchars($slide['judul']) ?>"
                                         class="img-thumbnail"
                                         style="max-width: 100px;">
                                </td>
                                <td><?= htmlspecialchars($slide['judul']) ?></td>
                                <td><?= $slide['urutan'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $slide['status'] == 'aktif' ? 'success' : 'danger' ?>">
                                        <?= ucfirst($slide['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($slide['tgl_update'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editSlideModal"
                                            data-id="<?= $slide['id'] ?>"
                                            data-judul="<?= htmlspecialchars($slide['judul']) ?>"
                                            data-urutan="<?= $slide['urutan'] ?>"
                                            data-status="<?= $slide['status'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-btn"
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Slide Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*" required>
                            <small class="text-muted">Format: JPG, JPEG, PNG</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="urutan" value="0" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Slide</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul</label>
                            <input type="text" class="form-control" name="judul" id="edit_judul" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Gambar</label>
                            <input type="file" class="form-control" name="gambar" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah gambar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Urutan</label>
                            <input type="number" class="form-control" name="urutan" id="edit_urutan" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
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
    <!-- Scripts (lanjutan) -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
   <script>
       $(document).ready(function() {
           // Inisialisasi DataTable
           $('#slideTable').DataTable({
               "language": {
                   "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
               }
           });

           // Edit Slide
           $('.edit-btn').click(function() {
               const id = $(this).data('id');
               const judul = $(this).data('judul');
               const urutan = $(this).data('urutan');
               const status = $(this).data('status');

               $('#edit_id').val(id);
               $('#edit_judul').val(judul);
               $('#edit_urutan').val(urutan);
               $('#edit_status').val(status);
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

           // Preview gambar sebelum upload
           $('input[type="file"]').change(function(e) {
               const file = e.target.files[0];
               if (file) {
                   const reader = new FileReader();
                   reader.onload = function(e) {
                       const preview = $('<img>').attr({
                           'src': e.target.result,
                           'class': 'img-thumbnail mt-2',
                           'style': 'max-width: 200px'
                       });
                       $(e.target).next('.preview').remove();
                       $(e.target).after(preview);
                   }
                   reader.readAsDataURL(file);
               }
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