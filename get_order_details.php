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
        SELECT ol.*, pc.price_per_sqft
        FROM order_list ol
        LEFT JOIN product_catalog pc ON ol.product_name = pc.product_name 
            AND ol.length_feet = pc.length_feet 
            AND ol.width_feet = pc.width_feet 
            AND ol.height_feet = pc.height_feet
        WHERE ol.order_id = :order_id 
        AND ol.buyer_id = :buyer_id
    ");
    
    $stmt->execute([
        ':order_id' => $orderId,
        ':buyer_id' => $_SESSION['user_id']
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        echo json_encode([
            'success' => true,
            'order' => $order
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }

} catch (Exception $e) {
    error_log("Error in get_order_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 