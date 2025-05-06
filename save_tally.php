<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cus_orders (
                order_id, product_name, length_feet, width_feet, 
                height_feet, quantity, price_per_sqft, total_amount
            ) VALUES (
                :order_id, :product_name, :length_feet, :width_feet,
                :height_feet, :quantity, :price_per_sqft, :total_amount
            )
        ");
        
        $result = $stmt->execute([
            'order_id' => $data['order_id'],
            'product_name' => $data['product_name'],
            'length_feet' => $data['length_feet'],
            'width_feet' => $data['width_feet'],
            'height_feet' => $data['height_feet'],
            'quantity' => $data['quantity'],
            'price_per_sqft' => $data['price_per_sqft'],
            'total_amount' => $data['total_amount']
        ]);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            error_log("Failed to insert into cus_orders: " . print_r($pdo->errorInfo(), true));
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to save order']);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in save_tally.php: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}