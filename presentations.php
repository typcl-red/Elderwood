<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a Seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

// Get user details for sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();

// Get seller's unique ID
$stmt = $pdo->prepare("SELECT seller_unique_id FROM seller_ids WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$sellerID = $stmt->fetch();

// Get all presentations for this seller
// Update the SQL query
// Add this at the top of your PHP section where other queries are
$allSchedulesQuery = $pdo->prepare("
    SELECT ds.*, so.item_id, i.product_name, u.username as supplier_name,
           GROUP_CONCAT(DISTINCT CONCAT(inv.length, ' × ', inv.width, ' × ', inv.height, ' (', inv.quantity, ' pcs)') SEPARATOR ', ') as all_sizes
    FROM delivery_schedules ds
    JOIN sup_orders so ON ds.presentation_id = so.id
    JOIN inventory i ON so.item_id = i.id
    JOIN users u ON i.supplier_id = u.id
    LEFT JOIN inventory inv ON i.supplier_id = inv.supplier_id AND i.product_name = inv.product_name
    GROUP BY ds.id
");
$allSchedulesQuery->execute();
$allDeliverySchedules = $allSchedulesQuery->fetchAll(PDO::FETCH_ASSOC);
// Update the SQL query
$stmt = $pdo->prepare("
    SELECT so.*, i.product_name, i.description, i.length, i.width, i.height, i.quantity,
           u.username as supplier_name, u.contactno as supplier_contact, u.profile_photo as supplier_photo,
           GROUP_CONCAT(DISTINCT CONCAT(inv.length, ' × ', inv.width, ' × ', inv.height, ' (', inv.quantity, ' pcs)') SEPARATOR ', ') as all_sizes,
           COALESCE(ds.status, so.status) as status, ds.scheduled_date
    FROM sup_orders so
    JOIN inventory i ON so.item_id = i.id
    JOIN users u ON i.supplier_id = u.id
    LEFT JOIN inventory inv ON i.supplier_id = inv.supplier_id AND i.product_name = inv.product_name
    LEFT JOIN delivery_schedules ds ON so.id = ds.presentation_id
    WHERE so.seller_id = ?
    GROUP BY so.id
    ORDER BY so.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$presentations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Presentations - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            --sidebar-width: 250px;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-brown);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-brown);
            color: white;
            padding: 20px 0;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
        }

        .profile-section {
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid var(--light-brown);
            margin-bottom: 20px;
            position: relative;
        }

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            overflow: hidden;
            background-color: white;
        }

        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-details {
            margin-bottom: 15px;
        }

        .profile-details h3 {
            margin-bottom: 5px;
            font-size: 1.2rem;
        }

        .profile-details p {
            font-size: 0.9rem;
            color: var(--light-brown);
            margin: 5px 0;
        }

        .profile-details p strong {
            color: var(--light-brown);
            margin-right: 5px;
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: var(--secondary-brown);
        }
        
        .nav-links a.active {
            background-color: var(--secondary-brown);
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }
        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .nav-links a:hover {
            background-color: var(--secondary-brown);
        }

        .nav-links i {
            width: 30px;
            font-size: 1.2rem;
        }

        .logout-btn {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 20px;
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            background-color: var(--secondary-brown);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn a:hover {
            background-color: #8B0000;
        }

        .logout-btn i {
            margin-right: 10px;
        }

        .container {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            background-color: var(--bg-brown);
        }

        .presentations-section {
            margin-bottom: 40px;
        }

        .presentation-card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .supplier-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .supplier-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-brown);
        }

        .size-tag {
            display: inline-block;
            background-color: #f0f0f0;
            color: var(--primary-brown);
            padding: 5px 10px;
            border-radius: 4px;
            margin: 3px;
            font-size: 0.9em;
        }

        .declined-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid var(--light-brown);
        }

        .presentations-section {
            margin-bottom: 40px;
        }

        .presentations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .presentation-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
        }

        .declined-section {
            margin-top: auto;
            border-top: 2px solid var(--light-brown);
            padding-top: 30px;
        }
        .action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9em;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s ease;
}

