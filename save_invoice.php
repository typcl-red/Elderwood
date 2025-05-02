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

try {
    $pdo->beginTransaction();

    // Fetch item_id from sup_orders table
    $stmt = $pdo->prepare("
        SELECT item_id FROM sup_orders WHERE supplier_id = ?
    ");
    $stmt->execute([$data['supplier_id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        throw new Exception('Item not found in sup_orders');
    }

    // Insert into invoices table
    $stmt = $pdo->prepare("
        INSERT INTO invoices (
            seller_id, supplier_id, product_name, item_id, price_per_unit, 
            grand_total_volume, total_bill, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $data['supplier_id'],
        $data['product_name'],
        $item['item_id'], // Use fetched item_id
        $data['price_per_unit'],
        $data['grand_total_volume'],
        $data['total_bill']
    ]);

    $invoice_id = $pdo->lastInsertId();

    // Insert items into invoice_items table
    $stmt = $pdo->prepare("
        INSERT INTO invoice_items (
            invoice_id, length_feet, width_feet, 
            height_feet, quantity, total_volume
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $stmt->execute([
            $invoice_id,
            $item['length_feet'],
            $item['width_feet'],
            $item['height_feet'],
            $item['quantity'],
            $item['total_volume']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}