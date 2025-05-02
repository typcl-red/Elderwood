<?php
session_start();
require_once 'database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            INSERT INTO product_inventory (
                seller_id, product_name, length_feet, width_feet, height_feet, quantity, total_square_feet, image_path, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        // Calculate total square feet from sizes_json
        $sizes = json_decode($_POST['sizes_json'], true);

        foreach ($sizes as $size) {
            $totalSquareFeet = ($size['length'] * $size['width'] * $size['height']) * $size['quantity'];

            // Execute the statement for each size
            $stmt->execute([
                $_POST['seller_id'],
                $_POST['product_name'],
                $size['length'],
                $size['width'],
                $size['height'],
                $size['quantity'],
                $totalSquareFeet,
                $_FILES['image_path']['name']
            ]);
        }

        // Move uploaded file to the desired directory
        move_uploaded_file($_FILES['image_path']['tmp_name'], 'uploads/' . $_FILES['image_path']['name']);

        $pdo->commit();
        $_SESSION['success_message'] = 'Product added to inventory successfully!';
        header('Location: categorize_product.php?id=' . $_POST['presentation_id'] . '&item_id=' . $_POST['item_id']);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error adding products to inventory: ' . $e->getMessage();
        header('Location: categorize_product.php?id=' . $_POST['presentation_id'] . '&item_id=' . $_POST['item_id']);
        exit();
    }
}