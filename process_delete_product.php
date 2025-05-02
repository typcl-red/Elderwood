<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($data['product_name'])) {
    try {
        $productName = $data['product_name'];
        $sellerId = $_SESSION['user_id'];

        // Begin transaction
        $pdo->beginTransaction();

        // Delete from product_catalog
        $stmt = $pdo->prepare("
            DELETE FROM product_catalog 
            WHERE product_name = ? AND seller_id = ?
        ");
        
        $result = $stmt->execute([$productName, $sellerId]);

        if (!$result) {
            throw new Exception("Failed to delete product from catalog");
        }

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Product removed successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting product: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error removing product: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request data'
    ]);
} 