<?php
session_start();
require_once 'database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];
    $laborerId = $_POST['laborer_id'];

    try {
        $pdo->beginTransaction();

        // Update the order status
        $stmt = $pdo->prepare("UPDATE order_list SET status = ?, status_updated_by = ?, status_updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$status, $laborerId, $orderId]);

        // If status is "Ready for Pick Up", add to for_pickup table
        if ($status === 'Ready for Pick Up') {
            // Get order details
            $stmt = $pdo->prepare("
                SELECT 
                    CONCAT(u.firstname, ' ', u.lastname) as buyer_name,
                    ol.product_name,
                    ol.length_feet,
                    ol.width_feet,
                    ol.height_feet
                FROM order_list ol
                JOIN users u ON ol.buyer_id = u.id
                WHERE ol.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $orderDetails = $stmt->fetch();

            // Insert into for_pickup table
            $stmt = $pdo->prepare("
                INSERT INTO for_pickup (buyer_name, product_name, length_feet, width_feet, height_feet, order_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderDetails['buyer_name'],
                $orderDetails['product_name'],
                $orderDetails['length_feet'],
                $orderDetails['width_feet'],
                $orderDetails['height_feet'],
                $orderId
            ]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>