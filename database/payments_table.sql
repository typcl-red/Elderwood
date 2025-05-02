CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    gcash_number VARCHAR(20),
    gcash_name VARCHAR(100),
    screenshot_path VARCHAR(255),
    delivery_address TEXT,
    contact_number VARCHAR(20),
    delivery_notes TEXT,
    pickup_date DATE,
    pickup_time TIME,
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_date DATETIME NOT NULL,
    FOREIGN KEY (receipt_id) REFERENCES receipt(id) ON DELETE CASCADE
);