<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO cus_orders (
            order_id,
            product_name,
            length_feet,
            width_feet,
            height_feet,
            quantity,
            price_per_sqft,
            total_amount,
            created_at
        ) VALUES (
            :order_id,
            :product_name,
            :length_feet,
            :width_feet,
            :height_feet,
            :quantity,
            :price_per_sqft,
            :total_amount,
            NOW()
        )
    ");

    $stmt->execute([
        ':order_id' => $data['order_id'],
        ':product_name' => $data['product_name'],
        ':length_feet' => $data['length_feet'],
        ':width_feet' => $data['width_feet'],
        ':height_feet' => $data['height_feet'],
        ':quantity' => $data['quantity'],
        ':price_per_sqft' => $data['price_per_sqft'],
        ':total_amount' => $data['total_amount']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Error in save_tally_order.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error saving tally order: ' . $e->getMessage()
    ]);
}
?> 