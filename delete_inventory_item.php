<?php
session_start();
require_once 'database/config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ? AND supplier_id = ?");
        $result = $stmt->execute([$data['id'], $_SESSION['user_id']]);
        
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete item']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>