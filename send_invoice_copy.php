<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a Seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$invoice_id = $data['invoice_id'];

try {
    // Fetch invoice details using item_id
    $stmt = $pdo->prepare("
        SELECT item_id, supplier_id FROM invoices WHERE invoice_id = ? AND seller_id = ?
    ");
    $stmt->execute([$invoice_id, $_SESSION['user_id']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        throw new Exception('Invoice not found');
    }

    // Update the supplier_presentations table using item_id
    $stmt = $pdo->prepare("
        UPDATE supplier_presentations 
        SET invoice_sent = 1 
        WHERE item_id = ? AND supplier_id = ?
    ");
    $stmt->execute([$invoice['item_id'], $invoice['supplier_id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('Error sending invoice: ' . $e->getMessage()); // Log error message
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error sending invoice: ' . $e->getMessage()]);
}