<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

$response = ['success' => false, 'message' => ''];

try {
    // Get the JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    $trash_id = $data['trash_id'];
    $seller_id = $_SESSION['user_id'];

    // Delete from trash
    $stmt = $pdo->prepare("
        DELETE FROM product_trash 
        WHERE trash_id = ? AND seller_id = ?
    ");
    $stmt->execute([$trash_id, $seller_id]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Product permanently deleted';
    } else {
        $response['message'] = 'Product not found in trash';
    }

} catch (Exception $e) {
    $response['message'] = 'Error deleting product: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 