.btn-schedule {
    background-color: var(--primary-brown);
    color: white;
}

.btn-schedule:hover {
    background-color: var(--secondary-brown);
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(139, 69, 19, 0.2);
}

.btn-decline {
    background-color: #dc3545;
    color: white;
}

.btn-decline:hover {
    background-color: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
}

.btn i {
    font-size: 1em;
}
.status-badge.pending {
    background-color: #fff3e0;
    color: #e65100;
}
.status-badge.arrived {
        background-color: #e8f5e9;
        color: #2e7d32;
        margin-top: 2px;
    }
    .status-badge.scheduled {
        background-color: #fff3cd;
        color: #856404;
    }
    .btn-categorize {
    background-color: #28a745;
    color: white;
}

.btn-categorize:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}
    </style>
</head>
<body>
    <!-- Add Sidebar -->
    <div class="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
                <p><i class="fas fa-id-badge"></i> Seller ID: <?php echo htmlspecialchars($sellerID['seller_unique_id']); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="seller_dashboard.php"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="my_products.php"><i class="fas fa-box"></i><span>My Products</span></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i><span>Orders</span></a></li>
            <li><a href="daily_sales.php"><i class="fas fa-chart-line"></i><span>Daily Sales</span></a></li>
            <li><a href="inventory.php"><i class="fas fa-warehouse"></i><span>Product Inventory</span></a></li>
            <li><a href="presentations.php" class="active"><i class="fas fa-box-open"></i><span>Presentations</span></a></li>
            <li><a href="gcash_settings.php"><i class="fas fa-money-bill"></i><span>GCash Settings</span></a></li>
        </ul>

        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <h1>Product Presentations</h1>
        </div>

        <?php if (empty($presentations)): ?>
            <div class="no-presentations">
                <i class="fas fa-inbox fa-3x"></i>
                <p>No presentations received yet.</p>
            </div>
        <?php else: ?>
            <!-- Active Presentations -->
            <!-- Add after the opening of presentations-section div and before Scheduled Presentations -->
            <div class="presentations-section">
                <h2>Pending Presentations</h2>
                <div class="presentations-grid">
                    <?php foreach ($presentations as $presentation): ?>
                        <?php if ($presentation['status'] === 'Pending'): ?>
                            <div class="presentation-card">
                                <div class="presentation-header">
                                    <div class="presentation-date">
                                        Presented on: <?php echo date('F j, Y, g:i a', strtotime($presentation['created_at'])); ?>
                                    </div>
                                    <div class="status-badge pending">
                                        <i class="fas fa-clock"></i> Pending
                                    </div>
                                </div>
                                <div class="product-info">
                                    <div class="supplier-info">
                                        <img src="<?php 
                                            echo !empty($presentation['supplier_photo']) && file_exists($presentation['supplier_photo']) 
                                                ? htmlspecialchars($presentation['supplier_photo']) 
                                                : 'assets/images/default-profile.jpg'; 
                                            ?>" 
                                            alt="Supplier Photo" 
                                            class="supplier-photo"
                                            onerror="this.src='assets/images/default-profile.jpg'">
                                        <div>
                                            <p><i class="fas fa-user"></i> Supplier: <?php echo htmlspecialchars($presentation['supplier_name']); ?></p>
                                            <p><i class="fas fa-phone"></i> Contact: <?php echo htmlspecialchars($presentation['supplier_contact']); ?></p>
                                        </div>
                                    </div>
                                    <p><strong>Product name:</strong> <?php echo htmlspecialchars($presentation['product_name']); ?></p>
                                    <p><strong>Available Sizes:</strong></p>
                                    <div class="sizes-wrapper">
                                        <?php 
                                        if (!empty($presentation['all_sizes'])) {
                                            $sizes = explode(', ', $presentation['all_sizes']);
                                            foreach ($sizes as $size) {
                                                $size = preg_replace('/\.00/', '', $size);
                                                echo '<span class="size-tag">' . htmlspecialchars($size) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($presentation['description']); ?></p>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-schedule" onclick="setSchedule(<?php echo $presentation['id']; ?>)">
                                        <i class="fas fa-calendar-alt"></i> Set Schedule
                                    </button>
                                    <button class="btn btn-decline" onclick="declinePresentation(<?php echo $presentation['id']; ?>)">
                                        <i class="fas fa-times"></i> Decline
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Scheduled Presentations -->
            <div class="presentations-section">
            <h2>Scheduled Presentations</h2>
    <div class="presentations-grid">
        <?php foreach ($presentations as $presentation): ?>
            <?php if (in_array($presentation['status'], ['Scheduled', 'On The Way', 'Arrived'])): ?>
                <div class="presentation-card">
                    <div class="presentation-header">
                        <div class="presentation-date">
                            Presented on: <?php echo date('F j, Y, g:i a', strtotime($presentation['created_at'])); ?>
                        </div>
                        <div class="status-badge <?php echo str_replace(' ', '-', strtolower($presentation['status'])); ?>">
                            <i class="<?php 
                                if ($presentation['status'] === 'Scheduled') echo 'fas fa-calendar-check';
                                elseif ($presentation['status'] === 'On The Way') echo 'fas fa-truck';
                                else echo 'fas fa-check-circle';
                            ?>"></i>
                            <?php echo $presentation['status']; ?>
                        </div>
                                </div>
                                <div class="product-info">
                                    <div class="supplier-info">
                                        <img src="<?php 
                                            echo !empty($presentation['supplier_photo']) && file_exists($presentation['supplier_photo']) 
                                                ? htmlspecialchars($presentation['supplier_photo']) 
                                                : 'assets/images/default-profile.jpg'; 
                                            ?>" 
                                            alt="Supplier Photo" 
                                            class="supplier-photo"
                                            onerror="this.src='assets/images/default-profile.jpg'">
                                        <div>
                                            <p><i class="fas fa-user"></i> Supplier: <?php echo htmlspecialchars($presentation['supplier_name']); ?></p>
                                            <p><i class="fas fa-phone"></i> Contact: <?php echo htmlspecialchars($presentation['supplier_contact']); ?></p>
                                        </div>
                                    </div>
                                    <p><strong>Product name:</strong> <?php echo htmlspecialchars($presentation['product_name']); ?></p>
                                </div>
                                <p><strong>Available Sizes:</strong> 
                                    <?php 
                                    if (!empty($presentation['all_sizes'])) {
                                        $sizes = explode(', ', $presentation['all_sizes']);
                                        foreach ($sizes as $size) {
                                            // Remove .00 from dimensions
                                            $size = preg_replace('/\.00/', '', $size);
                                            echo '<span class="size-tag">' . htmlspecialchars($size) . '</span>';
                                        }
                                    }
                                    ?>
                                </p>
                                <p><strong>Description:</strong> <?php echo htmlspecialchars($presentation['description']); ?></p>
                                <div class="schedule-info" onclick="viewSchedule(
                                    '<?php echo date('F j, Y g:i A', strtotime($presentation['scheduled_date'])); ?>', 
                                    '<?php echo htmlspecialchars($presentation['supplier_name']); ?>', 
                                    '<?php echo htmlspecialchars($presentation['product_name']); ?>', 
                                    '<?php echo htmlspecialchars($presentation['all_sizes']); ?>'
                                )" style="cursor: pointer;">
                                    <i class="fas fa-calendar-check"></i> 
                                    <span>Delivery Scheduled</span>
                                </div>
                                <?php if ($presentation['status'] === 'Arrived'): ?>
                    <div class="action-buttons">
                        <button class="btn btn-categorize" onclick="window.location.href='categorize_product.php?id=<?php echo $presentation['id']; ?>&item_id=<?php echo $presentation['item_id']; ?>'">
                            <i class="fas fa-tags"></i> Categorize Product
                        </button>
                    </div>
                <?php endif; ?>
</div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Declined Presentations -->
            <?php if (array_filter($presentations, function($p) { return $p['status'] === 'Declined'; })): ?>
                <div class="presentations-section declined-section">
                    <h2>Declined Presentations</h2>
                    <div class="presentations-grid">
                        <?php foreach ($presentations as $presentation): ?>
                            <?php if ($presentation['status'] === 'Declined'): ?>
                                <div class="presentation-card declined">
    <div class="presentation-header">
        <div class="presentation-date">
            Presented on: <?php echo date('F j, Y, g:i a', strtotime($presentation['created_at'])); ?>
        </div>
        <div class="status-badge declined">
            <i class="fas fa-times-circle"></i> Declined
        </div>
    </div>
    <div class="product-info">
        <div class="supplier-info">
            <img src="<?php 
                echo !empty($presentation['supplier_photo']) && file_exists($presentation['supplier_photo']) 
                    ? htmlspecialchars($presentation['supplier_photo']) 
                    : 'assets/images/default-profile.jpg'; 
                ?>" 
                alt="Supplier Photo" 
                class="supplier-photo"
                onerror="this.src='assets/images/default-profile.jpg'">
            <div>
                <p><i class="fas fa-user"></i> Supplier: <?php echo htmlspecialchars($presentation['supplier_name']); ?></p>
                <p><i class="fas fa-phone"></i> Contact: <?php echo htmlspecialchars($presentation['supplier_contact']); ?></p>
            </div>
        </div>
        <p><strong>Product name:</strong> <?php echo htmlspecialchars($presentation['product_name']); ?></p>
    </div>
                                    <p><strong>Available Sizes:</strong> 
                                        <?php 
                                        if (!empty($presentation['all_sizes'])) {
                                            $sizes = explode(', ', $presentation['all_sizes']);
                                            foreach ($sizes as $size) {
                                                $size = preg_replace('/\.00/', '', $size);
                                                echo '<span class="size-tag">' . htmlspecialchars($size) . '</span>';
                                            }
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($presentation['description']); ?></p>
                                    <!-- Removed action buttons for declined presentations -->
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<!-- Add this before closing body tag -->
    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Set Delivery Schedule</h2>
            <form id="scheduleForm">
                <input type="hidden" id="presentationId" name="presentationId">
                <div class="form-group">
                    <label for="scheduleDate">Select Date:</label>
                    <input type="date" id="scheduleDate" name="scheduleDate" required>
                </div>
                <div class="form-group">
                    <div class="time-labels">
                        <span>Hour</span>
                        <span>Minute</span>
                        <span>AM/PM</span>
                    </div>
                    <div class="time-inputs">
                        <select name="hour" required>
                            <?php for($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="minute" required>
                            <?php for($i = 1; $i <= 60; $i++): ?>
                                <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="ampm" required>
                            <option value="AM">AM</option>
                            <option value="PM">PM</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-schedule">Confirm Schedule</button>
            </form>
        </div>
    </div>
    <style>
    /* Add these styles after your existing root variables */
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

    .sidebar {
        transition: transform 0.3s ease;
    }

    @media screen and (max-width: 768px) {
        .burger-menu {
            display: block;
        }

        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .container {
            margin-left: 0;
            padding-top: 60px;
        }
    }
</style>
<button class="burger-menu" id="burgerMenu">
    <i class="fas fa-bars"></i>
</button>

<!-- Add this script before closing body tag -->
<script>
    const burgerMenu = document.getElementById('burgerMenu');
    const sidebar = document.querySelector('.sidebar');
    const container = document.querySelector('.container');

    burgerMenu.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !burgerMenu.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
        }
    });
