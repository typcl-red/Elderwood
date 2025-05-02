<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a laborer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Laborer') {
    header('Location: login.php');
    exit();
}

// Get laborer's details including employer info
$stmt = $pdo->prepare("
    SELECT u.*, s.seller_unique_id as employer_unique_id, 
           e.firstname as employer_firstname, e.lastname as employer_lastname,
           e.id as employer_id
    FROM users u 
    LEFT JOIN seller_ids s ON u.employer_id = s.user_id 
    LEFT JOIN users e ON u.employer_id = e.id
    WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();

// Fetch orders from the employer
$stmt = $pdo->prepare("
    SELECT 
        ol.*,
        u.firstname as buyer_firstname,
        u.lastname as buyer_lastname,
        u.contactno as buyer_contact,
        u.address as buyer_address,
        n.is_read,
        n.id as notification_id
    FROM notifications n
    JOIN order_list ol ON n.reference_id = ol.order_id
    JOIN users u ON ol.buyer_id = u.id
    WHERE n.user_id = ? 
    AND n.type = 'order'
    ORDER BY ol.created_at DESC");
$stmt->execute([$userDetails['employer_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Include your existing CSS styles */
        .tasks-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 20px;
        }

        .task-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 8px 16px;
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

        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .tasks-table th,
        .tasks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .tasks-table th {
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

        .status-completed {
            background-color: #D4EDDA;
            color: #155724;
        }

        /* Add this specific class for Product Received */
        .status-product-received {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .task-details {
            margin-top: 5px;
            font-size: 0.9em;
        }

        .buyer-info {
            margin-top: 5px;
            color: #666;
        }
    </style>
    <style>
        .unread-notification {
            background-color: #fff3e0;
        }
        
        .notification-dot {
            width: 8px;
            height: 8px;
            background-color: #ff4444;
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }
        
    </style>
</head>
<body>
    <?php include 'includes/laborer_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>My Tasks</h1>
            <p>View and manage your assigned tasks</p>
        </div>

        <div class="tasks-container">
            <div class="task-filters">
                <button class="filter-btn active" data-status="all">All Tasks</button>
                <button class="filter-btn" data-status="pending">Pending</button>
                <button class="filter-btn" data-status="processing">Processing</button>
                <button class="filter-btn" data-status="completed">Completed</button>
            </div>

            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>Product Details</th>
                        <th>Customer Information</th>
                        <th>Status</th>
                        <th>Action</th>  <!-- New column -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="<?php echo !$task['is_read'] ? 'unread-notification' : ''; ?>">
                            <td>
                                <?php echo date('M d, Y h:i A', strtotime($task['created_at'])); ?>
                                <?php if (!$task['is_read']): ?>
                                    <span class="notification-dot" title="New Order"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="task-details">
                                    <strong>Product:</strong> <?php echo htmlspecialchars($task['product_name']); ?><br>
                                    <strong>Size:</strong> <?php echo htmlspecialchars($task['length_feet']); ?>' x 
                                             <?php echo htmlspecialchars($task['width_feet']); ?>' x 
                                             <?php echo htmlspecialchars($task['height_feet']); ?>'<br>
                                    <strong>Quantity:</strong> <?php echo htmlspecialchars($task['quantity']); ?> pieces
                                </div>
                            </td>
                            <td>
                                <div class="buyer-info">
                                    <strong>Customer:</strong> 
                                    <?php echo htmlspecialchars($task['buyer_firstname'] . ' ' . $task['buyer_lastname']); ?><br>
                                    <strong>Contact:</strong> 
                                    <?php echo htmlspecialchars($task['buyer_contact']); ?><br>
                                    <strong>Address:</strong> 
                                    <?php echo htmlspecialchars($task['buyer_address']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                                    <?php echo htmlspecialchars($task['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task['status'] === 'Processing' || $task['status'] === 'Payment Pending' || $task['status'] === 'Pickup Pending'): ?>
                                    <button class="done-btn" data-order-id="<?php echo $task['order_id']; ?>">Done</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Filter tasks based on status
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                const status = this.dataset.status;
                const rows = document.querySelectorAll('.tasks-table tbody tr');

                rows.forEach(row => {
                    const rowStatus = row.querySelector('.status-badge').textContent.toLowerCase();
                    if (status === 'all' || rowStatus === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>

<head>
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-brown);
            display: flex;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-brown);
        }

        .profile-details {
            padding: 10px;
        }

        .profile-details h3 {
            color: var(--primary-brown);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .profile-details p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-details i {
            margin-right: 8px;
            color: var(--primary-brown);
        }

        .nav-links {
            list-style: none;
            margin-bottom: 30px;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--primary-brown);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: var(--bg-brown);
            transform: translateX(5px);
        }

        .nav-links i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .logout-btn {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--light-brown);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #d32f2f;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn a:hover {
            background-color: #ffebee;
            transform: translateX(5px);
        }

        .logout-btn i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .dashboard-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-brown);
        }

        .profile-image {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background-color: var(--light-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .change-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-brown);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .change-photo-btn:hover {
            background: var(--secondary-brown);
        }

        .profile-upload-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .profile-upload-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #profilePreview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px auto;
            display: block;
        }

        .profile-image i {
            font-size: 50px;
            color: white;
        }

        .profile-details h3 {
            color: var(--primary-brown);
            margin-bottom: 5px;
        }

        .profile-details p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: var(--primary-brown);
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: var(--bg-brown);
        }

        .nav-links i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
        

        .dashboard-headertop: 1px solid var(--light-brown);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: #d32f2f;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .logout-btn a:hover {
            background-color: #ffebee;
        }

        .logout-btn i {
            margin-right: 10px;
        }
    </style>
</head>

<script>
    document.querySelectorAll('.done-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const orderId = this.dataset.orderId;
            const laborerId = <?php echo $_SESSION['user_id']; ?>; // Get laborer's ID from session
            try {
                const response = await fetch('update_status_laborer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}&status=Ready for Pick Up&laborer_id=${laborerId}`
                });

                if (response.ok) {
                    // Update the status badge
                    const statusBadge = this.closest('tr').querySelector('.status-badge');
                    statusBadge.textContent = 'Ready for Pick Up';
                    statusBadge.className = 'status-badge status-ready';
                    // Remove the done button
                    this.remove();
                    // Refresh the page to update the filter
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    });
</script>

<style>
    .done-btn {
        padding: 8px 16px;
        background-color: var(--primary-brown);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .done-btn:hover {
        background-color: var(--secondary-brown);
    }

    .status-ready {
        background-color: #E1BEE7;
        color: #6A1B9A;
    }
</style>
