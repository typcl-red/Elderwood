<?php
require_once 'includes/session.php';
require_once 'database/config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Extract the base product name (remove numbers and special characters)
    $baseProductName = preg_replace('/[0-9\W]+/', ' ', $data['product_name']);
    $baseProductName = trim($baseProductName);
    
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            CONCAT(u.firstname, ' ', u.lastname) as seller_name,
            (SELECT image_url FROM product_images WHERE product_id = p.product_id LIMIT 1) as image_url,
            CASE 
                WHEN LOWER(p.product_name) = LOWER(:exact_name) THEN 0
                WHEN LOWER(p.product_name) LIKE LOWER(:base_name) THEN 1
                WHEN (
                    ABS(p.length_feet - :length) <= 1 AND 
                    ABS(p.width_feet - :width) <= 1 AND 
                    ABS(p.height_feet - :height) <= 1
                ) THEN 2
                ELSE 3
            END as match_priority,
            (
                ABS(p.length_feet - :length) + 
                ABS(p.width_feet - :width) + 
                ABS(p.height_feet - :height)
            ) as size_difference
        FROM product_catalog p
        JOIN users u ON p.seller_id = u.user_id
        WHERE (
            -- Match exact product name
            LOWER(p.product_name) = LOWER(:exact_name)
            -- Match similar product names
            OR LOWER(p.product_name) LIKE LOWER(:base_name)
            -- Match similar dimensions (within 1 foot difference)
            OR (
                ABS(p.length_feet - :length) <= 1 AND 
                ABS(p.width_feet - :width) <= 1 AND 
                ABS(p.height_feet - :height) <= 1
            )
        )
        AND p.seller_id != :current_user_id
        AND p.status = 'Active'
        AND p.quantity > 0
        ORDER BY 
            match_priority,
            size_difference
        LIMIT 12
    ");

    $params = [
        ':exact_name' => $data['product_name'],
        ':base_name' => '%' . $baseProductName . '%',
        ':length' => $data['length_feet'],
        ':width' => $data['width_feet'],
        ':height' => $data['height_feet'],
        ':current_user_id' => $_SESSION['user_id']
    ];

    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add a match reason to each product
    foreach ($products as &$product) {
        if (strtolower($product['product_name']) === strtolower($data['product_name'])) {
            $product['match_reason'] = 'Exact match';
        } elseif (stripos($product['product_name'], $baseProductName) !== false) {
            $product['match_reason'] = 'Similar product';
        } else {
            $product['match_reason'] = 'Similar dimensions';
        }
    }
    
    echo json_encode($products);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while fetching similar products']);
}
?> 