<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

// Proses tambah pendamping
if(isset($_POST['tambah'])) {
    try {
        $nama = $_POST['nama'];
        $no_hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];
        
        // Proses upload foto
        $foto = NULL;
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = 'foto_pendamping_' . time() . '.' . $ext;
                $db_path = 'img/pendamping/' . $new_filename; 
                $upload_path = '../' . $db_path; 
                
                // Buat direktori jika belum ada
                $upload_dir = '../img/pendamping/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    $foto = $db_path;
                }
            }
        }
        
        $query = "INSERT INTO pendamping (nama, no_hp, alamat, foto) 
                 VALUES (:nama, :no_hp, :alamat, :foto)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $nama,
            ':no_hp' => $no_hp,
            ':alamat' => $alamat,
            ':foto' => $foto
        ]);
        
        $_SESSION['success'] = "Data pendamping berhasil ditambahkan: " . $nama;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses update status
if(isset($_POST['update_status'])) {
    try {
        $id = $_POST['id'];
        $status = $_POST['status'];
        
        $query = "UPDATE pendamping SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Status berhasil diupdate!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses upload foto
if(isset($_POST['update_foto'])) {
    try {
        $id = $_POST['id'];
        
        // Ambil info foto lama
        $query_old = "SELECT foto FROM pendamping WHERE id = :id";
        $stmt_old = $conn->prepare($query_old);
        $stmt_old->execute([':id' => $id]);
        $old_data = $stmt_old->fetch();
        
        // Proses upload foto baru
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = 'foto_pendamping_' . time() . '.' . $ext;
                $db_path = 'img/pendamping/' . $new_filename; 
                $upload_path = '../' . $db_path; 
                
                // Buat direktori jika belum ada
                $upload_dir = '../img/pendamping/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // Hapus foto lama jika ada
                    if($old_data['foto']) {
                        $old_file_path = '../' . $old_data['foto'];
                        if(file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    
                    // Update database dengan foto baru
                    $query = "UPDATE pendamping SET foto = :foto WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':foto' => $db_path, 
                        ':id' => $id
                    ]);
                    
                    $_SESSION['success'] = "Foto berhasil diupdate!";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $error = "Gagal mengupload foto!";
                }
            } else {
                $error = "Jenis file tidak diizinkan! Hanya file JPG, JPEG, PNG, dan GIF yang diperbolehkan.";
            }
        } else {
            $error = "Silakan pilih foto terlebih dahulu!";
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}


// Proses update data pendamping
if(isset($_POST['update_pendamping'])) {
    try {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $no_hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];
        
        $query = "UPDATE pendamping SET nama = :nama, no_hp = :no_hp, alamat = :alamat 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $nama,
            ':no_hp' => $no_hp,
            ':alamat' => $alamat,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Data pendamping berhasil diupdate!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses hapus pendamping (using delete functional file would be better, but doing inline/using same delete file if generic)
// For now, I'll rely on a delete link to a delete handler or just modify this file if a delete post exists.
// The user asked for crud, deleting usually done via separate file or POST. 
// I will create a simple delete handler here to be safe or link to a new delete_pendamping.php if needed.
// But the plan checklist item said "Create delete_pendamping.php" wasn't explicitly there but implied in CRUD.
// For simplicity and following `pengisi.php` pattern: `pengisi.php` links to `delete_pengisi.php`.
// I'll create `delete_pendamping.php` separately later or just handle logic here if I can.
// Let's check `delete_pengisi.php` first? No, I'll just make a `delete_pendamping.php` file next.


// Ambil data pendamping
$pendamping_list = [];
try {
    $query = "SELECT * FROM pendamping ORDER BY nama ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pendamping_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendamping - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .profile-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        .profile-img-modal {
            width: 100%;
            max-height: 300px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    
    <?php require_once 'includes/header.php'; ?>
    
    <div class="container my-4">
        <div>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Data Pendamping</h1>
                    
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                        <i class='bx bx-plus'></i> Tambah Pendamping
                    </button>
                </div>

                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabel Pendamping -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama</th>
                                        <th>No HP</th>
                                        <th>Alamat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach($pendamping_list as $pendamping): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="text-center">
                                            <?php if(!empty($pendamping['foto']) && file_exists('../' . $pendamping['foto'])): ?>
                                                <img src="../<?= $pendamping['foto'] ?>" class="profile-img" alt="Foto <?= $pendamping['nama'] ?>" onclick="showImage('../<?= $pendamping['foto'] ?>', '<?= $pendamping['nama'] ?>')">
                                            <?php else: ?>
                                                <img src="../img/pengisi/default.jpg" class="profile-img" alt="Foto Default">
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary mt-1" 
                                                    onclick="uploadFoto(<?= $pendamping['id'] ?>)">
                                                <i class='bx bx-upload'></i>
                                            </button>
                                        </td>
                                        <td><?= htmlspecialchars($pendamping['nama']) ?></td>
                                        <td>
                                            <a href="https://wa.me/<?= $pendamping['no_hp'] ?>" 
                                            class="text-decoration-none" 
                                            target="_blank">
                                                <?= $pendamping['no_hp'] ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($pendamping['alamat']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $pendamping['status'] == 'aktif' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($pendamping['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                             <button class="btn btn-sm btn-primary" 
                                                    title="Edit Pendamping"
                                                    onclick="editPendamping(<?= $pendamping['id'] ?>, 
                                                                    '<?= htmlspecialchars($pendamping['nama']) ?>', 
                                                                    '<?= htmlspecialchars($pendamping['no_hp']) ?>', 
                                                                    '<?= htmlspecialchars(addslashes($pendamping['alamat'])) ?>')">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editStatus(<?= $pendamping['id'] ?>, '<?= $pendamping['status'] ?>')" title="Update Status">
                                                <i class='bx bx-refresh'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $pendamping['id'] ?>)" title="Hapus Pendamping">
                                                <i class='bx bx-trash'></i>
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
</div>

    <!-- Modal Tambah Pendamping -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pendamping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto</label>
                            <input type="file" name="foto" class="form-control">
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF (Max 2MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Status -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="pendamping_id">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Non Aktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Upload Foto -->
    <div class="modal fade" id="uploadFotoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="pendamping_id_foto">
                        <div class="mb-3">
                            <label class="form-label">Pilih Foto</label>
                            <input type="file" name="foto" class="form-control" required>
                            <small class="text-muted">Format: JPG, JPEG, PNG, GIF (Max 2MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_foto" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Show Image -->
    <div class="modal fade" id="showImageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Foto Pendamping</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="profile-img-modal" alt="Foto Pendamping">
                </div>
            </div>
        </div>
    </div>

<!-- Modal Edit Pendamping -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Pendamping</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_pendamping_id">
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No HP</label>
                        <input type="text" name="no_hp" id="edit_no_hp" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" id="edit_alamat" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_pendamping" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
            }
        });
    });

    function uploadFoto(id) {
        document.getElementById('pendamping_id_foto').value = id;
        new bootstrap.Modal(document.getElementById('uploadFotoModal')).show();
    }

    function showImage(src, name) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModalTitle').innerText = 'Foto ' + name;
        new bootstrap.Modal(document.getElementById('showImageModal')).show();
    }

    function editStatus(id, status) {
        document.getElementById('pendamping_id').value = id;
        const statusSelect = document.querySelector('select[name="status"]');
        statusSelect.value = status;
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    }

    function confirmDelete(id) {
        if(confirm('Apakah Anda yakin ingin menghapus pendamping ini?')) {
            window.location.href = 'delete_pendamping.php?id=' + id;
        }
    }
    
    function editPendamping(id, nama, no_hp, alamat) {
        document.getElementById('edit_pendamping_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_no_hp').value = no_hp;
        document.getElementById('edit_alamat').value = alamat;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
    </script>
</body>
</html>
