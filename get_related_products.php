<?php
session_start();
require_once 'database/config.php'; // Update path if needed

header('Content-Type: application/json');

try {
    // Get the search parameters
    $product_name = $_POST['product_name'] ?? '';
    $length = $_POST['length'] ?? 0;
    $width = $_POST['width'] ?? 0;
    $height = $_POST['height'] ?? 0;

    // Log received parameters for debugging
    error_log("Searching for related products: " . json_encode($_POST));

    // Query to get related products
    $stmt = $pdo->prepare("
        SELECT 
            pi.product_id,
            pi.seller_id,
            pi.product_name,
            pi.length_feet,
            pi.width_feet,
            pi.height_feet,
            pi.quantity,
            pi.image_path,
            pc.price_per_sqft,
            CONCAT(u.firstname, ' ', u.lastname) as seller_name
        FROM product_inventory pi
        JOIN product_catalog pc ON pi.product_name = pc.product_name
        JOIN users u ON pi.seller_id = u.ID
        WHERE pi.quantity > 0
        AND (
            pi.product_name LIKE :product_name
            OR (
                pi.length_feet BETWEEN :length_min AND :length_max
                AND pi.width_feet BETWEEN :width_min AND :width_max
                AND pi.height_feet BETWEEN :height_min AND :height_max
            )
        )
        ORDER BY pi.product_name ASC
        LIMIT 10
    ");

    // Set search parameters with some flexibility
    $stmt->execute([
        ':product_name' => '%' . $product_name . '%',
        ':length_min' => $length * 0.8,
        ':length_max' => $length * 1.2,
        ':width_min' => $width * 0.8,
        ':width_max' => $width * 1.2,
        ':height_min' => $height * 0.8,
        ':height_max' => $height * 1.2
    ]);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the products data
    $formattedProducts = array_map(function($product) {
        // Add available sizes array for compatibility with the frontend
        $product['available_sizes'] = [[
            'length_feet' => $product['length_feet'],
            'width_feet' => $product['width_feet'],
            'height_feet' => $product['height_feet'],
            'quantity' => $product['quantity']
        ]];
        
        // Format the price
        $product['price_per_sqft'] = number_format((float)$product['price_per_sqft'], 2, '.', '');
        
        return $product;
    }, $products);

    echo json_encode([
        'success' => true,
        'data' => $formattedProducts
    ]);

} catch (Exception $e) {
    error_log("Error in get_related_products.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading related products: ' . $e->getMessage()
    ]);
}
?> 