<?php
session_start();
require_once '../koneksi.php';

// Password protection check
if (!isset($_SESSION['authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // Default password: bismillah
    // Hash: $2y$10$R5TuuXV/M5tUHWR3qy5mCeboa.QSb7xO.d/NTS1I1SH25sI1Xvk9G
    $default_hash = '$2y$10$R5TuuXV/M5tUHWR3qy5mCeboa.QSb7xO.d/NTS1I1SH25sI1Xvk9G';

    try {
        $stmt = $conn->prepare("UPDATE lembaga SET password = ? WHERE id = ?");
        $stmt->execute([$default_hash, $id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>
