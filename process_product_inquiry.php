<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Validate input
    $required_fields = ['product', 'length', 'width', 'height', 'quantity'];
    $input_data = json_decode(file_get_contents('php://input'), true);

    foreach ($required_fields as $field) {
        if (!isset($input_data[$field]) || empty($input_data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get buyer details
    $user = new User($pdo);
    $buyer = $user->getUserById($_SESSION['user_id']);

    // Insert order into database
    $stmt = $pdo->prepare("
        INSERT INTO order_list (
            buyer_id,
            product_name,
            length_feet,
            width_feet,
            height_feet,
            quantity,
            status,
            created_at
        ) VALUES (
            :buyer_id,
            :product_name,
            :length_feet,
            :width_feet,
            :height_feet,
            :quantity,
            'pending',
            CURRENT_TIMESTAMP
        )
    ");

    $stmt->execute([
        'buyer_id' => $_SESSION['user_id'],
        'product_name' => $input_data['product'],
        'length_feet' => $input_data['length'],
        'width_feet' => $input_data['width'],
        'height_feet' => $input_data['height'],
        'quantity' => $input_data['quantity']
    ]);

    $response['success'] = true;
    $response['message'] = 'Order submitted successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response); 