<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if reference number is provided
if (!isset($_GET['ref'])) {
    header("Location: order_list.php");
    exit();
}

$referenceNumber = $_GET['ref'];

// Get payment details
$stmt = $pdo->prepare("
    SELECT p.*, r.receipt_number, r.total_amount
    FROM payments p
    JOIN receipt r ON p.receipt_id = r.id
    WHERE p.reference_number = ?
");
$stmt->execute([$referenceNumber]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: order_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Elwood</title>
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
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .success-icon {
            font-size: 5em;
            color: #28a745;
            margin-bottom: 20px;
        }

        h1 {
            color: #8B4513;
            margin-bottom: 20px;
        }

        .payment-details {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #8B4513;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #A0522D;
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Payment Successful!</h1>
        <p>Your payment has been processed successfully. We'll notify you once your order is ready.</p>
        
        <div class="payment-details">
            <div class="detail-row">
                <span>Reference Number:</span>
                <span><?php echo htmlspecialchars($referenceNumber); ?></span>
            </div>
            <div class="detail-row">
                <span>Receipt Number:</span>
                <span><?php echo htmlspecialchars($payment['receipt_number']); ?></span>
            </div>
            <div class="detail-row">
                <span>Payment Method:</span>
                <span><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></span>
            </div>
            <div class="detail-row">
                <span>Amount:</span>
                <span>₱<?php echo number_format($payment['total_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span>Status:</span>
                <span><?php echo htmlspecialchars(ucfirst($payment['payment_status'])); ?></span>
            </div>
        </div>
        
        <a href="order_list.php" class="btn">Back to Orders</a>
    </div>
</body>
</html>