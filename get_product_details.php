<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Debug log
    error_log('Received data: ' . print_r($data, true));
    
    // Validate input data
    if (!isset($data['product_name']) || 
        !isset($data['length_feet']) || 
        !isset($data['width_feet']) || 
        !isset($data['height_feet'])) {
        throw new Exception('Missing required parameters');
    }

    $stmt = $pdo->prepare("
        SELECT pc.*, u.firstname, u.lastname
        FROM product_catalog pc
        JOIN users u ON pc.seller_id = u.id
        WHERE pc.product_name = :product_name
        AND pc.seller_id = :seller_id
        AND pc.length_feet = :length_feet
        AND pc.width_feet = :width_feet
        AND pc.height_feet = :height_feet
        LIMIT 1
    ");

    $params = [
        ':product_name' => $data['product_name'],
        ':seller_id' => $_SESSION['user_id'],
        ':length_feet' => $data['length_feet'],
        ':width_feet' => $data['width_feet'],
        ':height_feet' => $data['height_feet']
    ];

    // Debug log
    error_log('SQL params: ' . print_r($params, true));

    $stmt->execute($params);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        echo json_encode([
            'success' => true,
            'product_name' => $product['product_name'],
            'image_path' => $product['image_path'],
            'price_per_sqft' => $product['price_per_sqft'],
            'seller_name' => $product['firstname'] . ' ' . $product['lastname']
        ]);
    } else {
        throw new Exception('Product not found in catalog');
    }

} catch (Exception $e) {
    error_log('Error in get_product_details.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error loading product details: ' . $e->getMessage()
    ]);
}
?> 