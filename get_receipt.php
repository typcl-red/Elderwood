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
    $stmt = $pdo->prepare("SELECT * FROM Receipt WHERE order_id = ?");
    $stmt->execute([$_GET['order_id']]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receipt) {
        echo json_encode([
            'success' => true,
            'receipt' => $receipt
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Receipt not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>