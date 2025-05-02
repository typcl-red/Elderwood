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
    
    if (!$data) {
        throw new Exception('Invalid order data');
    }

    // Start transaction
    $pdo->beginTransaction();

    // First, verify if the product exists and get seller information
    $stmt = $pdo->prepare("
        SELECT pc.seller_id, pc.product_name, u.firstname, u.lastname
        FROM product_catalog pc
        JOIN users u ON pc.seller_id = u.id
        WHERE pc.product_name = ?
    ");
    $stmt->execute([$data['product_name']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Product not found');
    }

    // Check if this product is already in the order list
    $stmt = $pdo->prepare("
        SELECT order_id 
        FROM order_list 
        WHERE buyer_id = ? 
        AND product_name = ?
        AND status = 'Pending'
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $data['product_name']
    ]);

    $existingOrder = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingOrder) {
        // Update the existing order with new specifications
        $stmt = $pdo->prepare("
            UPDATE order_list 
            SET status = 'Ordered',
                length_feet = ?,
                width_feet = ?,
                height_feet = ?,
                quantity = ?
            WHERE order_id = ?
        ");

        $stmt->execute([
            $data['length_feet'],
            $data['width_feet'],
            $data['height_feet'],
            $data['quantity'],
            $existingOrder['order_id']
        ]);

        $orderId = $existingOrder['order_id'];
    } else {
        // Create a new order
        $stmt = $pdo->prepare("
            INSERT INTO order_list (
                buyer_id,
                product_name,
                length_feet,
                width_feet,
                height_feet,
                quantity,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Ordered')
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $data['product_name'],
            $data['length_feet'],
            $data['width_feet'],
            $data['height_feet'],
            $data['quantity']
        ]);

        $orderId = $pdo->lastInsertId();
    }

    // Create notification message
    $message = sprintf(
        "New order received!\nProduct: %s\nSize: %s' x %s' x %s'\nQuantity: %d pieces",
        $data['product_name'],
        $data['length_feet'],
        $data['width_feet'],
        $data['height_feet'],
        $data['quantity']
    );

    // Insert notification for the seller
    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id,
            type,
            message,
            reference_id,
            is_read,
            created_at
        ) VALUES (?, 'order', ?, ?, 0, CURRENT_TIMESTAMP)
    ");

    $stmt->execute([
        $product['seller_id'],
        $message,
        $orderId
    ]);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>