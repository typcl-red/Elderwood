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
    
    if (isset($data['product_name']) && isset($data['required_quantity'])) {
        try {
            $stmt = $pdo->prepare("
                SELECT SUM(quantity) as total_quantity 
                FROM product_inventory 
                WHERE product_name = :product_name 
                AND seller_id = :seller_id
            ");
            
            $stmt->execute([
                'product_name' => $data['product_name'],
                'seller_id' => $_SESSION['user_id']
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $available_quantity = (int)$result['total_quantity'];
            $required_quantity = (int)$data['required_quantity'];
            
            $response = [
                'success' => true,
                'available' => $available_quantity >= $required_quantity,
                'available_quantity' => $available_quantity,
                'required_quantity' => $required_quantity
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database error']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid data']);
    }
}