<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

// Fetch orders from notifications table for this seller
$stmt = $pdo->prepare("
    SELECT 
        n.id,
        n.user_id,
        n.type,
        n.message,
        n.reference_id,
        n.is_read,
        n.created_at,
        ol.product_name,
        ol.length_feet,
        ol.width_feet,
        ol.height_feet,
        ol.quantity,
        pc.price_per_sqft,
        CASE 
            WHEN ol.status IN ('Pending', 'Ordered') THEN 'Pending Orders'
            ELSE ol.status 
        END as status,
        u.firstname,
        u.lastname,
        u.contactno,
        u.address
    FROM notifications n
    JOIN order_list ol ON n.reference_id = ol.order_id
    JOIN users u ON ol.buyer_id = u.id
    LEFT JOIN product_catalog pc ON ol.product_name = pc.product_name 
        AND ol.length_feet = pc.length_feet 
        AND ol.width_feet = pc.width_feet 
        AND ol.height_feet = pc.height_feet
        AND pc.seller_id = :seller_id
    WHERE n.user_id = :user_id 
    AND n.type = 'order'
    ORDER BY n.created_at DESC
");

$stmt->execute([
    'seller_id' => $_SESSION['user_id'],
    'user_id' => $_SESSION['user_id']
]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Include the same CSS from seller_dashboard.php */
        /* Add these additional styles for the orders page */
        .orders-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .order-filters {
            margin-bottom: 20px;
            flex-wrap: wrap;
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
            white-space: nowrap;
            border: none;
            border-radius: 5px;
            background-color: var(--light-brown);
            color: var(--primary-brown);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn.active {
            background-color: var(--primary-brown);
            color: white;
        }

        .orders-table {
            min-width: 800px;
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        @media screen and (max-width: 1024px) {
            .actions-cell {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .action-btn, .status-select {
                width: 100%;
                margin: 2px 0;
            }
        }
        @media screen and (max-width: 768px) {
            .dashboard-header {
                padding: 15px;
                margin-bottom: 15px;
            }

            .dashboard-header h1 {
                font-size: 24px;
            }

            .orders-container {
                padding: 10px;
            }

            .orders-table th, 
            .orders-table td {
                padding: 10px;
                font-size: 14px;
            }

            .buyer-info, 
            .order-details {
                font-size: 13px;
            }
        }
        @media screen and (max-width: 480px) {
            .filter-btn {
                padding: 6px 12px;
                font-size: 13px;
            }

            .orders-table th, 
            .orders-table td {
                padding: 8px;
                font-size: 13px;
            }
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .orders-table th {
            background-color: var(--light-brown);
            color: var(--primary-brown);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-processing {
            background-color: #CCE5FF;
            color: #004085;
        }

        .status-shipped {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-delivered {
            background-color: #C3E6CB;
            color: #1E7E34;
        }

        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            vertical-align: middle;
        }

        .view-btn {
            background-color: var(--primary-brown);
            color: white;
        }

        .update-btn {
            background-color: var(--secondary-brown);
            color: white;
        }

        .unread-notification {
            background-color: #fff3e0;
        }
        
        .status-badge.status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .notification-dot {
            width: 8px;
            height: 8px;
            background-color: #ff4444;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }

        .message-content {
            white-space: pre-line;
            line-height: 1.4;
        }

        .order-time {
            color: #666;
            font-size: 0.9em;
        }

        .buyer-info {
            margin-top: 5px;
            font-size: 0.9em;
            color: #555;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        #statusSelect {
            width: 100%;
            padding: 10px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .update-status-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-brown);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }

        .update-status-btn:hover {
            background-color: var(--secondary-brown);
        }

        .actions-cell {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            white-space: nowrap;
        }

        .status-select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            color: #333;
            font-size: 0.9em;
            cursor: pointer;
            transition: all 0.3s ease;
            vertical-align: middle;
        }

        .status-select:hover:not(:disabled) {
            border-color: var(--primary-brown);
        }

        .status-select:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .view-btn {
            background-color: var(--primary-brown);
            color: white;
        }

        .mark-read-btn {
            background-color: var(--secondary-brown);
            color: white;
        }

        .view-btn:hover, .mark-read-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Product Details Modal Styles */
        .product-details-modal {
            max-width: 600px;
            padding: 30px;
        }

        .product-details-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .product-image-container {
            flex: 0 0 200px;
            height: 200px;
        }

        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-info-container {
            flex: 1;
        }

        .product-info-container h3 {
            color: var(--primary-brown);
            margin-bottom: 15px;
        }

        .product-specs {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .product-specs p {
            margin: 0;
            font-size: 1.1em;
        }

        .check-product-btn {
            background-color: #2c3e50;
            color: white;
        }

        .check-product-btn:hover {
            background-color: #34495e;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .check-products-btn {
            background-color: #2c3e50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .check-products-btn:hover {
            background-color: #34495e;
            transform: translateY(-1px);
        }

        .tally-order-btn {
            background-color: #5a3921;
            color: white;
        }

        .divider {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #ddd;
        }

        .tally-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .total-amount {
            font-size: 1.2em;
            color: #5a3921;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px dashed #ddd;
        }

        #tallyOrderSection {
            margin-top: 20px;
        }

        .tally-modal {
            max-width: 500px;
            padding: 25px;
        }

        .tally-container {
            background-color: #fff;
            border-radius: 8px;
        }

        .tally-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .order-number {
            color: #666;
            font-size: 0.9em;
        }

        .tally-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .tally-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #ddd;
            border-bottom: none;
            font-size: 1.2em;
            font-weight: bold;
            color: #5a3921;
        }

        .tally-actions {
            margin-top: 20px;
            text-align: right;
        }

        .save-tally-btn {
            background-color: #5a3921;
            color: white;
        }

        .save-tally-btn:hover {
            background-color: #4a2911;
        }

        /* Receipt Modal Styles */
        .receipt-modal {
            max-width: 500px;
            padding: 30px;
        }

        .receipt-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #ddd;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .receipt-number, .receipt-date {
            color: #666;
            margin: 5px 0;
        }

        .receipt-body {
            padding: 20px 0;
        }

        .details-row {
            margin-bottom: 15px;
        }

        .details-column {
            padding: 0 15px;
        }

        .details-column p {
            margin: 8px 0;
            line-height: 1.4;
        }

        .amount-details {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        .create-receipt-btn {
            background-color: #28a745;
            color: white;
        }

        .create-receipt-btn:hover {
            background-color: #218838;
        }

        .print-receipt-btn {
            background-color: #17a2b8;
            margin-left: 10px;
        }

        .view-receipt-btn {
            background-color: #17a2b8;
            color: white;
        }

        .view-receipt-btn:hover {
            background-color: #138496;
        }
        .payment-details-modal {
    max-width: 400px;
    max-height: 80vh;
    padding: 20px;
    overflow-y: auto;
}

.payment-info {
    margin-top: 15px;
}

.payment-info p {
    margin: 10px 0;
    line-height: 1.6;
}
#paymentScreenshot {
    max-width: 100%;
    height: auto;
    max-height: 300px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.payment-info p {
    margin: 8px 0;
    line-height: 1.4;
    font-size: 14px;
}

.payment-section {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.payment-section:last-child {
    border-bottom: none;
}

.payment-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
}

.status-pending {
    background-color: #FFF3CD;
    color: #856404;
}

.status-completed {
    background-color: #D4EDDA;
    color: #155724;
}
.view-payment-btn {
    background-color: #28a745;
    color: white;
}

.view-payment-btn:hover {
    background-color: #218838;
}
.status-ready {
        background-color: #E8D5F9;  /* Light purple background */
        color: #6A1B9A;            /* Dark purple text */
    }
    .status-ready-for-pick-up {
        background-color: #E8D5F9;  /* Light purple background */
        color: #6A1B9A;            /* Dark purple text */
    }
    .laborer-info-btn {
        background: none;
        border: none;
        color: var(--primary-brown);
        cursor: pointer;
        padding: 5px;
        margin-left: 5px;
        transition: color 0.3s ease;
    }

    .laborer-info-btn:hover {
        color: var(--secondary-brown);
    }

    .laborer-info-modal {
        max-width: 300px;
        text-align: center;
    }

    .laborer-info-content {
        padding: 20px;
    }

    .laborer-info-content p {
        margin: 10px 0;
        color: #666;
    }
    </style>
 <style>
    /* Add these new styles after your existing root variables */
    .burger-menu {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: var(--primary-brown);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.2em;
    }

    /* Update the sidebar and responsive styles */
    .sidebar {
        transition: transform 0.3s ease;
    }

    @media screen and (max-width: 768px) {
            .burger-menu {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px !important; /* Override the default width */
                z-index: 1000;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 60px;
            }

            /* Show profile details and nav links text */
            .profile-details, 
            .nav-links span, 
            .logout-btn span {
                display: block !important;
            }

            /* Fix navigation links display */
            .nav-links a {
                padding: 15px 25px;
                justify-content: flex-start;
            }

            .nav-links i {
                width: 24px;
                margin-right: 15px;
            }

            /* Fix logout button */
            .logout-btn a {
                padding: 12px 25px;
                justify-content: flex-start;
            }

            .logout-btn i {
                margin-right: 15px;
            }

            /* Ensure profile section is visible */
            .profile-section {
                padding: 20px;
                display: block;
            }

            .profile-image {
                width: 100px;
                height: 100px;
                margin: 0 auto 15px;
            }
        }
</style>

</head>

<body>
        <button class="burger-menu" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    <!-- Include the sidebar from seller_dashboard.php -->
    <?php include 'includes/seller_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Orders Management</h1>
            <p>View and manage your orders</p>
        </div>

        <div class="orders-container">
            <div class="order-filters">
                <button class="filter-btn active" data-status="all">All Orders</button>
                <button class="filter-btn" data-status="pending orders">Pending Orders</button>
                <button class="filter-btn" data-status="processing">Processing</button>
                <button class="filter-btn" data-status="completed">Completed</button>
                <button class="filter-btn" data-status="cancelled">Cancelled</button>
            </div>

            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order Details</th>
                        <th>Buyer Information</th>
                        <th>Status</th>
                        <th style="min-width: 250px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $order): ?>
                        <tr class="<?php echo $order['is_read'] ? '' : 'unread-notification'; ?>">
                            <td class="order-time">
                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                <?php if (!$order['is_read']): ?>
                                    <span class="notification-dot" title="New Order"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="order-details">
                                    <strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?><br>
                                    <strong>Size:</strong> <?php echo htmlspecialchars($order['length_feet']); ?>' x 
                                                 <?php echo htmlspecialchars($order['width_feet']); ?>' x 
                                                 <?php echo htmlspecialchars($order['height_feet']); ?>'<br>
                                    <strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?> pieces
                                </div>
                            </td>
                            <td>
                                <div class="buyer-info">
                                    <strong>Buyer:</strong> 
                                    <?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?><br>
                                    <strong>Contact:</strong> 
                                    <?php echo htmlspecialchars($order['contactno']); ?><br>
                                    <strong>Address:</strong> 
                                    <?php echo htmlspecialchars($order['address']); ?>
                                </div>
                            </td>
                            <td>
    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
        <?php echo htmlspecialchars($order['status']); ?>
    </span>
    <?php if ($order['status'] === 'Ready for Pick Up'): ?>
        <button class="laborer-info-btn" onclick="viewLaborerInfo(<?php echo $order['reference_id']; ?>)">
            <i class="fas fa-user-clock"></i>
        </button>
    <?php endif; ?>
</td>
                            <td>
                            <div class="actions-cell">
                    <?php if ($order['status'] === 'Payment Pending' || $order['status'] === 'Pickup Pending'): ?>
                        <button class="action-btn view-payment-btn" 
                                onclick="viewPaymentDetails(<?php echo $order['reference_id']; ?>)">
                            <i class="fas fa-money-bill"></i> View Payment
                        </button>
                    <?php else: ?>
                        <button class="action-btn view-btn" 
                                onclick="checkInProduct(
                                    '<?php echo htmlspecialchars($order['product_name']); ?>', 
                                    <?php echo $order['length_feet']; ?>, 
                                    <?php echo $order['width_feet']; ?>, 
                                    <?php echo $order['height_feet']; ?>, 
                                    <?php echo $order['quantity']; ?>,
                                    <?php echo $order['price_per_sqft']; ?>,
                                    <?php echo $order['reference_id']; ?>
                                )">
                            <i class="fas fa-eye"></i> View
            </button>
        <?php endif; ?>
                                    <?php
                                    // Check if order has been tallied
                                    $tallyCheck = $pdo->prepare("SELECT id FROM cus_orders WHERE order_id = ?");
                                    $tallyCheck->execute([$order['reference_id']]);
                                    
                                    if ($tallyCheck->rowCount() > 0):
                                        // Check if receipt exists
                                        $receiptCheck = $pdo->prepare("SELECT receipt_number FROM Receipt WHERE order_id = ?");
                                        $receiptCheck->execute([$order['reference_id']]);
                                        
                                        if ($receiptCheck->rowCount() > 0):
                                            $receipt = $receiptCheck->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                            <button class="action-btn view-receipt-btn" 
                                                    onclick="viewReceipt(<?php echo $order['reference_id']; ?>)">
                                                <i class="fas fa-file-invoice"></i> View Receipt
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn create-receipt-btn" 
                                                    onclick="createReceipt(<?php echo $order['reference_id']; ?>)">
                                                <i class="fas fa-file-invoice"></i> Create Receipt
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (!$order['is_read']): ?>
                                        <button class="action-btn mark-read-btn" 
                                                onclick="markAsRead(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-check"></i> Mark as Read
                                        </button>
                                    <?php endif; ?>
                                    <select class="status-select" 
                                            onchange="updateOrderStatus(<?php echo $order['reference_id']; ?>, this.value)"
                                            <?php echo ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') ? 'disabled' : ''; ?>>
                                        <option value="">Update Status</option>
                                        <option value="Processing">Processing</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productDetailsModal" class="modal">
        <div class="modal-content product-details-modal">
            <span class="close" onclick="closeProductModal()">&times;</span>
            <h2>Product Details</h2>
            <div class="product-details-container">
                <div class="product-image-container">
                    <img id="productImage" src="" alt="Product Image">
                </div>
                <div class="product-info-container">
                    <h3 id="productName"></h3>
                    <div class="product-specs">
                        <p><strong>Size:</strong> <span id="productSize"></span></p>
                        <p><strong>Quantity:</strong> <span id="productQuantity"></span></p>
                        <p><strong>Price:</strong> <span id="productPrice"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this after your existing product details modal -->
    <div id="tallyOrderModal" class="modal">
        <div class="modal-content tally-modal">
            <span class="close" onclick="closeTallyModal()">&times;</span>
            <h2>Order Tally</h2>
            <div class="tally-container">
                <div class="tally-header">
                    <h3 id="tallyProductName"></h3>
                    <p class="order-number">Order #: <span id="tallyOrderId"></span></p>
                </div>
                <div class="tally-details">
                    <div class="tally-row">
                        <span class="label">Size:</span>
                        <span id="tallySize" class="value"></span>
                    </div>
                    <div class="tally-row">
                        <span class="label">Quantity:</span>
                        <span id="tallyQuantity" class="value"></span>
                    </div>
                    <div class="tally-row">
                        <span class="label">Price per piece:</span>
                        <span id="tallyPricePerSqft" class="value"></span>
                    </div>
                    <div class="tally-row total">
                        <span class="label">Total Amount:</span>
                        <span id="tallyTotalAmount" class="value"></span>
                    </div>
                </div>
                <div class="tally-actions">
                    <button class="action-btn save-tally-btn" onclick="saveTallyOrder()">
                        <i class="fas fa-save"></i> Save Tally
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update the receipt modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content receipt-modal">
            <span class="close" onclick="closeReceiptModal()">&times;</span>
            <div class="receipt-container">
                <div class="receipt-header">
                    <h2>Official Receipt</h2>
                    <p class="receipt-number">Receipt #: <span id="receiptNumber"></span></p>
                    <p class="receipt-date">Date: <span id="receiptDate"></span></p>
                </div>
                <div class="receipt-body">
                    <div class="details-row">
                        <div class="details-column">
                            <p><strong>Buyer:</strong> <span id="receiptBuyerName"></span></p>
                            <p><strong>Seller:</strong> <span id="receiptSellerName"></span></p>
                        </div>
                    </div>
                    <div class="details-row">
                        <div class="details-column">
                            <p><strong>Product:</strong> <span id="receiptProductName"></span></p>
                            <p><strong>Size:</strong> <span id="receiptSize"></span></p>
                            <p><strong>Quantity:</strong> <span id="receiptQuantity"></span></p>
                            <p class="total-amount"><strong>Total Amount:</strong> <span id="receiptTotalAmount"></span></p>
                        </div>
                    </div>
                </div>
                <div class="receipt-actions">
                    <button class="action-btn save-receipt-btn" onclick="saveReceiptData()">
                        <i class="fas fa-save"></i> Save Receipt
                    </button>
                    <button class="action-btn print-receipt-btn" onclick="printReceipt()">
                        <i class="fas fa-print"></i> Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div id="paymentDetailsModal" class="modal">
    <div class="modal-content payment-details-modal">
        <span class="close" onclick="closePaymentModal()">&times;</span>
        <h2>Payment Details</h2>
        <div class="payment-details-container">
            <div class="payment-info">
                <div class="payment-section">
                    <p><strong>Payment Method:</strong> <span id="paymentMethod"></span></p>
                    <p><strong>Reference Number:</strong> <span id="referenceNumber"></span></p>
                    <p><strong>Payment Status:</strong> <span id="paymentStatus"></span></p>
                    <p><strong>Payment Date:</strong> <span id="paymentDate"></span></p>
                </div>
                
                <!-- GCash specific details -->
                <div id="gcashDetails" class="payment-section" style="display: none;">
                    <p><strong>GCash Number:</strong> <span id="gcashNumber"></span></p>
                    <p><strong>GCash Name:</strong> <span id="gcashName"></span></p>
                    <p><strong>Screenshot:</strong></p>
                    <img id="paymentScreenshot" src="" alt="Payment Screenshot">
                </div>

                <!-- COD specific details -->
                <div id="codDetails" style="display: none;">
                    <p><strong>Delivery Address:</strong> <span id="deliveryAddress"></span></p>
                    <p><strong>Contact Number:</strong> <span id="contactNumber"></span></p>
                    <p><strong>Delivery Notes:</strong> <span id="deliveryNotes"></span></p>
                </div>

                <!-- Pickup specific details -->
                <div id="pickupDetails" style="display: none;">
                    <p><strong>Pickup Date:</strong> <span id="pickupDate"></span></p>
                    <p><strong>Pickup Time:</strong> <span id="pickupTime"></span></p>
                    <p><strong>Contact Number:</strong> <span id="pickupContact"></span></p>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="laborerInfoModal" class="modal">
    <div class="modal-content laborer-info-modal">
        <span class="close" onclick="closeLaborerInfoModal()">&times;</span>
        <div class="laborer-info-content">
            <h3>Status Updated By</h3>
            <p id="laborerName"></p>
            <p id="updateTime"></p>
        </div>
    </div>
</div>
<script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const burgerMenu = document.querySelector('.burger-menu');
            
            if (!sidebar.contains(event.target) && 
                !burgerMenu.contains(event.target) && 
                window.innerWidth <= 768 && 
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    </script>

    <script>
        
    // Add this JavaScript for updating order status
    function updateOrderStatus(orderId, status) {
        if (!status) return; // Don't proceed if no status is selected
        
        if (!confirm(`Are you sure you want to update this order to ${status}?`)) {
            // Reset the select element to its placeholder
            event.target.value = "";
            return;
        }

        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Order status updated successfully');
                location.reload(); // Reload to show updated status
            } else {
                alert('Error updating order status: ' + data.message);
                // Reset the select element to its placeholder
                event.target.value = "";
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating order status');
            // Reset the select element to its placeholder
            event.target.value = "";
        });
    }

    // Filter orders
    function filterOrders(status) {
        const rows = document.querySelectorAll('.orders-table tbody tr');
        rows.forEach(row => {
            const orderStatus = row.querySelector('.status-badge').textContent.trim().toLowerCase();
            
            if (status === 'all') {
                row.style.display = '';
            } else if (status === 'pending orders' && (orderStatus === 'pending' || orderStatus === 'ordered' || orderStatus === 'pending orders')) {
                // Show both Pending and Ordered status orders
                row.style.display = '';
            } else if (orderStatus === status.toLowerCase()) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Update the filter buttons click handler
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the status from the button's data attribute
            const status = this.dataset.status;
            filterOrders(status);
        });
    });

    function viewOrderDetails(notificationId, buyerId) {
        // Mark as read when viewing
        markAsRead(notificationId);
        
        // Fetch order details
        fetch(`get_order_details.php?buyer_id=${buyerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Display order details (implement your modal or preferred display method)
                    alert('Order details: ' + JSON.stringify(data.details));
                } else {
                    alert('Error loading order details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading order details');
            });
    }

    function markAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove unread styling
                const row = document.querySelector(`tr:has(button[onclick*="${notificationId}"])`);
                if (row) {
                    row.classList.remove('unread-notification');
                    const dot = row.querySelector('.notification-dot');
                    if (dot) dot.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function checkInProduct(productName, length, width, height, quantity, pricePerSqft, orderId) {
        console.log('Checking product:', { productName, length, width, height, quantity }); // Debug log

        // Fetch product details from my_products.php
        fetch('get_product_details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                product_name: productName,
                length_feet: length,
                width_feet: width,
                height_feet: height
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data); // Debug log

            if (data.success) {
                // Update modal with product details
                const modal = document.getElementById('productDetailsModal');
                const productImage = document.getElementById('productImage');
                const productNameEl = document.getElementById('productName');
                const productSize = document.getElementById('productSize');
                const productQuantity = document.getElementById('productQuantity');
                const productPrice = document.getElementById('productPrice');

                // Set image with fallback
                productImage.src = data.image_path || 'images/default-product.jpg';
                productImage.onerror = function() {
                    this.src = 'images/default-product.jpg';
                };

                // Set other details
                productNameEl.textContent = data.product_name;
                productSize.textContent = `${length}' x ${width}' x ${height}'`;
                productQuantity.textContent = `${quantity} pieces`;
                
                // Format price with commas and 2 decimal places
                const formattedPrice = parseFloat(data.price_per_sqft).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                productPrice.textContent = `₱${formattedPrice} per sq.ft`;

                // Create buttons container
                const buttonsContainer = document.createElement('div');
                buttonsContainer.className = 'modal-actions';

                // Create Tally Order button
                const tallyButton = document.createElement('button');
                tallyButton.className = 'action-btn tally-order-btn';
                tallyButton.innerHTML = '<i class="fas fa-calculator"></i> Tally Order';
                tallyButton.onclick = () => {
                    closeProductModal();
                    showTallyModal(productName, length, width, height, quantity, pricePerSqft, orderId);
                };

                // Create Check in My Products button
                const checkProductsButton = document.createElement('button');
                checkProductsButton.className = 'action-btn check-products-btn';
                checkProductsButton.innerHTML = '<i class="fas fa-search"></i> Check in My Products';
                checkProductsButton.onclick = () => {
                    // Store the product name in sessionStorage to highlight it on the next page
                    sessionStorage.setItem('highlightProduct', productName);
                    window.location.href = 'my_products.php';
                };

                // Add buttons to container
                buttonsContainer.appendChild(tallyButton);
                buttonsContainer.appendChild(checkProductsButton);

                // Add the buttons container to the modal
                const modalContent = document.querySelector('.product-details-modal');
                // Remove any existing buttons container
                const existingButtons = modalContent.querySelector('.modal-actions');
                if (existingButtons) {
                    existingButtons.remove();
                }
                modalContent.appendChild(buttonsContainer);

                // Show modal
                modal.style.display = 'flex';
            } else {
                alert(data.message || 'Error loading product details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product details. Please try again.');
        });
    }

    // Update the modal close function
    function closeProductModal() {
        const modal = document.getElementById('productDetailsModal');
        modal.style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            if (event.target.id === 'productDetailsModal') {
                closeProductModal();
            } else if (event.target.id === 'receiptModal') {
                closeReceiptModal();
            }
        }
    }

    // Add this JavaScript function after your existing JavaScript code
    function showTallyModal(productName, length, width, height, quantity, pricePerSqft, orderId) {
        // Calculate total amount based on quantity and price
        const totalAmount = quantity * pricePerSqft;

        // Update tally modal content
        document.getElementById('tallyProductName').textContent = productName;
        document.getElementById('tallyOrderId').textContent = orderId;
        document.getElementById('tallySize').textContent = `${length}' x ${width}' x ${height}'`;
        document.getElementById('tallyQuantity').textContent = `${quantity} pieces`;
        document.getElementById('tallyPricePerSqft').textContent = `₱${pricePerSqft.toFixed(2)}`;
        document.getElementById('tallyTotalAmount').textContent = `₱${totalAmount.toFixed(2)}`;

        // Store data for saving
        window.currentTallyData = {
            order_id: orderId,
            product_name: productName,
            length_feet: length,
            width_feet: width,
            height_feet: height,
            quantity: quantity,
            price_per_sqft: pricePerSqft,
            total_amount: totalAmount
        };

        // Show the modal
        document.getElementById('tallyOrderModal').style.display = 'flex';
    }

    function saveTallyOrder() {
        if (!window.currentTallyData) {
            alert('No tally data available');
            return;
        }

        fetch('save_tally_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(window.currentTallyData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Tally order saved successfully');
                closeTallyModal();
                // Reload the page to update the buttons
                location.reload();
            } else {
                alert('Error saving tally order: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving tally order');
        });
    }

    function closeTallyModal() {
        document.getElementById('tallyOrderModal').style.display = 'none';
        window.currentTallyData = null;
    }
    function createReceipt(orderId) {
    // Store the order ID globally for receipt creation
    window.currentReceiptOrderId = orderId;
    
    fetch('get_tally_details.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate receipt modal with tally details
                document.getElementById('receiptNumber').textContent = generateReceiptNumber();
                document.getElementById('receiptDate').textContent = new Date().toLocaleDateString();
                document.getElementById('receiptBuyerName').textContent = data.buyer_name;
                document.getElementById('receiptSellerName').textContent = data.seller_name;
                document.getElementById('receiptProductName').textContent = data.product_name;
                document.getElementById('receiptSize').textContent = `${data.length_feet}' x ${data.width_feet}' x ${data.height_feet}'`;
                document.getElementById('receiptQuantity').textContent = `${data.quantity} pieces`;
                document.getElementById('receiptTotalAmount').textContent = `₱${parseFloat(data.total_amount).toFixed(2)}`;

                // Show receipt modal
                document.getElementById('receiptModal').style.display = 'flex';
            } else {
                alert('Error loading tally details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading tally details');
        });
}
    function generateReceiptNumber() {
        // Generate a unique receipt number (you can modify this format)
        const date = new Date();
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        return `RCP-${year}${month}${day}-${random}`;
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').style.display = 'none';
    }
    function saveReceiptData() {
    // Get all the receipt data from the modal
    const receiptData = {
        receipt_number: document.getElementById('receiptNumber').textContent,
        order_id: window.currentReceiptOrderId, // Use the stored order ID
        product_name: document.getElementById('receiptProductName').textContent,
        size: document.getElementById('receiptSize').textContent,
        quantity: document.getElementById('receiptQuantity').textContent.replace(' pieces', ''),
        total_amount: document.getElementById('receiptTotalAmount').textContent.replace('₱', '').trim(),
        buyer_name: document.getElementById('receiptBuyerName').textContent,
        seller_name: document.getElementById('receiptSellerName').textContent,
        receipt_date: document.getElementById('receiptDate').textContent
    };

    // Send the data to the server
    fetch('save_receipt.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(receiptData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Receipt saved successfully');
            closeReceiptModal();
            location.reload(); // Reload to update the UI
        } else {
            alert('Error saving receipt: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving receipt');
    });
}
function viewReceipt(orderId) {
    fetch('get_receipt.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate receipt modal with saved receipt details
                document.getElementById('receiptNumber').textContent = data.receipt.receipt_number;
                document.getElementById('receiptDate').textContent = data.receipt.receipt_date;
                document.getElementById('receiptBuyerName').textContent = data.receipt.buyer_name;
                document.getElementById('receiptSellerName').textContent = data.receipt.seller_name;
                document.getElementById('receiptProductName').textContent = data.receipt.product_name;
                document.getElementById('receiptSize').textContent = data.receipt.size;
                document.getElementById('receiptQuantity').textContent = data.receipt.quantity + ' pieces';
                document.getElementById('receiptTotalAmount').textContent = '₱' + parseFloat(data.receipt.total_amount).toFixed(2);

                // Show receipt modal
                document.getElementById('receiptModal').style.display = 'flex';
            } else {
                alert('Error loading receipt: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading receipt');
        });
}
function viewPaymentDetails(orderId) {
    fetch('get_payment_details.php?order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal with payment details
                document.getElementById('paymentMethod').textContent = data.payment.payment_method;
                document.getElementById('referenceNumber').textContent = data.payment.reference_number;
                document.getElementById('paymentStatus').textContent = data.payment.payment_status;
                document.getElementById('paymentDate').textContent = new Date(data.payment.payment_date).toLocaleString();

                // Hide all specific details sections first
                document.getElementById('gcashDetails').style.display = 'none';
                document.getElementById('codDetails').style.display = 'none';
                document.getElementById('pickupDetails').style.display = 'none';

                // Show specific details based on payment method
                switch(data.payment.payment_method) {
                    case 'gcash':
                        document.getElementById('gcashDetails').style.display = 'block';
                        document.getElementById('gcashNumber').textContent = data.payment.gcash_number || 'Not provided';
                        document.getElementById('gcashName').textContent = data.payment.gcash_name || 'Not provided';
                        if (data.payment.screenshot_path) {
                            document.getElementById('paymentScreenshot').src = data.payment.screenshot_path;
                            document.getElementById('paymentScreenshot').style.display = 'block';
                        } else {
                            document.getElementById('paymentScreenshot').style.display = 'none';
                        }
                        break;

                    case 'cod':
                        document.getElementById('codDetails').style.display = 'block';
                        document.getElementById('deliveryAddress').textContent = data.payment.delivery_address || 'Not provided';
                        document.getElementById('contactNumber').textContent = data.payment.contact_number || 'Not provided';
                        document.getElementById('deliveryNotes').textContent = data.payment.delivery_notes || 'No notes provided';
                        break;

                    case 'pickup':
                        document.getElementById('pickupDetails').style.display = 'block';
                        const pickupDate = data.payment.pickup_date ? new Date(data.payment.pickup_date) : null;
                        const formattedDate = pickupDate ? pickupDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        }) : 'Not scheduled';

                        let formattedTime = 'Not scheduled';
                        if (data.payment.pickup_time) {
                            const timeArr = data.payment.pickup_time.split(':');
                            const hours = parseInt(timeArr[0]);
                            const minutes = timeArr[1];
                            const ampm = hours >= 12 ? 'PM' : 'AM';
                            const formattedHours = hours % 12 || 12;
                            formattedTime = `${formattedHours}:${minutes} ${ampm}`;
                        }

                        document.getElementById('pickupDate').textContent = formattedDate;
                        document.getElementById('pickupTime').textContent = formattedTime;
                        document.getElementById('pickupContact').textContent = data.payment.contact_number || 'Not provided';
                        break;
                }

                // Add product notes if available
                if (data.payment.product_notes) {
                    const notesElement = document.createElement('div');
                    notesElement.className = 'payment-section';
                    notesElement.innerHTML = `
                        <p><strong>Product Notes:</strong></p>
                        <p>${data.payment.product_notes}</p>
                    `;
                    document.querySelector('.payment-info').appendChild(notesElement);
                }

                // Show the modal
                document.getElementById('paymentDetailsModal').style.display = 'flex';
            } else {
                alert('Error loading payment details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading payment details');
        });
}

function closePaymentModal() {
    document.getElementById('paymentDetailsModal').style.display = 'none';
}
function viewLaborerInfo(orderId) {
        fetch('get_laborer_info.php?order_id=' + orderId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('laborerName').textContent = data.laborer_name;
                    document.getElementById('updateTime').textContent = data.update_time;
                    document.getElementById('laborerInfoModal').style.display = 'flex';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading laborer information');
            });
    }

    function closeLaborerInfoModal() {
        document.getElementById('laborerInfoModal').style.display = 'none';
    }
    </script>
</body>
</html>