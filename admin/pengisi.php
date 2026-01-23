<?php
session_start();

// Password protection
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

require_once '../koneksi.php';

// Fungsi untuk generate username dari nama
function generateUsername($nama) {
    // Hilangkan "Kak " atau "Ustadz " dari nama
    $clean_name = preg_replace('/^(Kak|Ustadz)\s+/i', '', $nama);
    
    // Ubah menjadi lowercase, hilangkan spasi dan karakter khusus
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $clean_name));
    
    // Pastikan minimal 4 karakter, tambahkan nama kembali jika terlalu pendek
    if (strlen($username) < 4) {
        $username .= strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nama));
    }
    
    return $username;
}

// Proses tambah pengisi
if(isset($_POST['tambah'])) {
    try {
        $nama = $_POST['nama'];
        $username = generateUsername($nama);
        $no_hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];
        // Generate password default 6 digit
        $default_password = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        
        // Proses upload foto
        $foto = NULL;
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = 'foto_' . time() . '.' . $ext;
                $db_path = 'img/pengisi/' . $new_filename;  // Path yang disimpan di database (tanpa ../)
                $upload_path = '../' . $db_path;  // Path lengkap untuk upload file
                
                // Buat direktori jika belum ada
                $upload_dir = '../img/pengisi/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    $foto = $db_path;  // Simpan path relatif di database
                }
            }
        }
        
        // Periksa apakah username sudah ada
        $check_query = "SELECT COUNT(*) FROM pengisi WHERE username = :username";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([':username' => $username]);
        $username_exists = $check_stmt->fetchColumn();
        
        // Jika username sudah ada, tambahkan angka di belakang
        if($username_exists > 0) {
            $username .= rand(1, 99);
        }
        
        $query = "INSERT INTO pengisi (nama, username, password, no_hp, alamat, foto) 
                 VALUES (:nama, :username, :password, :no_hp, :alamat, :foto)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $nama,
            ':username' => $username,
            ':password' => $hashed_password,
            ':no_hp' => $no_hp,
            ':alamat' => $alamat,
            ':foto' => $foto
        ]);
        
        $_SESSION['success'] = "Data pengisi berhasil ditambahkan dengan username: " . $username . " dan password: " . $default_password;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses generate password baru
