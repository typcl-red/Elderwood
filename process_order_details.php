<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Get the order data from the POST request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['order_id'])) {
        throw new Exception('Invalid order data');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert order details
    $stmt = $pdo->prepare("
        INSERT INTO order_details (
            order_id,
            payment_method,
            payment_status,
            delivery_method,
            delivery_address,
            special_instructions
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['order_id'],
        $data['payment_method'],
        'pending',
        $data['delivery_method'],
        $data['delivery_address'],
        $data['special_instructions'] ?? null
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order details saved successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}