<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

$response = ['success' => false, 'items' => []];

try {
    $seller_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM product_trash 
        WHERE seller_id = ? 
        ORDER BY deleted_at DESC
    ");
    $stmt->execute([$seller_id]);
    
    $response['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = 'Error fetching trash items: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 