<?php
require_once 'database/config.php';

try {
    // Make sure we're using the elderwood database
    $pdo->exec("USE elderwood");
    
    // Create product_inventory table with image field
    $sql = "CREATE TABLE IF NOT EXISTS `product_inventory` (
        `product_id` INT PRIMARY KEY AUTO_INCREMENT,
        `seller_id` INT NOT NULL,
        `product_name` VARCHAR(255) NOT NULL,
        `length_feet` INT NOT NULL,
        `width_feet` INT NOT NULL,
        `height_feet` INT NOT NULL,
        `quantity` INT NOT NULL,
        `total_square_feet` INT NOT NULL,
        `image_path` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(ID) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    
    // Add height_feet column if it doesn't exist
    $sql = "ALTER TABLE `product_inventory` 
            ADD COLUMN IF NOT EXISTS `height_feet` INT NOT NULL DEFAULT 0 
            AFTER `width_feet`";
    $pdo->exec($sql);

    // Add image_path column if it doesn't exist
    $sql = "ALTER TABLE `product_inventory` 
            ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(255) DEFAULT NULL 
            AFTER `total_square_feet`";
    $pdo->exec($sql);

    // Create product_catalog table
    $sql = "CREATE TABLE IF NOT EXISTS product_catalog (
        catalog_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT,
        seller_id INT,
        product_name VARCHAR(255),
        price_per_sqft DECIMAL(10,2),
        length_feet INT,
        width_feet INT,
        quantity INT,
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id)
    )";

    $pdo->exec($sql);

    // Create product_trash table
    $sql = "CREATE TABLE IF NOT EXISTS product_trash (
        trash_id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT,
        product_name VARCHAR(255),
        length_feet INT,
        width_feet INT,
        quantity INT,
        price_per_sqft DECIMAL(10,2),
        image_path VARCHAR(255),
        deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES users(id)
    )";

    $pdo->exec($sql);

    // Create product_inquiries table
    $sql = "CREATE TABLE IF NOT EXISTS product_inquiries (
        inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_id INT,
        product_name VARCHAR(255),
        length_feet INT,
        width_feet INT,
        quantity INT,
        status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (buyer_id) REFERENCES users(ID)
    )";

    $pdo->exec($sql);

    // Create order_list table
    $sql = "CREATE TABLE IF NOT EXISTS order_list (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_id INT,
        seller_id INT,
        product_name VARCHAR(255),
        length_feet INT,
        width_feet INT,
        height_feet INT,
        quantity INT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (buyer_id) REFERENCES users(ID),
        FOREIGN KEY (seller_id) REFERENCES users(ID)
    )";

    $pdo->exec($sql);

    // Create order_details table
    $sql = "CREATE TABLE IF NOT EXISTS order_details (
        detail_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        payment_method VARCHAR(50),
        payment_status VARCHAR(20) DEFAULT 'pending',
        delivery_method VARCHAR(50),
        delivery_address TEXT,
        special_instructions TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES order_list(order_id) ON DELETE CASCADE
    )";

    $pdo->exec($sql);

    echo "<div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #8B4513;'>Database Setup</h2>";
    echo "<p style='color: #4CAF50;'>Product inventory table updated successfully!</p>";
    echo "<p>Redirecting to inventory page in 3 seconds...</p>";
    echo "</div>";
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/uploads/products';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    // Redirect to inventory page after 3 seconds
    header("refresh:3;url=inventory.php");
    
} catch(PDOException $e) {
    echo "<div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #8B4513;'>Database Setup Error</h2>";
    echo "<p style='color: #ff0000;'>Error updating table: " . $e->getMessage() . "</p>";
    echo "</div>";
}