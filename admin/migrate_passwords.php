<?php
require_once '../koneksi.php';

try {
    // 1. Ambil semua pengisi yang passwords-nya belum di-hash (asumsi hash bcrypt panjangnya 60 char)
    // Kita ambil semua dulu untuk safety, lalu cek apakah perlu dihash
    $query = "SELECT id, password FROM pengisi";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    
    foreach ($users as $user) {
        $id = $user['id'];
        $pass = $user['password'];
        
        // Cek apakah password sudah di-hash (Bcrypt biasanya diawali $2y$)
        // Atau cek panjang string. MD5 32 chars, Bcrypt 60 chars.
        // Jika panjang < 50, kemungkinan besar belum dihash (atau hash lama md5)
        // Di sini kita asumsikan plain text jika tidak diawali $2y$
        
        if (substr($pass, 0, 4) !== '$2y$') {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            
            $update = "UPDATE pengisi SET password = :hash WHERE id = :id";
            $upStmt = $conn->prepare($update);
            $upStmt->execute([':hash' => $hashed, ':id' => $id]);
            $count++;
        }
    }
    
    echo "Berhasil migrasi $count password ke format Hash (Bcrypt).";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
