<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['receipt_id'])) {
    header('Location: order_list.php');
    exit();
}

$receipt_id = $_GET['receipt_id'];

// Add buyer details to the SQL query
try {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               u.firstname as seller_firstname, 
               u.lastname as seller_lastname,
               u.contactno as seller_contact,
               u.address as seller_address,
               ol.product_name,
               ol.length_feet,
               ol.width_feet,
               ol.height_feet,
               ol.quantity,
               ol.order_id,
               b.firstname as buyer_firstname,
               b.lastname as buyer_lastname,
               b.contactno as buyer_contact,
               b.address as buyer_address
        FROM receipt r
        JOIN users u ON r.seller_id = u.id
        JOIN order_list ol ON r.order_id = ol.order_id
        JOIN users b ON ol.buyer_id = b.id
        WHERE r.id = :receipt_id AND ol.buyer_id = :buyer_id
    ");
    
    $stmt->execute([
        'receipt_id' => $receipt_id,
        'buyer_id' => $_SESSION['user_id']
    ]);
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: order_list.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: order_list.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Options - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-brown);
            margin: 0;
            padding: 20px;
        }

        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-details {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .order-details h2 {
            color: var(--primary-brown);
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }

        .detail-label {
            font-weight: bold;
            color: var(--secondary-brown);
        }

        .payment-options {
            margin-top: 20px;
        }

        .payment-options h3 {
            color: var(--primary-brown);
            margin-bottom: 15px;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .submit-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        .submit-btn:hover {
            background-color: var(--secondary-brown);
        }

        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-brown);
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="order-details">
            <h2>Order Details</h2>
            <div class="detail-row">
                <span class="detail-label">Product:</span>
                <span><?php echo htmlspecialchars($order['product_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Size:</span>
                <span><?php echo htmlspecialchars($order['length_feet'] . "' x " . $order['width_feet'] . "' x " . $order['height_feet'] . "'"); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Quantity:</span>
                <span><?php echo htmlspecialchars($order['quantity']); ?> pieces</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Seller:</span>
                <span><?php echo htmlspecialchars($order['seller_firstname'] . ' ' . $order['seller_lastname']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <div class="payment-options">
            <h3>Select Payment Method</h3>
            <form id="paymentForm" onsubmit="return processPayment(event)" enctype="multipart/form-data">
                <input type="hidden" name="receipt_id" value="<?php echo $receipt_id; ?>">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                <input type="hidden" name="amount" value="<?php echo $order['total_amount']; ?>">
                
                <select name="payment_method" id="paymentMethod" onchange="toggleGcashDetails(this.value)" required>
                    <option value="">Select payment method</option>
                    <option value="Gcash">GCash</option>
                    <option value="Cash on Delivery">Cash on Delivery</option>
                    <option value="Store Pickup">Store Pickup</option>
                </select>

                <div id="gcashDetails" style="display: none;">
                    <?php
                    // Fetch GCash details
                    $gcashStmt = $pdo->prepare("SELECT * FROM gcash WHERE seller_id = ?");
                    $gcashStmt->execute([$order['seller_id']]);
                    $gcashDetails = $gcashStmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <div class="gcash-info">
                        <h4>GCash Payment Details</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($gcashDetails['gcash_name']); ?></p>
                        <p><strong>Number:</strong> <?php echo htmlspecialchars($gcashDetails['gcash_number']); ?></p>
                        <p><strong>QR Code:</strong></p>
                        <img src="<?php echo htmlspecialchars($gcashDetails['gcash_qr']); ?>" alt="GCash QR Code" style="max-width: 200px;">
                        
                        <div class="proof-upload">
                            <p><strong>Upload Payment Screenshot:</strong></p>
                            <input type="file" name="payment_proof" id="paymentProof" accept="image/*" style="display: none;">
                            <button type="button" class="upload-btn" onclick="document.getElementById('paymentProof').click()">
                                <i class="fas fa-upload"></i> Select Screenshot
                            </button>
                            <div id="selectedFile" class="selected-file"></div>
                        </div>
                    </div>
                </div>

                <div id="codDetails" style="display: none;" class="delivery-info">
                    <h4>Delivery Information</h4>
                    <div class="buyer-details">
                        <p><strong>Recipient Name:</strong> <?php echo htmlspecialchars($order['buyer_firstname'] . ' ' . $order['buyer_lastname']); ?></p>
                        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order['buyer_contact']); ?></p>
                        <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['buyer_address']); ?></p>
                    </div>
                    <div class="delivery-note">
                        <p>* Please prepare the exact amount upon delivery</p>
                        <p>* Our delivery personnel will contact you before delivery</p>
                    </div>
                </div>

                <div id="storePickupDetails" style="display: none;" class="pickup-info">
                    <h4>Store Pickup Information</h4>
                    <div class="seller-details">
                        <p><strong>Store Owner:</strong> <?php echo htmlspecialchars($order['seller_firstname'] . ' ' . $order['seller_lastname']); ?></p>
                        <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order['seller_contact']); ?></p>
                        <p><strong>Store Address:</strong> <?php echo htmlspecialchars($order['seller_address']); ?></p>
                    </div>
                    <div class="pickup-note">
                        <p>* Please bring a valid ID for verification</p>
                        <p>* Store hours: 8:00 AM - 5:00 PM (Monday - Saturday)</p>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-money-bill-wave"></i> Proceed to Payment
                </button>
            </form>
        </div>

        <!-- Add these styles -->
        <style>
            .pickup-info {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .seller-details {
                margin: 15px 0;
            }

            .seller-details p {
                margin: 8px 0;
            }

            .pickup-note {
                margin-top: 15px;
                padding-top: 15px;
                border-top: 1px dashed #ddd;
                font-size: 0.9em;
                color: #666;
            }
        </style>

        <!-- Add this JavaScript before the closing body tag -->
        <script>
        function toggleGcashDetails(method) {
            const gcashDetails = document.getElementById('gcashDetails');
            const codDetails = document.getElementById('codDetails');
            const storePickupDetails = document.getElementById('storePickupDetails');
            const paymentProof = document.getElementById('paymentProof');
            
            // Hide all payment details first
            gcashDetails.style.display = 'none';
            codDetails.style.display = 'none';
            storePickupDetails.style.display = 'none';
            paymentProof.required = false;
            
            // Show relevant details based on payment method
            if (method === 'Gcash') {
                gcashDetails.style.display = 'block';
                paymentProof.required = true;
            } else if (method === 'Cash on Delivery') {
                codDetails.style.display = 'block';
            } else if (method === 'Store Pickup') {
                storePickupDetails.style.display = 'block';
            }
        }

        document.getElementById('paymentProof').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            document.getElementById('selectedFile').textContent = fileName || '';
        });

        function processPayment(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('.submit-btn');
            const paymentMethod = formData.get('payment_method');
            
            if (paymentMethod === 'Gcash' && !formData.get('payment_proof').size) {
                alert('Please upload your payment screenshot');
                return false;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            fetch('process_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Payment submitted successfully!');
                    window.location.href = 'order_list.php';
                } else {
                    alert(data.error || 'Error processing payment');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-money-bill-wave"></i> Proceed to Payment';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing payment');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-money-bill-wave"></i> Proceed to Payment';
            });

            return false;
        }
        </script>
</body>
</html>