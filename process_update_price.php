<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

$response = ['success' => false, 'message' => ''];

try {
    // Get the POST data
    $catalog_id = $_POST['catalog_id'] ?? null;
    $product_name = $_POST['product_name'];
    $price_per_sqft = $_POST['price_per_sqft'];
    $seller_id = $_SESSION['user_id'];

    // If catalog_id exists, update the price
    if ($catalog_id) {
        $stmt = $pdo->prepare("
            UPDATE product_catalog 
            SET price_per_sqft = ?
            WHERE catalog_id = ? AND seller_id = ?
        ");
        $stmt->execute([$price_per_sqft, $catalog_id, $seller_id]);
    } else {
        // Check if a price already exists for this product
        $stmt = $pdo->prepare("
            SELECT catalog_id FROM product_catalog 
            WHERE product_name = ? AND seller_id = ?
        ");
        $stmt->execute([$product_name, $seller_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update existing price
            $stmt = $pdo->prepare("
                UPDATE product_catalog 
                SET price_per_sqft = ?
                WHERE product_name = ? AND seller_id = ?
            ");
            $stmt->execute([$price_per_sqft, $product_name, $seller_id]);
        } else {
            // Insert new product into catalog
            $stmt = $pdo->prepare("
                INSERT INTO product_catalog (
                    seller_id, product_name, price_per_sqft
                ) VALUES (?, ?, ?)
            ");
            $stmt->execute([$seller_id, $product_name, $price_per_sqft]);
        }
    }

    $response['success'] = true;
    $response['message'] = 'Price updated successfully';

} catch (PDOException $e) {
    $response['message'] = 'Error updating price: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 