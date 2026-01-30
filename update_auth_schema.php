<?php
require_once 'koneksi.php';

try {
    // 1. Add password column
    $checkPass = $conn->query("SHOW COLUMNS FROM lembaga LIKE 'password'");
    if ($checkPass->rowCount() == 0) {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN password VARCHAR(255) DEFAULT NULL AFTER email");
        echo "Berhasil: Kolom 'password' ditambahkan.<br>";
    } else {
        echo "Info: Kolom 'password' sudah ada.<br>";
    }

    // 2. Add last_login
    $checkLogin = $conn->query("SHOW COLUMNS FROM lembaga LIKE 'last_login'");
    if ($checkLogin->rowCount() == 0) {
        $conn->exec("ALTER TABLE lembaga ADD COLUMN last_login DATETIME DEFAULT NULL AFTER password");
        echo "Berhasil: Kolom 'last_login' ditambahkan.<br>";
    } else {
        echo "Info: Kolom 'last_login' sudah ada.<br>";
    }

    // 3. Set default password for existing users (if null)
    // Default: 'bismillah' -> hashed
    $defaultPass = password_hash('bismillah', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE lembaga SET password = ? WHERE password IS NULL");
    $stmt->execute([$defaultPass]);
    
    if ($stmt->rowCount() > 0) {
        echo "Berhasil: Mengupdate password default untuk " . $stmt->rowCount() . " lembaga.<br>";
        echo "Default password: 'bismillah'<br>";
    } else {
        echo "Info: Tidak ada lembaga yang perlu diupdate (password sudah terisi semua).<br>";
    }

    echo "Selesai.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
