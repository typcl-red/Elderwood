<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.firstname,
            u.lastname,
            ol.status_updated_at
        FROM order_list ol
        LEFT JOIN users u ON ol.status_updated_by = u.id  /* Changed from u.user_id to u.id */
        WHERE ol.order_id = ?
    ");
    
    $stmt->execute([$_GET['order_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {  // Simplified the check since we're using LEFT JOIN
        echo json_encode([
            'success' => true,
            'laborer_name' => $result['firstname'] . ' ' . $result['lastname'],
            'update_time' => date('M d, Y h:i A', strtotime($result['status_updated_at']))
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Laborer information not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}