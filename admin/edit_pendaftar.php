<?php
session_start();
require_once '../koneksi.php';

// Cek login
if (!isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Ambil ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    die("ID Invalid");
}

// Proses Update Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Format No WA (Auto convert 08xx -> 628xx)
        $formatted_wa = $_POST['no_wa'];
        // Remove non-numeric characters first just in case
        $formatted_wa = preg_replace('/[^0-9]/', '', $formatted_wa);
        if (substr($formatted_wa, 0, 1) === '0') {
            $formatted_wa = '62' . substr($formatted_wa, 1);
        }

        // 1. Update Tabel Lembaga
        $stmt = $conn->prepare("UPDATE lembaga SET 
            nama_lembaga = ?, 
            alamat = ?, 
            kecamatan = ?, 
            jumlah_santri = ?, 
            jam_aktif = ?, 
            penanggung_jawab = ?, 
            jabatan = ?, 
            no_wa = ?, 
            email = ?,
            share_loc = ?
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['nama_lembaga'],
            $_POST['alamat'],
            $_POST['kecamatan'],
            $_POST['jumlah_santri'],
            $_POST['jam_aktif'],
            $_POST['pj'],
            $_POST['jabatan'],
            $formatted_wa, // Use formatted WA
            $_POST['email'],
            $_POST['share_loc'],
            $id
        ]);

        // 2. Update Hari Aktif (Delete insert strategy)
        $conn->prepare("DELETE FROM hari_aktif WHERE lembaga_id = ?")->execute([$id]);
        if (!empty($_POST['hari_aktif'])) {
            $stmt_hari = $conn->prepare("INSERT INTO hari_aktif (lembaga_id, hari) VALUES (?, ?)");
            foreach ($_POST['hari_aktif'] as $hari) {
                $stmt_hari->execute([$id, $hari]);
            }
        }

        // 3. Update Materi (Delete insert strategy)
        $conn->prepare("DELETE FROM materi_dipilih WHERE lembaga_id = ?")->execute([$id]);
        if (!empty($_POST['materi'])) {
            $stmt_materi = $conn->prepare("INSERT INTO materi_dipilih (lembaga_id, materi) VALUES (?, ?)");
            foreach ($_POST['materi'] as $materi) {
                $stmt_materi->execute([$id, $materi]);
            }
        }

        // 4. Update Persetujuan
        $stmt_pl = $conn->prepare("UPDATE persetujuan_lembaga SET 
            frekuensi_kunjungan = ?, 
            duta_gnb = ?, 
            kesediaan_infaq = ?, 
            manfaat = ?
            WHERE lembaga_id = ?");
        
        $stmt_pl->execute([
            $_POST['frekuensi'],
            $_POST['duta_gnb'],
            isset($_POST['kesediaan_infaq']) ? 1 : 0,
            $_POST['manfaat'],
            $id
        ]);

        $conn->commit();
        $success = "Data berhasil diupdate!";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil Data Existing
try {
    // Info Utama
    $stmt = $conn->prepare("SELECT * FROM lembaga WHERE id = ?");
    $stmt->execute([$id]);
    $data_lembaga = $stmt->fetch(PDO::FETCH_ASSOC);

    // Hari Aktif
    $stmt = $conn->prepare("SELECT hari FROM hari_aktif WHERE lembaga_id = ?");
    $stmt->execute([$id]);
    $data_hari = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Materi
    $stmt = $conn->prepare("SELECT materi FROM materi_dipilih WHERE lembaga_id = ?");
    $stmt->execute([$id]);
    $data_materi = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Persetujuan
    $stmt = $conn->prepare("SELECT * FROM persetujuan_lembaga WHERE lembaga_id = ?");
    $stmt->execute([$id]);
    $data_persetujuan = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error fetch data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pendaftar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once 'includes/header.php'; ?>

    <div class="container my-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Data Pendaftar</h5>
                <a href="pendaftar.php" class="btn btn-sm btn-light">Kembali</a>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Data Lembaga -->
                    <h5 class="text-primary mb-3">1. Identitas Lembaga</h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lembaga</label>
                            <input type="text" name="nama_lembaga" class="form-control" value="<?= htmlspecialchars($data_lembaga['nama_lembaga']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data_lembaga['email']) ?>" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kecamatan</label>
                            <select name="kecamatan" class="form-select" required>
                                <option value="">Pilih...</option>
                                <?php
                                $kecamatans = ['Bayat','Cawas','Ceper','Delanggu','Gantiwarno','Jatinom','Jogonalan','Juwiring','Kalikotes','Karanganom','Karangdowo','Karangnongko','Kebonarum','Kemalang','Klaten_Selatan','Klaten_Tengah','Klaten_Utara','Manisrenggo','Ngawen','Pedan','Polanharjo','Prambanan','Trucuk','Tulung','Wedi','Wonosari'];
                                foreach ($kecamatans as $kec) {
                                    // Case-insensitive comparison
                                    $selected = (strcasecmp($data_lembaga['kecamatan'], $kec) == 0) ? 'selected' : '';
                                    echo "<option value='$kec' $selected>".ucwords(str_replace('_', ' ', $kec))."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alamat Lengkap</label>
                            <input type="text" name="alamat" class="form-control" value="<?= htmlspecialchars($data_lembaga['alamat']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jumlah Santri</label>
                            <input type="number" name="jumlah_santri" class="form-control" value="<?= $data_lembaga['jumlah_santri'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Share Location (URL)</label>
                            <input type="text" name="share_loc" class="form-control" value="<?= htmlspecialchars($data_lembaga['share_loc']) ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                         <div class="col-md-6">
                            <label class="form-label d-block">Hari Aktif</label>
                            <?php 
                            $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            foreach($days as $day): 
                                $checked = in_array($day, $data_hari) ? 'checked' : '';
                            ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="hari_aktif[]" value="<?= $day ?>" <?= $checked ?>>
                                    <label class="form-check-label"><?= $day == 'Jumat' ? "Jum'at" : $day ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jam Aktif</label>
                            <input type="text" name="jam_aktif" class="form-control" value="<?= htmlspecialchars($data_lembaga['jam_aktif']) ?>" required>
                        </div>
                    </div>
                    
                    <hr>

                    <!-- Data PJ -->
                    <h5 class="text-primary mb-3">2. Penanggung Jawab</h5>
                    <div class="row mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Nama PJ</label>
                            <input type="text" name="pj" class="form-control" value="<?= htmlspecialchars($data_lembaga['penanggung_jawab']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jabatan</label>
                             <select name="jabatan" class="form-select" required>
                                <option value="TAKMIR MASJID" <?= strcasecmp($data_lembaga['jabatan'], 'TAKMIR MASJID') == 0 ? 'selected' : '' ?>>Takmir Masjid</option>
                                <option value="KOORDINATOR TPA" <?= strcasecmp($data_lembaga['jabatan'], 'KOORDINATOR TPA') == 0 ? 'selected' : '' ?>>Koordinator TPA</option>
                                <option value="GURU TPA" <?= strcasecmp($data_lembaga['jabatan'], 'GURU TPA') == 0 ? 'selected' : '' ?>>Guru TPA</option>
                                <option value="LAINNYA" <?= strcasecmp($data_lembaga['jabatan'], 'LAINNYA') == 0 ? 'selected' : '' ?>>Lainnya</option>
                             </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor WhatsApp</label>
                            <input type="text" name="no_wa" id="noWaInput" class="form-control" value="<?= htmlspecialchars($data_lembaga['no_wa']) ?>" required>
                            <small class="text-muted">*Otomatis ubah 08xx jadi 628xx</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Detail Kegiatan -->
                    <h5 class="text-primary mb-3">3. Detail Kegiatan & Pengajuan</h5>
                    <div class="mb-3">
                        <label class="form-label d-block">Materi yang Dipilih</label>
                         <?php 
                            $materis = ['Berkisah Islami', 'Motivasi & Muhasabah', 'Kajian Buka Bersama'];
                            foreach($materis as $mat): 
                                $isMatChecked = in_array($mat, $data_materi) ? 'checked' : '';
                            ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="materi[]" value="<?= $mat ?>" <?= $isMatChecked ?>>
                                    <label class="form-check-label"><?= $mat ?></label>
                                </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="row mb-3">
                         <div class="col-md-6">
                            <label class="form-label">Frekuensi Kunjungan (Kali)</label>
                            <input type="number" name="frekuensi" class="form-control" value="<?= $data_persetujuan['frekuensi_kunjungan'] ?>" required>
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Minggu Ke (Pengajuan)</label>
                            <select name="manfaat" class="form-select" required>
                                <option value="Pekan 1" <?= $data_persetujuan['manfaat']=='Pekan 1'?'selected':''?>>Pekan 1</option>
                                <option value="Pekan 2" <?= $data_persetujuan['manfaat']=='Pekan 2'?'selected':''?>>Pekan 2</option>
                                <option value="Pekan 3" <?= $data_persetujuan['manfaat']=='Pekan 3'?'selected':''?>>Pekan 3</option>
                                <option value="Pekan 4" <?= $data_persetujuan['manfaat']=='Pekan 4'?'selected':''?>>Pekan 4</option>
                                <option value="Bebas" <?= $data_persetujuan['manfaat']=='Bebas'?'selected':''?>>Bebas / Kapan Saja</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kesediaan Infaq Amplop</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="kesediaan_infaq" value="1" <?= $data_persetujuan['kesediaan_infaq'] == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label">Bersedia</label>
                            </div>
                        </div>
                         <div class="col-md-6">
                            <label class="form-label">Duta GNB</label>
                            <?php
                                // Handle DB values which might be '1'/'bersedia' or '0'/'tidak_bersedia' or NULL
                                $val = $data_persetujuan['duta_gnb'];
                                $isBersedia = ($val === '1' || $val === 1 || strcasecmp((string)$val, 'bersedia') === 0);
                                $isTidakBersedia = ($val === '0' || $val === 0 || strcasecmp((string)$val, 'tidak_bersedia') === 0);
                            ?>
                             <select name="duta_gnb" class="form-select">
                                <option value="">Pilih...</option>
                                <option value="bersedia" <?= $isBersedia ? 'selected' : '' ?>>Bersedia</option>
                                <option value="tidak_bersedia" <?= $isTidakBersedia ? 'selected' : '' ?>>Tidak Bersedia</option>
                             </select>
                        </div>
                    </div>


                    <button type="submit" class="btn btn-primary d-block w-100 mt-4">Simpan Perubahan</button>
                    
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('noWaInput').addEventListener('input', function(e) {
            // Hapus karakter selain angka
            let val = this.value.replace(/[^0-9]/g, '');
            
            // Jika diawali 0, ganti dengan 62
            if (val.startsWith('0')) {
                val = '62' + val.substring(1);
            }
            
            this.value = val;
        });
    </script>
</body>
</html>
