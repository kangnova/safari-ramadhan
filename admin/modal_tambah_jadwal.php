<?php
// Query untuk data lembaga yang akan ditampilkan di select
try {
    $query = "SELECT l.*, 
            GROUP_CONCAT(DISTINCT ha.hari) as hari_aktif,
            GROUP_CONCAT(DISTINCT md.materi) as materi_dipilih,
            pl.frekuensi_kunjungan,
            (SELECT COUNT(*) FROM jadwal_safari js WHERE js.lembaga_id = l.id) as jumlah_terjadwal
            FROM lembaga l
            LEFT JOIN hari_aktif ha ON l.id = ha.lembaga_id 
            LEFT JOIN materi_dipilih md ON l.id = md.lembaga_id
            LEFT JOIN persetujuan_lembaga pl ON l.id = pl.lembaga_id
            GROUP BY l.id
            HAVING jumlah_terjadwal < frekuensi_kunjungan OR jumlah_terjadwal IS NULL
            ORDER BY l.nama_lembaga ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $lembaga_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!-- Modal Tambah Jadwal -->
<div class="modal fade" id="tambahModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Jadwal Safari</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Jam</label>
                                <input type="time" name="jam" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Lembaga</label>
                        <select name="lembaga_id" class="form-select" id="selectLembaga" required onchange="updateInfo()">
                            <option value="">Pilih Lembaga</option>
                            <?php foreach($lembaga_list as $lembaga): 
                                $kecamatan = ucwords(str_replace('_', ' ', $lembaga['kecamatan']));
                            ?>
                                <option value="<?= $lembaga['id'] ?>"
                                        data-alamat="<?= htmlspecialchars($lembaga['alamat']) ?>"
                                        data-santri="<?= $lembaga['jumlah_santri'] ?>"
                                        data-pj="<?= htmlspecialchars($lembaga['penanggung_jawab']) ?>"
                                        data-materi="<?= htmlspecialchars($lembaga['materi_dipilih']) ?>">
                                    <?= htmlspecialchars($lembaga['nama_lembaga']) ?> [<?= $kecamatan ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Form Info Lembaga -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Alamat</label>
                                    <input type="text" id="alamatInfo" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Jumlah Santri</label>
                                    <input type="text" id="santriInfo" class="form-control" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">PJ Lembaga</label>
                                    <input type="text" id="pjInfo" class="form-control" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Materi</label>
                                    <input type="text" id="materiInfo" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Pengisi</label>
                        <select name="pengisi" class="form-select" required>
                            <option value="">Pilih Pengisi</option>
                            <?php 
                            $query_pengisi = "SELECT * FROM pengisi WHERE status = 'aktif' ORDER BY nama ASC";
                            $stmt = $conn->prepare($query_pengisi);
                            $stmt->execute();
                            while($pengisi = $stmt->fetch()) {
                                echo "<option value='" . htmlspecialchars($pengisi['nama']) . "'>";
                                echo htmlspecialchars($pengisi['nama']);
                                echo "</option>";
                            }
                            ?>
                        </select>
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

<script>
function updateInfo() {
    const select = document.getElementById('selectLembaga');
    const option = select.options[select.selectedIndex];

    document.getElementById('alamatInfo').value = option.dataset.alamat || '-';
    document.getElementById('santriInfo').value = option.dataset.santri || '-';
    document.getElementById('pjInfo').value = option.dataset.pj || '-';
    document.getElementById('materiInfo').value = option.dataset.materi || '-';
}
</script>