<?php
require_once 'includes/session.php';
require_once 'database/config.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productName = $_POST['productName'] ?? '';
        $productId = $_POST['productId'] ?? null;
        $sellerId = $_SESSION['user_id'];
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Check if product already exists in catalog
        $checkStmt = $pdo->prepare("SELECT catalog_id FROM product_catalog WHERE product_name = ? AND seller_id = ?");
        $checkStmt->execute([$productName, $sellerId]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Product already exists in your catalog']);
            exit;
        }

        // Get product details from inventory
        $inventoryStmt = $pdo->prepare("
            SELECT length_feet, width_feet, height_feet, quantity, image_path 
            FROM product_inventory 
            WHERE product_name = ? AND seller_id = ?
        ");
        $inventoryStmt->execute([$productName, $sellerId]);
        $productDetails = $inventoryStmt->fetch(PDO::FETCH_ASSOC);

        // Insert into product_catalog
        $catalogStmt = $pdo->prepare("
            INSERT INTO product_catalog (
                product_id, 
                seller_id, 
                product_name, 
                length_feet, 
                width_feet, 
                height_feet,
                quantity, 
                image_path, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $catalogStmt->execute([
            $productId,
            $sellerId,
            $productName,
            $productDetails['length_feet'],
            $productDetails['width_feet'],
            $productDetails['height_feet'],
            $productDetails['quantity'],
            $productDetails['image_path']
        ]);
        
        if (!$result) {
            throw new Exception("Failed to insert into product_catalog");
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error adding product: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
} 