if(isset($_POST['generate_password'])) {
    try {
        $id = $_POST['id'];
        $new_password = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $query = "UPDATE pengisi SET password = :password WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':password' => $hashed_password,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Password baru berhasil digenerate: " . $new_password;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Proses generate username baru
if(isset($_POST['generate_username'])) {
    try {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $new_username = generateUsername($nama) . rand(1, 99);
        
        $query = "UPDATE pengisi SET username = :username WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':username' => $new_username,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Username baru berhasil digenerate: " . $new_username;
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
        
        $query = "UPDATE pengisi SET status = :status WHERE id = :id";
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
        $query_old = "SELECT foto FROM pengisi WHERE id = :id";
        $stmt_old = $conn->prepare($query_old);
        $stmt_old->execute([':id' => $id]);
        $old_data = $stmt_old->fetch();
        
        // Proses upload foto baru
        if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['foto']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = 'foto_' . time() . '.' . $ext;
                $db_path = 'img/pengisi/' . $new_filename;  // Path yang disimpan di database (tanpa ../)
                $upload_path = '../' . $db_path;  // Path lengkap untuk upload file
                
                // Buat direktori jika belum ada
                $upload_dir = '../img/pengisi/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    // Hapus foto lama jika ada
                    if($old_data['foto']) {
                        $old_file_path = '../' . $old_data['foto'];  // Path lengkap ke file lama
                        if(file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }
                    
                    // Update database dengan foto baru
                    $query = "UPDATE pengisi SET foto = :foto WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':foto' => $db_path,  // Simpan path relatif di database
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


// Proses update data pengisi
if(isset($_POST['update_pengisi'])) {
    try {
        $id = $_POST['id'];
        $nama = $_POST['nama'];
        $no_hp = $_POST['no_hp'];
        $alamat = $_POST['alamat'];
        
        $query = "UPDATE pengisi SET nama = :nama, no_hp = :no_hp, alamat = :alamat 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':nama' => $nama,
            ':no_hp' => $no_hp,
            ':alamat' => $alamat,
            ':id' => $id
        ]);
        
        $_SESSION['success'] = "Data pengisi berhasil diupdate!";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
// Generate username untuk pengisi yang belum memiliki username
try {
    $check_query = "SELECT COUNT(*) FROM pengisi WHERE username IS NULL OR username = ''";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute();
    $missing_usernames = $check_stmt->fetchColumn();
    
    if($missing_usernames > 0) {
        $query = "SELECT id, nama FROM pengisi WHERE username IS NULL OR username = ''";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $pengisi_without_username = $stmt->fetchAll();
        
        foreach($pengisi_without_username as $pengisi) {
            $username = generateUsername($pengisi['nama']);
            
            // Periksa apakah username sudah ada
            $check_query = "SELECT COUNT(*) FROM pengisi WHERE username = :username";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->execute([':username' => $username]);
            $username_exists = $check_stmt->fetchColumn();
            
            // Jika username sudah ada, tambahkan angka di belakang
            if($username_exists > 0) {
                $username .= rand(1, 99);
            }
            
            $update_query = "UPDATE pengisi SET username = :username WHERE id = :id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([
                ':username' => $username,
                ':id' => $pengisi['id']
            ]);
        }
        
        $_SESSION['success'] = "Username berhasil digenerate untuk " . $missing_usernames . " pengisi!";
    }
} catch(PDOException $e) {
    $error = "Error saat generate username: " . $e->getMessage();
}

// Ambil data pengisi
$pengisi_list = [];
try {
    $query = "SELECT * FROM pengisi ORDER BY nama ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pengisi_list = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengisi - Admin</title>
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
                    <h1 class="h2">Data Pengisi</h1>
                    
                    <a href="jadwal.php" class="btn btn-success">
                        <i class='bx bx-user-plus'></i> Tambah Jadwal
                    </a>
                    
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                        <i class='bx bx-plus'></i> Tambah Pengisi
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

                <!-- Tabel Pengisi -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>No HP</th>
                                        <th>Alamat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach($pengisi_list as $pengisi): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="text-center">
                                            <?php if(!empty($pengisi['foto']) && file_exists('../' . $pengisi['foto'])): ?>
                                                <img src="../<?= $pengisi['foto'] ?>" class="profile-img" alt="Foto <?= $pengisi['nama'] ?>" onclick="showImage('../<?= $pengisi['foto'] ?>', '<?= $pengisi['nama'] ?>')">
                                            <?php else: ?>
                                                <img src="../img/pengisi/default.jpg" class="profile-img" alt="Foto Default">
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-primary mt-1" 
                                                    onclick="uploadFoto(<?= $pengisi['id'] ?>)">
                                                <i class='bx bx-upload'></i>
                                            </button>
                                        </td>
                                        <td><?= htmlspecialchars($pengisi['nama']) ?></td>
                                        <td>
                                            <?= $pengisi['username'] ?? '-' ?>
                                            <button class="btn btn-sm btn-secondary ms-2" 
                                                    onclick="generateUsername(<?= $pengisi['id'] ?>, '<?= htmlspecialchars($pengisi['nama']) ?>')">
                                                <i class='bx bx-refresh'></i> Generate
                                            </button>
                                        </td>
                                        <td>
                                            <span class="text-muted"><i class='bx bx-lock-alt'></i> Terenkripsi</span>
                                            <button class="btn btn-sm btn-info ms-2" 
                                                    onclick="generatePassword(<?= $pengisi['id'] ?>)">
                                                <i class='bx bx-refresh'></i> Generate
                                            </button>
                                        </td>
                                        <td>
                                            <a href="https://wa.me/<?= $pengisi['no_hp'] ?>" 
                                            class="text-decoration-none" 
                                            target="_blank">
                                                <?= $pengisi['no_hp'] ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($pengisi['alamat']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $pengisi['status'] == 'aktif' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($pengisi['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                             <button class="btn btn-sm btn-primary" 
            onclick="editPengisi(<?= $pengisi['id'] ?>, 
                                '<?= htmlspecialchars($pengisi['nama']) ?>', 
                                '<?= htmlspecialchars($pengisi['no_hp']) ?>', 
                                '<?= htmlspecialchars(addslashes($pengisi['alamat'])) ?>')">
        <i class='bx bx-edit'></i>
    </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick="editStatus(<?= $pengisi['id'] ?>, '<?= $pengisi['status'] ?>')">
                                                <i class='bx bx-refresh'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?= $pengisi['id'] ?>)">
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

    <!-- Modal Tambah Pengisi -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengisi</h5>
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
                        <input type="hidden" name="id" id="pengisi_id">
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

    <!-- Modal Generate Password -->
    <div class="modal fade" id="generatePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Password Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="pengisi_id_password">
                        <p>Anda yakin ingin generate password baru untuk pengisi ini?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="generate_password" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Generate Username -->
    <div class="modal fade" id="generateUsernameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Username Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="pengisi_id_username">
                        <input type="hidden" name="nama" id="pengisi_nama_username">
                        <p>Anda yakin ingin generate username baru untuk pengisi ini?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="generate_username" class="btn btn-primary">Generate</button>
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
                        <input type="hidden" name="id" id="pengisi_id_foto">
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
                    <h5 class="modal-title" id="imageModalTitle">Foto Pengisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="profile-img-modal" alt="Foto Pengisi">
                </div>
            </div>
        </div>
    </div>

<!-- Modal Edit Pengisi -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data Pengisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_pengisi_id">
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
                    <button type="submit" name="update_pengisi" class="btn btn-primary">Simpan</button>
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

    function generatePassword(id) {
        document.getElementById('pengisi_id_password').value = id;
        new bootstrap.Modal(document.getElementById('generatePasswordModal')).show();
    }

    function generateUsername(id, nama) {
        document.getElementById('pengisi_id_username').value = id;
        document.getElementById('pengisi_nama_username').value = nama;
        new bootstrap.Modal(document.getElementById('generateUsernameModal')).show();
    }

    function uploadFoto(id) {
        document.getElementById('pengisi_id_foto').value = id;
        new bootstrap.Modal(document.getElementById('uploadFotoModal')).show();
    }

    function showImage(src, name) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModalTitle').innerText = 'Foto ' + name;
        new bootstrap.Modal(document.getElementById('showImageModal')).show();
    }

    function editStatus(id, status) {
        document.getElementById('pengisi_id').value = id;
        const statusSelect = document.querySelector('select[name="status"]');
        statusSelect.value = status;
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    }

    function confirmDelete(id) {
        if(confirm('Apakah Anda yakin ingin menghapus pengisi ini?')) {
            window.location.href = 'delete_pengisi.php?id=' + id;
        }
    }
    
    // Tambahkan fungsi ini di dalam tag <script> pada bagian bawah file

function editPengisi(id, nama, no_hp, alamat) {
    document.getElementById('edit_pengisi_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_no_hp').value = no_hp;
    document.getElementById('edit_alamat').value = alamat;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
    </script>
</body>
</html>