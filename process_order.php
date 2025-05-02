 <?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();

    // Insert into order_list table
    $stmt = $pdo->prepare("
        INSERT INTO order_list (
            buyer_id, seller_id, product_id, product_name,
            length_feet, width_feet, height_feet,
            quantity, price_per_sqft, total_price, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending'
        )
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $data['seller_id'],
        $data['product_id'],
        $data['product_name'],
        $data['length_feet'],
        $data['width_feet'],
        $data['height_feet'],
        $data['quantity'],
        $data['price_per_sqft'],
        $data['total_price']
    ]);

    $order_id = $pdo->lastInsertId();

    // Create notification for seller
    $stmt = $pdo->prepare("
        INSERT INTO notifications (
            user_id, type, message, reference_id, is_read
        ) VALUES (
            ?, 'new_order', ?, ?, 0
        )
    ");

    $notification_message = "New order received for {$data['product_name']} - {$data['quantity']} piece(s)";
    $stmt->execute([$data['seller_id'], $notification_message, $order_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>