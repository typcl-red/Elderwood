<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user details
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userDetails = $stmt->fetch();

// Check if receipt ID is provided
if (!isset($_GET['receipt_id'])) {
    header("Location: order_list.php");
    exit();
}

$receiptId = $_GET['receipt_id'];

// Get receipt details with seller's GCash information
$stmt = $pdo->prepare("
    SELECT r.*, o.product_name, o.length_feet, o.width_feet, o.height_feet, o.quantity, 
           CONCAT(u.firstname, ' ', u.lastname) AS buyer_name,
           CONCAT(s.firstname, ' ', s.lastname) AS seller_name,
           g.gcash_name, g.gcash_number, g.gcash_qr
    FROM receipt r
    JOIN order_list o ON r.order_id = o.order_id
    JOIN users u ON o.buyer_id = u.id
    JOIN users s ON r.seller_id = s.id
    LEFT JOIN gcash g ON r.seller_id = g.seller_id
    WHERE r.id = ? AND o.buyer_id = ?
");
$stmt->execute([$receiptId, $userId]);
$receipt = $stmt->fetch();

if (!$receipt) {
    header("Location: order_list.php");
    exit();
}

// Process payment form submission
$paymentMessage = '';
$paymentSuccess = false;
$errorMessage = '';

// Add this near the top of the file, after session_start()
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Add this in the POST handling section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
        $errorMessage = "Payment method not selected";
    } else {
        $paymentMethod = $_POST['payment_method'];
        $paymentStatus = 'pending';
        
        try {
            $pdo->beginTransaction();
            
            // Validate pickup information first
            if ($paymentMethod === 'pickup') {
                if (empty($_POST['pickup_date']) || empty($_POST['pickup_time']) || empty($_POST['pickup_contact'])) {
                    throw new Exception("All pickup information is required");
                }
                
                // Validate date
                $pickupDate = new DateTime($_POST['pickup_date']);
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                
                if ($pickupDate < $today) {
                    throw new Exception("Pickup date cannot be in the past");
                }
            }
            
            // Generate reference number
            $referenceNumber = strtoupper(substr($paymentMethod, 0, 2)) . time() . rand(1000, 9999);
            
            // In the POST handling section, modify the SQL query construction
            $sql = "INSERT INTO payments (
                receipt_id, 
                payment_method, 
                reference_number, 
                payment_status, 
                payment_date, 
                gcash_number, 
                gcash_name, 
                screenshot_path, 
                delivery_address, 
                delivery_notes, 
                pickup_date, 
                pickup_time, 
                contact_number,
                product_notes
            ) VALUES (
                :receipt_id, 
                :payment_method, 
                :reference_number, 
                :payment_status, 
                NOW(),
                :gcash_number, 
                :gcash_name, 
                :screenshot_path, 
                :delivery_address, 
                :delivery_notes, 
                :pickup_date, 
                :pickup_time, 
                :contact_number,
                :product_notes
            )";
            
            // Initialize all parameters with NULL
            $params = [
                ':receipt_id' => $receiptId,
                ':payment_method' => $paymentMethod,
                ':reference_number' => $referenceNumber,
                ':payment_status' => $paymentStatus,
                ':gcash_number' => NULL,
                ':gcash_name' => NULL,
                ':screenshot_path' => NULL,
                ':delivery_address' => NULL,
                ':delivery_notes' => NULL,
                ':pickup_date' => NULL,
                ':pickup_time' => NULL,
                ':contact_number' => NULL,
                ':product_notes' => NULL  // Add this line
            ];
            
            // Update specific parameters based on payment method
            switch($paymentMethod) {
                case 'gcash':
                    $params[':gcash_number'] = $_POST['gcash_number'] ?? null;
                    $params[':gcash_name'] = $_POST['gcash_name'] ?? null;
                    $params[':contact_number'] = $_POST['gcash_number'] ?? null;
                    $params[':product_notes'] = $_POST['product_notes'] ?? null;  // Add this line
                    
                    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/payments/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $fileName = time() . '_' . $_FILES['payment_screenshot']['name'];
                        $filePath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $filePath)) {
                            $params[':screenshot_path'] = $filePath;
                        }
                    }
                    break;
            
                case 'cod':
                    $params[':delivery_address'] = $_POST['delivery_address'] ?? null;
                    $params[':delivery_notes'] = $_POST['delivery_notes'] ?? '';
                    $params[':contact_number'] = $_POST['contact_number'] ?? null;
                    $params[':product_notes'] = $_POST['product_notes'] ?? null;  // Add this line
                    break;
            
                case 'pickup':
                    // Validate pickup date and time
                    $pickupDate = $_POST['pickup_date'] ?? null;
                    $pickupTime = $_POST['pickup_time'] ?? null;
                    $contactNumber = $_POST['pickup_contact'] ?? null;

                    if (!$pickupDate || !$pickupTime || !$contactNumber) {
                        throw new Exception("All pickup information is required");
                    }

                    $params[':pickup_date'] = $pickupDate;
                    $params[':pickup_time'] = $pickupTime;
                    $params[':contact_number'] = $contactNumber;
                    $params[':product_notes'] = $_POST['product_notes'] ?? null;  // Add this line
                    break;
            }

            // Just prepare and execute the SQL query
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute($params);
            } catch (PDOException $e) {
                error_log('SQL Error: ' . $e->getMessage());
                throw $e;
            }

            // Update order status based on payment method
            $newStatus = $paymentMethod === 'pickup' ? 'Pickup Pending' : 'Payment Pending';
            $stmtOrder = $pdo->prepare("
                UPDATE order_list 
                SET status = :status 
                WHERE order_id = :order_id
            ");
            $stmtOrder->execute([
                ':status' => $newStatus,
                ':order_id' => $receipt['order_id']
            ]);

            // After successful payment insertion and before commit
            try {
                // Insert into daily_sales table
                $salesSql = "INSERT INTO daily_sales (
                    seller_id,
                    seller_name,
                    sale_date,
                    product_name,
                    length_feet,
                    width_feet,
                    height_feet,
                    quantity,
                    total_amount,
                    created_at
                ) VALUES (
                    :seller_id,
                    :seller_name,
                    CURRENT_DATE(),
                    :product_name,
                    :length_feet,
                    :width_feet,
                    :height_feet,
                    :quantity,
                    :total_amount,
                    CURRENT_TIMESTAMP
                )";

                $salesStmt = $pdo->prepare($salesSql);
                $salesStmt->execute([
                    ':seller_id' => $receipt['seller_id'],
                    ':seller_name' => $receipt['seller_name'],
                    ':product_name' => $receipt['product_name'],
                    ':length_feet' => $receipt['length_feet'],
                    ':width_feet' => $receipt['width_feet'],
                    ':height_feet' => $receipt['height_feet'],
                    ':quantity' => $receipt['quantity'],
                    ':total_amount' => $receipt['total_amount']
                ]);

                // Update inventory quantity
                $updateInventorySql = "UPDATE product_inventory 
                    SET quantity = quantity - :sold_quantity 
                    WHERE seller_id = :seller_id 
                    AND product_name = :product_name
                    AND length_feet = :length_feet
                    AND width_feet = :width_feet
                    AND height_feet = :height_feet";

                $updateInventoryStmt = $pdo->prepare($updateInventorySql);
                $updateInventoryStmt->execute([
                    ':sold_quantity' => $receipt['quantity'],
                    ':seller_id' => $receipt['seller_id'],
                    ':product_name' => $receipt['product_name'],
                    ':length_feet' => $receipt['length_feet'],
                    ':width_feet' => $receipt['width_feet'],
                    ':height_feet' => $receipt['height_feet']
                ]);

                $pdo->commit();
                
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_message'] = "Payment processed successfully. Reference #: " . $referenceNumber;
                
                header("Location: order_list.php");
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                $errorMessage = "Database error occurred. Please try again.";
                error_log('Payment Error: ' . $e->getMessage());
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = $e->getMessage();
            error_log('Validation Error: ' . $e->getMessage());
        }
    }
}

