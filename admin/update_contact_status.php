<?php
session_start();
require_once '../koneksi.php';

header('Content-Type: application/json');

// Cek login admin
if (!isset($_SESSION['authenticated'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

    if ($id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE lembaga SET is_contacted = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);
            
            $rowCount = $stmt->rowCount();
            echo json_encode(['success' => true, 'rows_affected' => $rowCount, 'id' => $id, 'new_status' => $status]);
        } catch (PDOException $e) {
            error_log("Db Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => "Database Error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
}
?>
