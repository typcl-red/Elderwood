<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, r.receipt_number
        FROM payments p
        LEFT JOIN receipt r ON p.receipt_id = r.id
        WHERE p.order_id = :order_id
    ");
    
    $stmt->execute(['order_id' => $orderId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($payment) {
        echo json_encode(['success' => true, 'payment' => $payment]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>