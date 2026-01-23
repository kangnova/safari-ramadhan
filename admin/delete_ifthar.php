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
    if (!isset($_POST['id'])) {
        throw new Exception('ID is required');
    }

    $id = intval($_POST['id']);

    // Hapus data
    $query = "DELETE FROM ifthar WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Data deleted successfully']);
    } else {
        throw new Exception('No record found to delete');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}