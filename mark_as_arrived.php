<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if presentation_id is provided
if (!isset($_POST['presentation_id'])) {
    echo json_encode(['success' => false, 'message' => 'Presentation ID is required']);
    exit();
}

try {
    $presentationId = $_POST['presentation_id'];
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Update the status in sup_orders table
    $stmt = $pdo->prepare("UPDATE sup_orders SET status = 'Arrived' WHERE id = ? AND status = 'On The Way'");
    $stmt->execute([$presentationId]);
    
    // Update the status in delivery_schedules table - fixed to use presentation_id
    $stmt = $pdo->prepare("UPDATE delivery_schedules SET status = 'Arrived' WHERE presentation_id = ?");
    $stmt->execute([$presentationId]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Delivery marked as arrived successfully']);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>