<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            co.*,
            CONCAT(b.firstname, ' ', b.lastname) as buyer_name,
            CONCAT(s.firstname, ' ', s.lastname) as seller_name
        FROM cus_orders co
        JOIN order_list ol ON co.order_id = ol.order_id
        JOIN users b ON ol.buyer_id = b.id
        JOIN users s ON s.id = ?
        WHERE co.order_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id'], $_GET['order_id']]);
    $tallyDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tallyDetails) {
        echo json_encode([
            'success' => true,
            'buyer_name' => $tallyDetails['buyer_name'],
            'seller_name' => $tallyDetails['seller_name'],
            'order_id' => $tallyDetails['order_id'],
            'product_name' => $tallyDetails['product_name'],
            'length_feet' => floatval($tallyDetails['length_feet']),
            'width_feet' => floatval($tallyDetails['width_feet']),
            'height_feet' => floatval($tallyDetails['height_feet']),
            'quantity' => intval($tallyDetails['quantity']),
            'price_per_sqft' => floatval($tallyDetails['price_per_sqft']),
            'total_amount' => floatval($tallyDetails['total_amount']),
            'created_at' => $tallyDetails['created_at']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tally details not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>