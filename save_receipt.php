<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    // Insert into Receipt table
    $stmt = $pdo->prepare("
        INSERT INTO Receipt (
            receipt_number,
            order_id,
            product_name,
            size,
            quantity,
            total_amount,
            buyer_name,
            seller_id,
            receipt_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['receipt_number'],
        $data['order_id'],
        $data['product_name'],
        $data['size'],
        $data['quantity'],
        $data['total_amount'],
        $data['buyer_name'],
        $_SESSION['user_id'],
        date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>