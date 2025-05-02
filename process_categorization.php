<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a Seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $seller_id = $_POST['seller_id'];
    $product_name = $_POST['product_name'];
    $length_feet = $_POST['length_feet'];
    $width_feet = $_POST['width_feet'];
    $height_feet = $_POST['height_feet'];
    $quantity = $_POST['quantity'];
    $presentation_id = $_POST['presentation_id'];

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === 0) {
        $upload_dir = 'uploads/products/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('product_') . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image_path']['tmp_name'], $target_path)) {
            $image_path = $target_path; // This stays as relative path
        }
    }

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert into product_inventory
        $stmt = $pdo->prepare("
            INSERT INTO product_inventory (
                seller_id, product_name, length_feet, width_feet, 
                height_feet, quantity, image_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $seller_id,
            $product_name,
            $length_feet,
            $width_feet,
            $height_feet,
            $quantity,
            $image_path
        ]);

        // Update sup_orders status
        $stmt = $pdo->prepare("
            UPDATE sup_orders 
            SET status = 'Processed'
            WHERE id = ?
        ");
        $stmt->execute([$presentation_id]);

        // Get the item_id and supplier_id from sup_orders
        $stmt = $pdo->prepare("
            SELECT item_id, supplier_id, price_per_unit, grand_total_volume, total_bill 
            FROM sup_orders 
            WHERE id = ?
        ");
        $stmt->execute([$presentation_id]);
        $sup_order = $stmt->fetch(PDO::FETCH_ASSOC);

        // Create invoice with sup_order_id and item_id
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                seller_id, 
                supplier_id, 
                product_name, 
                price_per_unit, 
                grand_total_volume, 
                total_bill,
                sup_order_id,
                item_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $sup_order['supplier_id'],
            $product_name,
            $sup_order['price_per_unit'],
            $sup_order['grand_total_volume'],
            $sup_order['total_bill'],
            $presentation_id,
            $sup_order['item_id']
        ]);
        // Commit transaction
        $pdo->commit();

        // Redirect back with success message
        $_SESSION['success_message'] = "Product saved successfully!";
        header("Location: categorize_product.php?id=" . $presentation_id);
        exit();

    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        
        $_SESSION['error_message'] = "Error saving product: " . $e->getMessage();
        header("Location: categorize_product.php?id=" . $presentation_id);
        exit();
    }
}

// If not POST request, redirect back
header('Location: presentations.php');
exit();
?>