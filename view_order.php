<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: order_list.php');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT ol.*, pc.price_per_sqft
        FROM order_list ol
        LEFT JOIN product_catalog pc ON ol.product_name = pc.product_name 
            AND ol.length_feet = pc.length_feet 
            AND ol.width_feet = pc.width_feet 
            AND ol.height_feet = pc.height_feet
        WHERE ol.order_id = :order_id 
        AND ol.buyer_id = :buyer_id
    ");
    
    $stmt->execute([
        ':order_id' => $_GET['id'],
        ':buyer_id' => $_SESSION['user_id']
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header('Location: order_list.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: order_list.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - ElderWood</title>
    <!-- Add your CSS links here -->
</head>
<body>
    <div class="container">
        <h2>Order Details</h2>
        <div class="order-details">
            <p><strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
            <p><strong>Size:</strong> 
                L: <?php echo htmlspecialchars($order['length_feet']); ?>' x 
                W: <?php echo htmlspecialchars($order['width_feet']); ?>' x 
                H: <?php echo htmlspecialchars($order['height_feet']); ?>'
            </p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?> pieces</p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
            <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></p>
            <?php if (isset($order['price_per_sqft'])): ?>
                <p><strong>Price per sq.ft:</strong> ₱<?php echo number_format($order['price_per_sqft'], 2); ?></p>
            <?php endif; ?>
        </div>
        <div class="actions">
            <a href="order_list.php" class="btn btn-primary">Back to Orders</a>
        </div>
    </div>
</body>
</html> 