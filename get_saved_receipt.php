<?php
// Turn off error display in the output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'database/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if order_id is provided
if (!isset($data['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = $data['order_id'];

try {
    // Get receipt details - using lowercase 'receipt' instead of 'Receipt'
    $stmt = $pdo->prepare("
        SELECT * FROM receipt
        WHERE order_id = ?
    ");
    $stmt->execute([$orderId]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Receipt not found']);
        exit;
    }
    
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Add success flag to response
    $receipt['success'] = true;
    
    echo json_encode($receipt);
} catch (PDOException $e) {
    // Log the error but don't display it
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>