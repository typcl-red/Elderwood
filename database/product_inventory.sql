CREATE TABLE IF NOT EXISTS `product_inventory` (
    `product_id` INT PRIMARY KEY AUTO_INCREMENT,
    `seller_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `length_feet` INT NOT NULL,
    `width_feet` INT NOT NULL,
    `quantity` INT NOT NULL,
    `total_square_feet` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 