<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'];

    $stmt = $pdo->prepare("
        SELECT r.*
        FROM Receipt r
        JOIN order_list ol ON r.order_id = ol.order_id
        WHERE r.order_id = :order_id
        AND ol.buyer_id = :buyer_id
    ");
    
    $stmt->execute([
        ':order_id' => $orderId,
        ':buyer_id' => $_SESSION['user_id']
    ]);
    
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receipt) {
        echo json_encode([
            'success' => true,
            'receipt_number' => $receipt['receipt_number'],
            'product_name' => $receipt['product_name'],
            'size' => $receipt['size'],
            'quantity' => $receipt['quantity'],
            'total_amount' => $receipt['total_amount'],
            'buyer_name' => $receipt['buyer_name'],
            'receipt_date' => $receipt['receipt_date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
    }

} catch (Exception $e) {
    error_log("Error in get_buyer_receipt.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 