// Format the size string
$sizeStr = $receipt['length_feet'] . "' x " . $receipt['width_feet'] . "' x " . $receipt['height_feet'] . "'";

// Format the date
$receiptDate = new DateTime($receipt['receipt_date']);
$formattedDate = $receiptDate->format('m/d/Y');
$formattedTime = $receiptDate->format('h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options - Elwood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Back button styles */
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #8B4513;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 100;
            transition: all 0.3s ease;
            text-decoration: none; /* Add this line to remove the underline */
        }

        .back-button:hover {
            background-color: #A0522D;
            transform: scale(1.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #8B4513;
            margin-bottom: 10px;
        }

        .receipt-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #eee;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-total {
            font-weight: bold;
            color: #8B4513;
            font-size: 1.2em;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #ddd;
        }

        .payment-options {
            margin-top: 30px;
        }

        .payment-title {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .payment-methods {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .payment-method {
            flex: 1;
            min-width: 200px;
            padding: 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: #8B4513;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .payment-method.selected {
            border-color: #8B4513;
            background-color: #fff8f3;
        }

        .payment-method i {
            font-size: 2em;
            color: #8B4513;
            margin-bottom: 10px;
        }

        .payment-form {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .gcash-form, .cod-form {
            display: none;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            margin-right: 10px;
        }

        .btn:not(:disabled) {
            background-color: #8B4513;
            color: white;
        }

        .btn:disabled {
            background-color: #ddd;
            cursor: not-allowed;
        }

        .buttons {
            margin-top: 20px;
            text-align: right;
        }

        .qr-code {
            margin: 20px 0;
            text-align: center;
        }

        .qr-code img {
            max-width: 200px;
            height: auto;
        }

        .gcash-number {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
    </style>
    
    <div class="container">
        <a href="order_list.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="header">
            <h1>Payment Options</h1>
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message" style="color: red; background-color: #ffeeee; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="receipt-summary">
            <div class="receipt-row">
                <span>Product:</span>
                <span><?php echo htmlspecialchars($receipt['product_name']); ?></span>
            </div>
            <div class="receipt-row">
                <span>Size:</span>
                <span><?php echo htmlspecialchars($sizeStr); ?></span>
            </div>
            <div class="receipt-row">
                <span>Quantity:</span>
                <span><?php echo htmlspecialchars($receipt['quantity']); ?></span>
            </div>
            <div class="receipt-row">
                <span>Seller:</span>
                <span><?php echo htmlspecialchars($receipt['seller_name']); ?></span>
            </div>
            <div class="receipt-total">
                <span>Total Amount:</span>
                <span>₱<?php echo number_format($receipt['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="payment-options">
            <h2 class="payment-title">Select Payment Method</h2>
            <div class="payment-methods">
                <div class="payment-method" id="gcash-option" onclick="selectPaymentMethod('gcash')">
                    <i class="fas fa-mobile-alt"></i>
                    <h3>GCash</h3>
                    <p>Pay securely using your GCash account</p>
                </div>

                <div class="payment-method" id="cod-option" onclick="selectPaymentMethod('cod')">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Cash on Delivery</h3>
                    <p>Pay when you receive your product</p>
                </div>

                <div class="payment-method" id="pickup-option" onclick="selectPaymentMethod('pickup')">
                    <i class="fas fa-store"></i>
                    <h3>Store Pickup</h3>
                    <p>Pick up your order from our store</p>
                </div>
            </div>
        </div>

        <form id="paymentForm" method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?receipt_id=' . $receiptId; ?>" class="payment-form" enctype="multipart/form-data">
            <input type="hidden" name="payment_method" id="paymentMethodInput" value="">
            
            <!-- GCash Form -->
            <div id="gcashForm" class="gcash-form" style="display: none;">
                <h4>GCash Payment Instructions</h4>
                <p>Please send the payment to this GCash account:</p>
                <div class="gcash-number">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($receipt['gcash_name'] ?? 'Not available'); ?></p>
                    <p><strong>Number:</strong> <?php echo htmlspecialchars($receipt['gcash_number'] ?? 'Not available'); ?></p>
                </div>
                
                <div class="qr-code">
                    <?php if (!empty($receipt['gcash_qr'])): ?>
                        <img src="<?php echo htmlspecialchars($receipt['gcash_qr']); ?>" alt="GCash QR Code">
                    <?php else: ?>
                        <i class="fas fa-qrcode fa-5x"></i>
                        <p>QR Code not available</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="gcashNumber">Your GCash Number</label>
                    <input type="text" id="gcashNumber" name="gcash_number" class="form-control" placeholder="09XX-XXX-XXXX" required>
                </div>
                
                <div class="form-group">
                    <label for="gcashName">Name on GCash Account</label>
                    <input type="text" id="gcashName" name="gcash_name" class="form-control" placeholder="Enter name on GCash account" required>
                </div>

                <div class="form-group">
                    <label for="paymentScreenshot">Payment Screenshot</label>
                    <input type="file" id="paymentScreenshot" name="payment_screenshot" class="form-control" accept="image/*" required>
                    <small class="form-text text-muted">Please upload a screenshot of your GCash payment as proof.</small>
                </div>
                
                <div class="form-group">
                    <label for="gcashProductNotes">Product Notes (Optional)</label>
                    <textarea id="gcashProductNotes" name="product_notes" class="form-control" rows="2" placeholder="Any special instructions for your product"></textarea>
                </div>
            </div>
            
            <!-- COD Form -->
            <div id="codForm" class="cod-form" style="display: none;">
                <h4>Cash on Delivery Information</h4>
                <p>Please confirm your delivery address:</p>
                
                <div class="form-group">
                    <label for="deliveryAddress">Delivery Address</label>
                    <textarea id="deliveryAddress" name="delivery_address" class="form-control" rows="3" required><?php echo htmlspecialchars($userDetails['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contactNumber">Contact Number</label>
                    <input type="text" id="contactNumber" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($userDetails['contactno']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="codProductNotes">Product Notes (Optional)</label>
                    <textarea id="codProductNotes" name="product_notes" class="form-control" rows="2" placeholder="Any special instructions for your product"></textarea>
                </div>
            </div>

            <!-- Pickup Form -->
            <div id="pickupForm" class="pickup-form" style="display: none;">
                <h4>Store Pickup Information</h4>
                <p>Please select your preferred pickup schedule:</p>
                
                <div class="form-group">
                    <label for="pickupDate">Preferred Pickup Date</label>
                    <input type="date" id="pickupDate" name="pickup_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="pickupTime">Preferred Pickup Time</label>
                    <select id="pickupTime" name="pickup_time" class="form-control" required>
                        <option value="">Select time...</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                        <option value="17:00">5:00 PM</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="pickupContactNumber">Contact Number</label>
                    <input type="text" id="pickupContactNumber" name="pickup_contact" class="form-control" value="<?php echo htmlspecialchars($userDetails['contactno']); ?>" required>
                </div>
            </div>
            
            <div class="buttons">
                <a href="order_list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn" id="confirmPaymentBtn" disabled>Confirm Payment</button>
            </div>
        </form>
    </div>

    <script>
        // Update the selectPaymentMethod function
        function selectPaymentMethod(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
        
            // Add selected class to clicked payment method
            document.getElementById(method + '-option').classList.add('selected');
        
            // Hide all payment forms
            document.getElementById('gcashForm').style.display = 'none';
            document.getElementById('codForm').style.display = 'none';
            document.getElementById('pickupForm').style.display = 'none';
        
            // Show selected payment form
            document.getElementById(method + 'Form').style.display = 'block';
        
            // Update hidden input value
            document.getElementById('paymentMethodInput').value = method;
            
            // Enable the confirm payment button
            const confirmButton = document.getElementById('confirmPaymentBtn');
            confirmButton.disabled = false;
            confirmButton.style.backgroundColor = '#8B4513';
            confirmButton.style.cursor = 'pointer';
        }
        
        // Add this at the beginning of your script
        document.addEventListener('DOMContentLoaded', function() {
            // Initially disable the confirm button
            const confirmButton = document.getElementById('confirmPaymentBtn');
            if (confirmButton) {
                confirmButton.disabled = true;
                confirmButton.style.backgroundColor = '#ddd';
                confirmButton.style.cursor = 'not-allowed';
            }
        });

        // Replace the existing form submission code
        document.addEventListener('DOMContentLoaded', function() {
            const paymentForm = document.getElementById('paymentForm');
            
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    
                    const paymentMethod = document.getElementById('paymentMethodInput').value;
                    if (!paymentMethod) {
                        showError('Please select a payment method');
                        return false;
                    }
                    
                    let isValid = true;
                    let formData = new FormData(this);
                    
                    // Validate based on payment method
                    switch(paymentMethod) {
                        case 'gcash':
                            isValid = validateGcashFields(formData);
                            break;
                        case 'cod':
                            isValid = validateCodFields(formData);
                            break;
                        case 'pickup':
                            isValid = validatePickupFields(formData);
                            break;
                    }
                    
                    if (isValid) {
                        const button = document.getElementById('confirmPaymentBtn');
                        button.disabled = true;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                        
                        // Submit the form normally
                        this.submit();
                    }
                });
            }
        });

        // Add these helper functions
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.cssText = 'color: red; background-color: #ffeeee; padding: 10px; border-radius: 5px; margin: 10px 0;';
            errorDiv.textContent = message;
            document.querySelector('.header').appendChild(errorDiv);
        }

        function validateGcashFields(formData) {
            if (!formData.get('gcash_number')) {
                showError('GCash number is required');
                return false;
            }
            if (!formData.get('gcash_name')) {
                showError('GCash account name is required');
                return false;
            }
            if (!formData.get('payment_screenshot').size) {
                showError('Payment screenshot is required');
                return false;
            }
            return true;
        }

        function validateCodFields(formData) {
            if (!formData.get('delivery_address')) {
                showError('Delivery address is required');
                return false;
            }
            if (!formData.get('contact_number')) {
                showError('Contact number is required');
                return false;
            }
            return true;
        }

        function validatePickupFields(formData) {
            const pickupDate = formData.get('pickup_date');
            const pickupTime = formData.get('pickup_time');
            const pickupContact = formData.get('pickup_contact');
        
            if (!pickupDate) {
                showError('Pickup date is required');
                return false;
            }
            
            // Validate if date is not in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const selectedDate = new Date(pickupDate);
            if (selectedDate < today) {
                showError('Pickup date cannot be in the past');
                return false;
            }
        
            if (!pickupTime) {
                showError('Pickup time is required');
                return false;
            }
            
            if (!pickupContact) {
                showError('Contact number is required');
                return false;
            }
            
            // Validate contact number format (Philippine format)
            const contactRegex = /^(09|\+639)\d{9}$/;
            if (!contactRegex.test(pickupContact.replace(/[-\s]/g, ''))) {
                showError('Please enter a valid contact number (e.g., 09XXXXXXXXX)');
                return false;
            }
        
            return true;
        }
    </script>
</body>
</html>