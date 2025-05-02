<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['receipt_id'])) {
    echo json_encode(['success' => false, 'message' => 'Receipt ID is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
            CONCAT(u.firstname, ' ', u.lastname) as seller_name
        FROM receipt r
        JOIN users u ON r.seller_id = u.id
        WHERE r.id = ?
    ");
    
    $stmt->execute([$_GET['receipt_id']]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receipt) {
        echo json_encode([
            'success' => true,
            'receipt' => $receipt
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Receipt not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>