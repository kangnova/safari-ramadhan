<?php
require_once 'koneksi.php';
$stmt = $conn->query("DESCRIBE lembaga");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
