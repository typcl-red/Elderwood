<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM payments p
        JOIN receipt r ON p.receipt_id = r.id
        WHERE r.order_id = ?
    ");
    
    $stmt->execute([$_GET['order_id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        echo json_encode([
            'success' => true,
            'payment' => $payment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Payment not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>