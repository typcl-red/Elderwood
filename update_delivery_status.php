<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (isset($_POST['presentation_id'])) {
    try {
        // Update the delivery_schedules table using presentation_id
        $stmt = $pdo->prepare("UPDATE delivery_schedules SET status = 'On The Way' WHERE presentation_id = ?");
        $stmt->execute([$_POST['presentation_id']]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No records updated']);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
}