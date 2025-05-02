<?php
session_start();
require_once 'database/config.php';

header('Content-Type: application/json');

// Disable error reporting for output
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Check if user is logged in and is a supplier
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
        throw new Exception('Unauthorized access');
    }

    // Validate required data
    if (!isset($data['seller_id']) || !isset($data['item_id'])) {
        throw new Exception('Missing seller_id or item_id');
    }

    // Verify seller exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'Seller'");
    $stmt->execute([$data['seller_id']]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid seller ID');
    }

    // Insert presentation into sup_orders
    $stmt = $pdo->prepare("
        INSERT INTO sup_orders (
            seller_id, 
            supplier_id, 
            item_id, 
            status, 
            created_at, 
            is_read
        ) VALUES (
            ?, 
            ?, 
            ?, 
            'Pending', 
            CURRENT_TIMESTAMP, 
            0
        )
    ");

    $success = $stmt->execute([
        $data['seller_id'],
        $_SESSION['user_id'],
        $data['item_id']
    ]);

    if (!$success) {
        throw new Exception('Failed to save presentation');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item presented successfully'
    ]);

} catch (Exception $e) {
    error_log('Presentation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}