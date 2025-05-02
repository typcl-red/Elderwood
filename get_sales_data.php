<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$product = $_GET['product'] ?? '';
$seller_id = $_SESSION['user_id'];

try {
    // Get sold quantity and earnings from daily_sales
    $salesStmt = $pdo->prepare("
        SELECT 
            SUM(quantity) as sold_quantity,
            SUM(total_amount) as total_earnings
        FROM daily_sales
        WHERE seller_id = ? AND product_name = ?
    ");
    $salesStmt->execute([$seller_id, $product]);
    $salesResult = $salesStmt->fetch(PDO::FETCH_ASSOC);

    // Get current inventory and price from product_inventory and product_catalog
    $inventoryStmt = $pdo->prepare("
        SELECT pi.quantity, pc.price_per_sqft
        FROM product_inventory pi
        JOIN product_catalog pc ON pi.product_name = pc.product_name
        WHERE pi.seller_id = ? AND pi.product_name = ?
    ");
    $inventoryStmt->execute([$seller_id, $product]);
    $inventoryResult = $inventoryStmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total potential quantity (inventory + sold)
    $soldQuantity = (int)$salesResult['sold_quantity'];
    $remainingQuantity = (int)$inventoryResult['quantity'];
    $totalQuantity = $soldQuantity + $remainingQuantity;
    $pricePerUnit = (float)$inventoryResult['price_per_sqft'];

    header('Content-Type: application/json');
    echo json_encode([
        'sold_quantity' => $soldQuantity,
        'remaining_quantity' => $remainingQuantity,
        'total_earnings' => (float)$salesResult['total_earnings'],
        'price_per_unit' => $pricePerUnit,
        'total_quantity' => $totalQuantity
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}