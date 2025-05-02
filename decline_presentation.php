<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a Seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE sup_orders SET status = 'Declined' WHERE id = ? AND seller_id = ?");
    
    try {
        $result = $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
        echo json_encode(['success' => $result]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}