</script>

    <style>
        /* Add these styles */
        .time-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 0 5px;
        }
        
        .time-labels span {
            flex: 1;
            text-align: center;
            font-weight: bold;
            color: var(--primary-brown);
            font-size: 0.9em;
        }

        .time-inputs {
            display: flex;
            gap: 10px;
        }

        .time-inputs select {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .presentation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .status-badge {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.9em;
    font-weight: bold;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}

.status-badge.on-the-way {
    background-color: #e3f2fd;
    color: #1976d2;
    margin-top: 2px;
}

.status-badge i {
    margin-right: 5px;
}

        .schedule-info {
            margin-top: 15px;
            padding: 10px;
            background-color: #e8f5e9;
            border-radius: 4px;
            color: #2e7d32;
            text-align: center;
            font-weight: bold;
        }
        
        .schedule-info i {
            margin-right: 8px;
        }
        .status-badge.declined {
    background-color: #ffebee;
    color: #c62828;
}

    </style>

    <style>
        /* Modal Styles */
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

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            color: #aaa;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            padding: 5px;
        }

        .close:hover {
            color: var(--primary-brown);
        }

        .modal h2 {
            margin-top: 10px;
            padding-right: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-brown);
        }

        .form-group strong {
            color: var(--primary-brown);
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>

    <script>
        // Add this to your existing script
        function setSchedule(id) {
            document.getElementById('presentationId').value = id;
            document.getElementById('scheduleModal').style.display = 'block';
        }

        // Close modal when clicking the X
        document.querySelector('.close').onclick = function() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('scheduleModal')) {
                document.getElementById('scheduleModal').style.display = 'none';
            }
        }

        // Handle form submission
        document.getElementById('scheduleForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('set_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Schedule set successfully');
                    location.reload();
                } else {
                    alert('Error setting schedule: ' + data.message);
                }
            });
        }
        function viewSchedule(dateTime, supplierName, productName, sizes) {
            const date = new Date(dateTime);
            const monthNames = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
            
            // Get all schedules from PHP
            const allSchedules = <?php echo json_encode($allDeliverySchedules); ?>;
            
            // Update month display
            document.getElementById('scheduleMonth').textContent = `${monthNames[date.getMonth()]} ${date.getDate()}`;
            
            // Generate calendar grid
            const calendarGrid = document.querySelector('.calendar-grid');
            calendarGrid.innerHTML = '';
            
            // Get first day of month and total days
            const firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            let currentDay = new Date(firstDay);
            
            // Adjust to start from Monday
            currentDay.setDate(currentDay.getDate() - (firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1));
            
            // Generate calendar days
            for (let i = 0; i < 42; i++) {
                const dayDiv = document.createElement('div');
                dayDiv.className = 'calendar-day';
                
                // Format the current date to match database format (YYYY-MM-DD)
                const year = currentDay.getFullYear();
                const month = String(currentDay.getMonth() + 1).padStart(2, '0');
                const day = String(currentDay.getDate()).padStart(2, '0');
                const currentDate = `${year}-${month}-${day}`;
                
                // Check if there are any schedules for this day
                const daySchedules = allSchedules.filter(schedule => {
                    const scheduleDate = schedule.scheduled_date.split(' ')[0];
                    return scheduleDate === currentDate;
                });
                
                if (daySchedules.length > 0) {
                    dayDiv.classList.add('has-schedule');
                    dayDiv.style.backgroundColor = 'var(--primary-brown)'; // Changed from '#f0f4ff' to brown
                    dayDiv.style.color = 'white'; // Added to ensure text is visible on brown background
                    dayDiv.setAttribute('data-schedules', JSON.stringify(daySchedules));
                    dayDiv.onclick = function() {
                        showScheduleDetails(daySchedules);
                    };
                }
                
                if (currentDay.getMonth() !== date.getMonth()) {
                    dayDiv.classList.add('other-month');
                }
                if (currentDay.getDate() === date.getDate() && 
                    currentDay.getMonth() === date.getMonth()) {
                    dayDiv.classList.add('current');
                }
                
                dayDiv.textContent = currentDay.getDate();
                calendarGrid.appendChild(dayDiv);
                currentDay.setDate(currentDay.getDate() + 1);
            }
        
            // Show initial details
            const initialSchedule = allSchedules.find(schedule => 
                schedule.scheduled_date.split(' ')[0] === date.toISOString().split('T')[0]
            );
            if (initialSchedule) {
                showScheduleDetails([initialSchedule]);
            } else {
                // Show the clicked presentation details
                document.getElementById('supplierName').textContent = supplierName;
                document.getElementById('productName').textContent = productName;
                document.getElementById('productSizes').textContent = sizes.replace(/\.00/g, '');
            }
            
            document.getElementById('viewScheduleModal').style.display = 'block';
        }

        function showScheduleDetails(schedules) {
            const detailsContainer = document.querySelector('.delivery-details');
            let html = '';
            
            schedules.forEach(schedule => {
                const scheduleTime = new Date(schedule.scheduled_date).toLocaleTimeString();
                html += `
                    <div class="schedule-item">
                        <p><strong>Time:</strong> ${scheduleTime}</p>
                        <p><strong>Supplier:</strong> ${schedule.supplier_name}</p>
                        <p><strong>Product:</strong> ${schedule.product_name}</p>
                        <p><strong>Sizes:</strong> ${schedule.all_sizes.replace(/\.00/g, '')}</p>
                    </div>
                `;
            });
            
            detailsContainer.innerHTML = html;
        }

        // Update window.onclick to handle both modals
        window.onclick = function(event) {
            if (event.target == document.getElementById('scheduleModal')) {
                document.getElementById('scheduleModal').style.display = 'none';
            }
            if (event.target == document.getElementById('viewScheduleModal')) {
                document.getElementById('viewScheduleModal').style.display = 'none';
            }
        }

        // Add close button functionality for view schedule modal
        document.querySelectorAll('.close').forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                this.closest('.modal').style.display = 'none';
            }
        });
    </script>

    <!-- Schedule View Modal -->
    <div id="viewScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeViewScheduleModal()">&times;</span>
            <h2>Delivery Schedule Details</h2>
            <div class="schedule-details">
                <div class="calendar-header">
                    <span id="scheduleMonth"></span>
                </div>
                <div class="calendar-container">
                    <div class="weekdays">
                        <div>MON</div>
                        <div>TUE</div>
                        <div>WED</div>
                        <div>THU</div>
                        <div>FRI</div>
                        <div>SAT</div>
                        <div>SUN</div>
                    </div>
                    <div class="calendar-grid">
                        <!-- Calendar days will be populated by JavaScript -->
                    </div>
                </div>
                <div class="delivery-details">
                    <p><strong>Supplier:</strong> <span id="supplierName"></span></p>
                    <p><strong>Product:</strong> <span id="productName"></span></p>
                    <p><strong>Sizes:</strong> <span id="productSizes"></span></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .modal-content {
            max-width: 400px;
            padding: 25px;
            margin: 2% auto; /* Changed from 15% to 5% */
            transform: translateY(-20px); /* Added to move up slightly */
        }

        .calendar-header {
            text-align: left;
            padding: 10px 0;
            margin-bottom: 15px;
        }

        #scheduleMonth {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .calendar-container {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
        }

        .weekdays div {
            text-align: center;
            padding: 8px 0;
            font-size: 0.8em;
            color: #666;
            font-weight: 500;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #f8f9fa;
            padding: 5px;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9em;
            color: #333;
            cursor: default;
            width: 30px; /* Made smaller */
            height: 30px; /* Made smaller */
            margin: 2px;
        }

        .calendar-day.has-schedule {
            background-color: var(--primary-brown);
            color: white;
            cursor: pointer;
            border-radius: 50%;
            transform: scale(0.8); /* Makes the circle smaller */
        }

        .calendar-day.has-schedule:hover {
            background-color: var(--secondary-brown);
            transform: scale(0.9); /* Slightly larger on hover */
            transition: all 0.2s ease;
        }

        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: #f8f9fa;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-weight: 500;
            color: #666;
        }

        .weekdays div {
            text-align: center;
            font-size: 0.8em;
        }

        .calendar-day.other-month {
            color: #ccc;
        }

        .calendar-day.current {
            background-color: var(--primary-brown);
            color: white;
            cursor: pointer;
            position: relative;
        }

        .calendar-day.has-schedule:hover {
            background-color: var(--secondary-brown);
        }

        .delivery-details {
            margin-top: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delivery-details p {
            margin: 8px 0;
            font-size: 0.95em;
        }

        .delivery-details strong {
            color: #666;
            margin-right: 8px;
        }
    </style>

    <script>
        function closeViewScheduleModal() {
            document.getElementById('viewScheduleModal').style.display = 'none';
        }
    </script>
</body>
</html>