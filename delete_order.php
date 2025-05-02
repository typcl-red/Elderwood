<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id'])) {
        throw new Exception('Order ID is required');
    }

    $orderId = (int)$data['order_id'];

    // Delete the order
    $stmt = $pdo->prepare("
        DELETE FROM order_list 
        WHERE order_id = ? AND buyer_id = ?
    ");
    
    $result = $stmt->execute([$orderId, $_SESSION['user_id']]);

    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
    } else {
        throw new Exception('Order not found or cannot be deleted');
    }

} catch (PDOException $e) {
    // Log the error details for debugging
    error_log("Database Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred while deleting the order'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 