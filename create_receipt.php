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
            INSERT INTO receipt (
                receipt_number, order_id, product_name, size,
                quantity, total_amount, buyer_name, seller_id, receipt_date
            ) VALUES (
                :receipt_number, :order_id, :product_name, :size,
                :quantity, :total_amount, :buyer_name, :seller_id, :receipt_date
            )
        ");
        
        $result = $stmt->execute([
            'receipt_number' => $data['receipt_number'],
            'order_id' => $data['order_id'],
            'product_name' => $data['product_name'],
            'size' => $data['size'],
            'quantity' => $data['quantity'],
            'total_amount' => $data['total_amount'],
            'buyer_name' => $data['buyer_name'],
            'seller_id' => $_SESSION['user_id'],
            'receipt_date' => $data['receipt_date']
        ]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}