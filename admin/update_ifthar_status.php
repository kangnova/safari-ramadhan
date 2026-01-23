<?php
session_start();
if (!isset($_SESSION['authenticated'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Validasi input
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $id = intval($_POST['id']);
    $status = $_POST['status'];

    // Validasi status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        throw new Exception('Invalid status value');
    }

    // Update status
    $query = "UPDATE ifthar SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':id' => $id,
        ':status' => $status
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        throw new Exception('No record found to update');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}