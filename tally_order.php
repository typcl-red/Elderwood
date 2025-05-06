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
    
    if (isset($data['product_name']) && isset($data['length_feet']) && 
        isset($data['width_feet']) && isset($data['height_feet']) && 
        isset($data['quantity'])) {
        
        try {
            $stmt = $pdo->prepare("
                SELECT price_per_sqft 
                FROM product_catalog 
                WHERE product_name = :product_name 
                AND seller_id = :seller_id
                LIMIT 1
            ");
            
            $stmt->execute([
                'product_name' => $data['product_name'],
                'seller_id' => $_SESSION['user_id']
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $price_per_sqft = floatval($result['price_per_sqft']);
                $quantity = intval($data['quantity']);
                
                $total_price = $price_per_sqft * $quantity;
                
                $response = [
                    'success' => true,
                    'price_per_sqft' => $price_per_sqft,
                    'total_price' => $total_price
                ];
            } else {
                $response = ['error' => 'Product not found'];
            }
            
        } catch (PDOException $e) {
            $response = ['error' => 'Database error'];
        }
        
    } else {
        $response = ['error' => 'Invalid data'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
}