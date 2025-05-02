<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$status = $data['status'] ?? null;

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE order_list SET status = ? WHERE order_id = ? AND buyer_id = ?");
    $result = $stmt->execute([$status, $orderId, $_SESSION['user_id']]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}