<?php
session_start();
require_once 'database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $supplier_id = $_SESSION['user_id'];
        $response = ['success' => false, 'message' => ''];

        // Validate required fields
        $required_fields = ['productName', 'length', 'width', 'height', 'quantity', 'status'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Field {$field} is required");
            }
        }

        // Validate numeric fields
        $numeric_fields = ['length', 'width', 'height', 'quantity'];
        foreach ($numeric_fields as $field) {
            if (!is_numeric($_POST[$field]) || $_POST[$field] < 0) {
                throw new Exception("Field {$field} must be a positive number");
            }
        }

        $description = isset($_POST['description']) ? $_POST['description'] : '';
        
        // Prepare the SQL statement
        if (empty($_POST['itemId'])) {
            $sql = "INSERT INTO inventory (supplier_id, product_name, length, width, height, quantity, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $supplier_id,
                $_POST['productName'],
                $_POST['length'],
                $_POST['width'],
                $_POST['height'],
                $_POST['quantity'],
                $description,
                $_POST['status']
            ];
        } else {
            $sql = "UPDATE inventory 
                    SET product_name = ?, length = ?, width = ?, height = ?, quantity = ?, 
                        description = ?, status = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND supplier_id = ?";
            $params = [
                $_POST['productName'],
                $_POST['length'],
                $_POST['width'],
                $_POST['height'],
                $_POST['quantity'],
                $description,
                $_POST['status'],
                $_POST['itemId'],
                $supplier_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $response['success'] = true;
        $response['message'] = empty($_POST['itemId']) ? 'Item added successfully' : 'Item updated successfully';
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
}
?>