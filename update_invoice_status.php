<?php
session_start();
require_once 'database/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['invoice_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$invoice_id = $_POST['invoice_id'];

try {
    // Update the invoice status to indicate it's been sent to supplier
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET sent_to_supplier = 1 
        WHERE invoice_id = ?
    ");
    $stmt->execute([$invoice_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}