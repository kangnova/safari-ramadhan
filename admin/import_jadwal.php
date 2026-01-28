<?php
session_start();
require_once '../koneksi.php';

// Check auth
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

// Fetch list of valid pengisi for reference
$pengisi_list = [];
try {
    $stmt = $conn->query("SELECT nama FROM pengisi ORDER BY nama ASC");
    $pengisi_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    // Handle error
}

$preview_data = [];
$error_message = '';
$valid_pengisi_names = array_map('strtolower', $pengisi_list); // For case-insensitive comparison

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['csv_file']['tmp_name'];
        if (($handle = fopen($tmpName, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            
            $row_num = 2; // Start from row 2 (1 is header)
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Determine if row is empty or just ID
                if (empty($data[2]) && empty($data[3]) && empty($data[4])) {
                    $row_num++;
                    continue; // Skip rows with no schedule info
                }

                $id_lembaga = trim($data[0]);
                $nama_lembaga = trim($data[1]);
                $tanggal = trim($data[2]);
                $jam = trim($data[3]);
                $pengisi = trim($data[4]);

                $status = 'OK';
                $notes = [];

                // Basic validation
                if (empty($tanggal)) {
                    $status = 'ERROR';
                    $notes[] = 'Tanggal kosong';
                } else {
                    // Try to convert format DD-MM-YYYY to YYYY-MM-DD
                    // Assume input is DD-MM-YYYY or DD/MM/YYYY
                    $dateObj = DateTime::createFromFormat('d-m-Y', $tanggal);
                    if (!$dateObj) $dateObj = DateTime::createFromFormat('d/m/Y', $tanggal);
                    
                    if ($dateObj) {
                        $tanggal_db = $dateObj->format('Y-m-d');
                    } else {
                        $status = 'ERROR';
                        $notes[] = 'Format tanggal salah (gunakan DD-MM-YYYY)';
                        $tanggal_db = $tanggal;
                    }
                }

                if (empty($jam)) {
                    $status = 'ERROR';
                    $notes[] = 'Jam kosong';
                }

                // Pengisi validation
                if (!empty($pengisi)) {
                    if (!in_array(strtolower($pengisi), $valid_pengisi_names)) {
                        $status = 'WARNING';
                        $notes[] = 'Nama pengisi tidak dikenal (akan tersimpan sebagai teks biasa)';
                    }
                } else {
                    $status = 'ERROR';
                    $notes[] = 'Pengisi kosong';
                }

                $preview_data[] = [
                    'row' => $row_num,
                    'id_lembaga' => $id_lembaga,
                    'nama_lembaga' => $nama_lembaga,
                    'tanggal_raw' => $tanggal,
                    'tanggal_db' => $tanggal_db ?? '',
                    'jam' => $jam,
                    'pengisi' => $pengisi,
                    'status' => $status,
                    'notes' => implode(', ', $notes)
                ];
                $row_num++;
            }
            fclose($handle);
            
            // Save preview data to session for processing
            $_SESSION['import_preview'] = $preview_data;
        }
    } else {
        $error_message = 'Gagal mengupload file.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Jadwal - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Import Jadwal dari Excel/CSV</h2>
                    <a href="jadwal.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>

                <!-- Step 1: Download & Upload -->
                <?php if (empty($preview_data)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Langkah 1: Download Template & Upload</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h6>1. Download Template</h6>
                                <p class="text-muted small">Template ini sudah berisi daftar nama lembaga. Anda tinggal mengisi kolom Tanggal, Jam, dan Pengisi.</p>
                                <a href="download_template.php" class="btn btn-info text-white mb-3">
                                    <i class="bi bi-download"></i> Download Template CSV
                                </a>
                                
                                <div class="alert alert-info small">
                                    <strong>Tips Pengisian:</strong>
                                    <ul class="mb-0 ps-3">
                                        <li>Jangan ubah kolom <strong>ID Lembaga</strong>.</li>
                                        <li>Format Tanggal: <strong>DD-MM-YYYY</strong> (Contoh: 15-03-2025).</li>
                                        <li>Format Jam: <strong>HH:MM</strong> (Contoh: 16:30).</li>
                                        <li>Isi kolom <strong>Pengisi</strong> sesuai nama di daftar kanan.</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>2. Upload File CSV</h6>
                                <p class="text-muted small">Upload file CSV yang sudah diisi.</p>
                                
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <input class="form-control" type="file" name="csv_file" accept=".csv" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-upload"></i> Upload & Validasi
                                    </button>
                                </form>
                                <?php if($error_message): ?>
                                    <div class="alert alert-danger mt-2"><?= $error_message ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Referensi Nama Pengisi Resmi</h6>
                    </div>
                    <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                        <div class="row">
                            <?php foreach ($pengisi_list as $p): ?>
                                <div class="col-md-3 mb-1"><span class="badge bg-light text-dark border"><?= htmlspecialchars($p) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Step 2: Preview & Confirm -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Langkah 2: Preview & Validasi</h5>
                        <form action="process_import.php" method="POST">
                            <button type="submit" class="btn btn-success" onclick="return confirm('Yakin ingin memproses data ini?')">
                                <i class="bi bi-check-circle"></i> Proses Import
                            </button>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Baris</th>
                                        <th>Lembaga</th>
                                        <th>Tanggal</th>
                                        <th>Jam</th>
                                        <th>Pengisi</th>
                                        <th>Status</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $has_errors = false;
                                    foreach ($preview_data as $row): 
                                        $bg_class = '';
                                        $icon = '';
                                        if ($row['status'] === 'OK') {
                                            $bg_class = 'text-success';
                                            $icon = '<i class="bi bi-check-circle-fill"></i>';
                                        } elseif ($row['status'] === 'WARNING') {
                                            $bg_class = 'text-warning';
                                            $icon = '<i class="bi bi-exclamation-triangle-fill"></i>';
                                        } else {
                                            $bg_class = 'text-danger';
                                            $icon = '<i class="bi bi-x-circle-fill"></i>';
                                            $has_errors = true;
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $row['row'] ?></td>
                                        <td><?= htmlspecialchars($row['nama_lembaga']) ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_raw']) ?></td>
                                        <td><?= htmlspecialchars($row['jam']) ?></td>
                                        <td class="<?= $row['status'] === 'WARNING' ? 'fw-bold text-warning' : '' ?>">
                                            <?= htmlspecialchars($row['pengisi']) ?>
                                        </td>
                                        <td class="<?= $bg_class ?>"><?= $icon ?> <?= $row['status'] ?></td>
                                        <td class="small text-muted"><?= $row['notes'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($has_errors): ?>
                    <div class="card-footer bg-danger text-white">
                        <i class="bi bi-exclamation-circle"></i> Terdapat data dengan status ERROR. Baris tersebut tidak akan disimpan.
                    </div>
                    <?php endif; ?>
                </div>

                <div class="text-center">
                    <a href="import_jadwal.php" class="btn btn-outline-secondary">Batal / Upload Ulang</a>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
