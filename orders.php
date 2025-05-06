<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

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
        ol.status,
        pc.price_per_sqft,
        pc.image_path,
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
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #5a3921;
            --secondary-brown: #8b4513;
            --light-brown: #d2b48c;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .dashboard-header {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .dashboard-header h1 {
            color: var(--primary-brown);
            margin-bottom: 10px;
        }

        /* Orders Table Styles */
        .orders-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .order-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            background-color: var(--light-brown);
            color: var(--primary-brown);
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn.active {
            background-color: var(--primary-brown);
            color: white;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .orders-table th {
            background-color: var(--light-brown);
            color: var(--primary-brown);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    max-width: fit-content;
    width: auto;
    position: relative;
}

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .burger-menu {
                display: block;
            }
        }

        /* Update Sidebar and Responsive Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: var(--primary-brown);
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        /* Add these new styles for nav links */
        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin: 5px 0;
        }

        .nav-links a {
            color: white !important;
            text-decoration: none;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .nav-links a i {
            margin-right: 15px;
            width: 20px;
            color: white !important;
        }

        .nav-links a span {
            color: white !important;
            display: inline-block;
        }

        /* Update Media Query */
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .sidebar.active {
                width: 250px !important;
            }

            .nav-links a span {
                display: none;
            }

            .sidebar.active .nav-links a span {
                display: inline-block;
            }

            .nav-links a {
                justify-content: center;
                padding: 12px;
            }

            .sidebar.active .nav-links a {
                justify-content: flex-start;
                padding: 12px 20px;
            }

            .nav-links a i {
                margin-right: 0;
            }

            .sidebar.active .nav-links a i {
                margin-right: 15px;
            }
        }

        /* Profile section styles */
        .profile-section {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .profile-name {
            color: white;
            margin-bottom: 5px;
        }

        /* Update Media Query */
        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 60px;
            }

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
                width: 250px !important;
                height: 100vh;
            }

            .sidebar.active {
                transform: translateX(0);
                display: flex;
                flex-direction: column;
            }

            /* Ensure sidebar content is visible */
            .sidebar .nav-links,
            .sidebar .profile-section,
            .sidebar .logout-btn {
                display: block;
                width: 100%;
            }

            /* Add overlay when sidebar is active */
            .sidebar.active::after {
                content: '';
                position: fixed;
                top: 0;
                left: 250px;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: -1;
            }
        }

        /* Burger Menu - Update display conditions */
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
        }

        /* Update Media Query */
        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-top: 60px; /* Add space for burger menu */
            }

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .burger-menu {
                display: block !important; /* Force display on mobile */
            }
        }

        /* Burger Menu */
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
        }

        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            color: white;
        }

        .view-btn {
            background-color: var(--primary-brown);
        }

        .status-select {
            padding: 6px;
            border-radius: 4px;
        }

        /* Status Badges */
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

        .status-completed {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-cancelled {
            background-color: #F8D7DA;
            color: #721C24;
        }
        .create-receipt-btn {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.create-receipt-btn:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

.tally-details {
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 15px;
}
.receipt-btn {
    background-color: #17a2b8;
    color: white;
    margin: 5px 0;
}

.receipt-btn:hover {
    background-color: #138496;
}
.receipt-container {
    background: white;
    padding: 20px;
    border: 2px solid #8B4513;
    width: 400px;
    margin: 0;
    font-family: 'Courier New', monospace;
}

.receipt-header {
    text-align: center;
    margin-bottom: 20px;
}

.receipt-header h2 {
    margin: 0;
    font-size: 1.2em;
    font-weight: bold;
}

.receipt-date {
    text-align: left;
    margin-top: 10px;
}

.receipt-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.receipt-table th,
.receipt-table td {
    border: 1px solid #000;
    padding: 8px;
    text-align: left;
}

.receipt-table th {
    background-color: #fff;
    font-weight: bold;
}

.receipt-summary {
    margin-top: 20px;
}

.receipt-totals {
    border-top: 1px solid #000;
    padding-top: 10px;
}

.receipt-totals p {
    display: flex;
    justify-content: space-between;
    margin: 5px 0;
}

.receipt-totals .total {
    font-weight: bold;
    border-top: 1px solid #000;
    padding-top: 5px;
}

.receipt-number {
    text-align: left;
    margin-top: 5px;
    font-family: 'Courier New', monospace;
}

.receipt-info {
    margin: 15px 0;
    padding: 10px 0;
    border-bottom: 1px solid #000;
}

.receipt-info p {
    margin: 5px 0;
}

.payment-method input[type="checkbox"] {
    margin-right: 10px;
}
.payment-btn {
    background-color: #6f42c1;
    color: white;
    margin: 5px 0;
}

.payment-btn:hover {
    background-color: #5a32a3;
}

.payment-details {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.payment-info {
    margin: 15px 0;
}

.payment-proof {
    margin-top: 15px;
    text-align: center;
}

.payment-proof img {
    max-width: 300px;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
}
    </style>
</head>
<body>
    <button class="burger-menu" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <?php include 'includes/seller_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Orders Management</h1>
            <p>View and manage your orders</p>
        </div>

        <div class="orders-container">
            <div class="order-filters">
                <button class="filter-btn active" data-status="all">All Orders</button>
                <button class="filter-btn" data-status="pending">Pending Orders</button>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr class="<?php echo $order['is_read'] ? '' : 'unread-notification'; ?>">
                        <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                            <strong>Product:</strong> <?php echo htmlspecialchars($order['product_name']); ?><br>
                            <strong>Size:</strong> <?php echo htmlspecialchars($order['length_feet']); ?>' x 
                                         <?php echo htmlspecialchars($order['width_feet']); ?>' x 
                                         <?php echo htmlspecialchars($order['height_feet']); ?>'<br>
                            <strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']); ?> pieces
                        </td>
                        <td>
                            <strong>Buyer:</strong> <?php echo htmlspecialchars($order['firstname'] . ' ' . $order['lastname']); ?><br>
                            <strong>Contact:</strong> <?php echo htmlspecialchars($order['contactno']); ?><br>
                            <strong>Address:</strong> <?php echo htmlspecialchars($order['address']); ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="action-btn view-btn" onclick="viewOrder(<?php 
                                echo htmlspecialchars(json_encode([
                                    'reference_id' => $order['reference_id'],
                                    'product_name' => $order['product_name'],
                                    'length_feet' => $order['length_feet'],
                                    'width_feet' => $order['width_feet'],
                                    'height_feet' => $order['height_feet'],
                                    'quantity' => $order['quantity'],
                                    'buyer_name' => $order['firstname'] . ' ' . $order['lastname'],
                                    'contact' => $order['contactno'],
                                    'address' => $order['address']
                                ])); 
                            ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            
                            <?php
                            // Check if receipt exists for this order
                            $receiptStmt = $pdo->prepare("SELECT id FROM receipt WHERE order_id = ?");
                            $receiptStmt->execute([$order['reference_id']]);
                            if ($receiptStmt->fetch()): 
                            ?>
                            <button class="action-btn receipt-btn" onclick="viewReceipt(<?php echo $order['reference_id']; ?>)">
                                <i class="fas fa-file-invoice"></i> View Receipt
                            </button>
                            <?php
// Check if payment exists for this order
$paymentStmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$paymentStmt->execute([$order['reference_id']]);
if ($paymentStmt->fetch()): 
?>
<button class="action-btn payment-btn" onclick="viewPayment(<?php echo $order['reference_id']; ?>)">
    <i class="fas fa-money-bill-wave"></i> View Payment
</button>
<?php endif; ?>
                            <?php endif; ?>
                            <select class="status-select" onchange="updateStatus(<?php echo $order['reference_id']; ?>, this.value)"
                                    <?php echo ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') ? 'disabled' : ''; ?>>
                                <option value="">Update Status</option>
                                <option value="Processing">Processing</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <?php
                            // Check if order is tallied
                            $tallyStmt = $pdo->prepare("SELECT * FROM cus_orders WHERE order_id = ?");
                            $tallyStmt->execute([$order['reference_id']]);
                            if ($tallyStmt->fetch()): 
                            ?>
                            <button class="action-btn view-btn" onclick="viewTally(<?php echo $order['reference_id']; ?>)" style="background-color: #28a745;">
                                <i class="fas fa-receipt"></i> View Tally
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Order Modal -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Order Details</h2>
            <div id="orderDetails">
                <p><strong>Product Name:</strong> <span id="modalProductName"></span></p>
                <p><strong>Size:</strong> <span id="modalSize"></span></p>
                <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                <button id="checkInventoryBtn" class="action-btn view-btn" style="margin-top: 10px;">
                    <i class="fas fa-boxes"></i> Check Inventory
                </button>
                <p id="inventoryStatus" style="margin-top: 10px; font-weight: bold;"></p>
                <button id="tallyOrderBtn" class="action-btn view-btn" style="margin-top: 10px; display: none;">
                    <i class="fas fa-calculator"></i> Tally Order
                </button>
                <p id="tallyResult" style="margin-top: 10px; font-weight: bold;"></p>
                <hr style="margin: 15px 0;">
                <h3 style="margin-bottom: 10px;">Buyer Information</h3>
                <p><strong>Name:</strong> <span id="modalBuyerName"></span></p>
                <p><strong>Contact:</strong> <span id="modalContact"></span></p>
                <p><strong>Address:</strong> <span id="modalAddress"></span></p>
            </div>
        </div>
    </div>
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="receiptDetails"></div>
        </div>
    </div>

    <script>
        function viewPayment(orderId) {
    fetch('get_payment_details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('paymentModal');
            const paymentDetails = document.getElementById('paymentDetails');
            
            let proofImage = '';
            if (data.payment.proof_of_payment) {
                proofImage = `
                    <div class="payment-proof">
                        <h4>Payment Proof</h4>
                        <img src="${data.payment.proof_of_payment}" alt="Payment Proof">
                    </div>
                `;
            }
            
            paymentDetails.innerHTML = `
                <div class="payment-details">
                    <h3>Payment Details</h3>
                    <div class="payment-info">
                        <p><strong>Payment Method:</strong> ${data.payment.payment_method}</p>
                        <p><strong>Amount:</strong> ₱${parseFloat(data.payment.amount).toFixed(2)}</p>
                        <p><strong>Payment Date:</strong> ${new Date(data.payment.payment_date).toLocaleString()}</p>
                        <p><strong>Payment Status:</strong> ${data.payment.payment_status}</p>
                    </div>
                    ${proofImage}
                </div>
            `;
            modal.style.display = 'flex';
        } else {
            alert('Error loading payment details: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading payment details');
    });
}
        // Toggle Sidebar
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }

        // View Order Details
        function viewOrder(orderData) {
            const modal = document.getElementById('viewOrderModal');
            const productName = document.getElementById('modalProductName');
            const size = document.getElementById('modalSize');
            const quantity = document.getElementById('modalQuantity');
            const buyerName = document.getElementById('modalBuyerName');
            const contact = document.getElementById('modalContact');
            const address = document.getElementById('modalAddress');
            const checkInventoryBtn = document.getElementById('checkInventoryBtn');
            const inventoryStatus = document.getElementById('inventoryStatus');
            const tallyOrderBtn = document.getElementById('tallyOrderBtn');
            const tallyResult = document.getElementById('tallyResult');
        
            productName.textContent = orderData.product_name;
            size.textContent = `${orderData.length_feet}' x ${orderData.width_feet}' x ${orderData.height_feet}'`;
            quantity.textContent = `${orderData.quantity} pieces`;
            buyerName.textContent = orderData.buyer_name;
            contact.textContent = orderData.contact;
            address.textContent = orderData.address;
        
            // Add click event for inventory check
            checkInventoryBtn.onclick = function() {
                fetch('check_inventory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_name: orderData.product_name,
                        required_quantity: orderData.quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.available) {
                            inventoryStatus.style.color = '#155724';
                            inventoryStatus.textContent = `✓ Stock Available (${data.available_quantity} pieces in inventory)`;
                            tallyOrderBtn.style.display = 'inline-block'; // Show tally button
                        } else {
                            inventoryStatus.style.color = '#721C24';
                            inventoryStatus.textContent = `✗ Insufficient Stock (${data.available_quantity} pieces available, ${data.required_quantity} required)`;
                            tallyOrderBtn.style.display = 'none'; // Hide tally button
                        }
                    } else {
                        inventoryStatus.style.color = '#721C24';
                        inventoryStatus.textContent = 'Error checking inventory';
                        tallyOrderBtn.style.display = 'none';
                    }
                })
                .catch(error => {
                    inventoryStatus.style.color = '#721C24';
                    inventoryStatus.textContent = 'Error checking inventory';
                    tallyOrderBtn.style.display = 'none';
                });
            };
        
            // Add click event for tally order
            tallyOrderBtn.onclick = function() {
                fetch('tally_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_name: orderData.product_name,
                        length_feet: orderData.length_feet,
                        width_feet: orderData.width_feet,
                        height_feet: orderData.height_feet,
                        quantity: orderData.quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tallyResult.style.color = '#155724';
                        tallyResult.innerHTML = `
                            Price per piece: ₱${data.price_per_sqft.toFixed(2)}<br>
                            Total Price: ₱${data.total_price.toFixed(2)}
                            <button class="action-btn view-btn" style="margin-top: 10px;" onclick='saveTally(${JSON.stringify(orderData)}, ${data.price_per_sqft}, ${data.total_price})'>
                                <i class="fas fa-save"></i> Save Tally
                            </button>
                        `;
                    } else {
                        tallyResult.style.color = '#721C24';
                        tallyResult.textContent = 'Error calculating order total';
                    }
                })
                .catch(error => {
                    tallyResult.style.color = '#721C24';
                    tallyResult.textContent = 'Error calculating order total';
                });
            };
        
            modal.style.display = 'flex';
        }

        // Update Order Status
        function updateStatus(orderId, status) {
            if (!status) return;
            
            if (confirm(`Are you sure you want to update this order to ${status}?`)) {
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
                        alert('Status updated successfully');
                        location.reload();
                    } else {
                        alert('Error updating status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status');
                });
            }
        }

        // Filter Orders
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const status = this.dataset.status;
                const rows = document.querySelectorAll('.orders-table tbody tr');
                
                rows.forEach(row => {
                    const orderStatus = row.querySelector('.status-badge').textContent.toLowerCase();
                    if (status === 'all' || orderStatus.includes(status.toLowerCase())) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Close Modal
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', function() {
                this.closest('.modal').style.display = 'none';
            });
        });

        // Close Modal When Clicking Outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        // Close Sidebar When Clicking Outside (Mobile)
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
        function saveTally(orderData, price, totalAmount) {
            console.log('Saving tally with data:', {
                order_id: orderData.reference_id,
                product_name: orderData.product_name,
                length_feet: orderData.length_feet,
                width_feet: orderData.width_feet,
                height_feet: orderData.height_feet,
                quantity: orderData.quantity,
                price_per_sqft: price,
                total_amount: totalAmount
            });
        
            fetch('save_tally.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderData.reference_id,
                    product_name: orderData.product_name,
                    length_feet: orderData.length_feet,
                    width_feet: orderData.width_feet,
                    height_feet: orderData.height_feet,
                    quantity: orderData.quantity,
                    price_per_sqft: price,
                    total_amount: totalAmount
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order tally saved successfully!');
                    location.reload();
                } else {
                    alert('Error saving order tally: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving order tally: ' + error.message);
            });
        }
        function viewTally(orderId) {
            fetch('view_tally.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('viewOrderModal');
                    const tallyDetails = document.getElementById('orderDetails');
                    tallyDetails.innerHTML = `
                        <h3>Tally Details</h3>
                        <p><strong>Product Name:</strong> ${data.tally.product_name}</p>
                        <p><strong>Size:</strong> ${data.tally.length_feet}' x ${data.tally.width_feet}' x ${data.tally.height_feet}'</p>
                        <p><strong>Quantity:</strong> ${data.tally.quantity} pieces</p>
                        <p><strong>Price per piece:</strong> ₱${parseFloat(data.tally.price_per_sqft).toFixed(2)}</p>
                        <p><strong>Total Amount:</strong> ₱${parseFloat(data.tally.total_amount).toFixed(2)}</p>
                        <p><strong>Date Tallied:</strong> ${new Date(data.tally.created_at).toLocaleString()}</p>
                        <button class="create-receipt-btn" onclick="createReceipt({
                            reference_id: ${orderId},
                            product_name: '${data.tally.product_name}',
                            length_feet: ${data.tally.length_feet},
                            width_feet: ${data.tally.width_feet},
                            height_feet: ${data.tally.height_feet},
                            quantity: ${data.tally.quantity},
                            total_amount: ${data.tally.total_amount},
                            buyer_name: '${data.tally.buyer_name}'
                        })">
                            <i class="fas fa-file-invoice"></i> Create Receipt
                        </button>
                    `;
                    modal.style.display = 'flex';
                } else {
                    alert('Error loading tally details: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading tally details');
            });
        }
    function viewTallyDetails(order) {
        const modal = document.getElementById('orderDetailsModal');
        const modalContent = modal.querySelector('.modal-content');
        
        modalContent.innerHTML = `
            <span class="close-modal" onclick="closeOrderDetails()">&times;</span>
            <h3>Tally Details</h3>
            <div class="tally-details">
                <p><strong>Product Name:</strong> ${order.product_name}</p>
                <p><strong>Size:</strong> ${order.length_feet}' x ${order.width_feet}' x ${order.height_feet}'</p>
                <p><strong>Quantity:</strong> ${order.quantity} pieces</p>
                <p><strong>Price per piece:</strong> ₱${parseFloat(order.price_per_sqft).toFixed(2)}</p>
                <p><strong>Total Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)}</p>
                <p><strong>Date Tallied:</strong> ${new Date(order.created_at).toLocaleString()}</p>
            </div>
            <button class="create-receipt-btn" onclick="createReceipt(${JSON.stringify(order)})">
                <i class="fas fa-file-invoice"></i> Create Receipt
            </button>
        `;
        
        modal.style.display = 'flex';
    }
    
    function createReceipt(order) {
        const receiptNumber = 'RCP-' + Date.now();
        const currentDate = new Date().toISOString().slice(0, 19).replace('T', ' ');
        
        fetch('create_receipt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                receipt_number: receiptNumber,
                order_id: order.reference_id,
                product_name: order.product_name,
                size: `${order.length_feet}' x ${order.width_feet}' x ${order.height_feet}'`,
                quantity: order.quantity,
                total_amount: order.total_amount,
                buyer_name: order.buyer_name,
                receipt_date: currentDate
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Receipt created successfully!');
                location.reload();
            } else {
                alert('Error creating receipt: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating receipt');
        });
    }
    function viewReceipt(orderId) {
    fetch('get_receipt.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = document.getElementById('receiptModal');
            const receiptDetails = document.getElementById('receiptDetails');
            receiptDetails.innerHTML = `
    <div class="receipt-container">
        <div class="receipt-header">
            <h2>SALES RECEIPT</h2>
            <p class="receipt-number">Receipt No: ${data.receipt.receipt_number}</p>
            <p class="receipt-date">Date: ${new Date(data.receipt.receipt_date).toLocaleDateString()}</p>
        </div>
        <div class="receipt-info">
            <p><strong>Seller:</strong> ${data.receipt.seller_firstname} ${data.receipt.seller_lastname}</p>
            <p><strong>Buyer:</strong> ${data.receipt.buyer_name}</p>
        </div>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Qty</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>${data.receipt.quantity}</td>
                    <td>${data.receipt.product_name}<br>${data.receipt.size}</td>
                    <td>₱${parseFloat(data.receipt.total_amount/data.receipt.quantity).toFixed(2)}</td>
                    <td>₱${parseFloat(data.receipt.total_amount).toFixed(2)}</td>
                </tr>
            </tbody>
        </table>
        <div class="receipt-summary">
            <div class="receipt-totals">
                <p><span>Subtotal:</span> <span>₱${parseFloat(data.receipt.total_amount).toFixed(2)}</span></p>
                <p><span>Tax:</span> <span>₱0.00</span></p>
                <p class="total"><span>Total:</span> <span>₱${parseFloat(data.receipt.total_amount).toFixed(2)}</span></p>
            </div>
        </div>
    </div>
`;
            modal.style.display = 'flex';
        } else {
            alert('Error loading receipt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading receipt');
    });
}
    </script>
    
    <div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div id="paymentDetails"></div>
    </div>
</div>
</html>
