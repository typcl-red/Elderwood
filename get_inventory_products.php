<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

try {
    $seller_id = $_SESSION['user_id'];
    
    // Get products from inventory that are NOT in the catalog
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            i.product_name,
            GROUP_CONCAT(
                DISTINCT CONCAT(
                    i.length_feet, 'x', 
                    i.width_feet, 'x',
                    i.height_feet
                )
                ORDER BY i.length_feet, i.width_feet, i.height_feet
                SEPARATOR ', '
            ) as dimensions,
            SUM(i.quantity) as total_quantity,
            MAX(i.image_path) as image_path
        FROM product_inventory i
        LEFT JOIN product_catalog c ON 
            i.product_name = c.product_name AND 
            i.seller_id = c.seller_id
        WHERE i.seller_id = ? 
        AND c.catalog_id IS NULL
        GROUP BY i.product_name
        ORDER BY i.product_name
    ");
    
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($products);
    
} catch (Exception $e) {
    error_log("Error getting inventory products: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load products']);
} 