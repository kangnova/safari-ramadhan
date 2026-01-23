<?php
function recordVisit($halaman) {
    global $conn;
    
    try {
        // Cek apakah sudah ada record untuk halaman dan tanggal ini
        $query = "SELECT id FROM statistik_pengunjung 
                 WHERE halaman = :halaman 
                 AND DATE(last_visit) = CURDATE()";
        $stmt = $conn->prepare($query);
        $stmt->execute([':halaman' => $halaman]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Update jumlah kunjungan
            $query = "UPDATE statistik_pengunjung 
                     SET jumlah_kunjungan = jumlah_kunjungan + 1,
                         last_visit = NOW()
                     WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':id' => $result['id']]);
        } else {
            // Insert record baru
            $query = "INSERT INTO statistik_pengunjung 
                     (halaman, jumlah_kunjungan, last_visit)
                     VALUES (:halaman, 1, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->execute([':halaman' => $halaman]);
        }
    } catch (PDOException $e) {
        error_log("Error recording visit: " . $e->getMessage());
    }
}

// Panggil fungsi di setiap halaman yang ingin direkam
recordVisit(basename($_SERVER['PHP_SELF']));
?>