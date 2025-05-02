<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    header('Location: login.php');
    exit();
}

// Get supplier's details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Dashboard - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: var(--primary-brown);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: var(--secondary-brown);
            font-size: 1.1rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: var(--primary-brown);
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 1.5rem;
            color: var(--secondary-brown);
            font-weight: bold;
        }

        .quick-actions {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .quick-actions h2 {
            color: var(--primary-brown);
            margin-bottom: 20px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            padding: 15px;
            border: none;
            border-radius: 8px;
            background-color: var(--light-brown);
            color: var(--primary-brown);
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .action-btn:hover {
            background-color: var(--primary-brown);
            color: white;
            transform: translateY(-2px);
        }

        .action-btn i {
            font-size: 1.2rem;
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/supplier_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($userDetails['firstname']); ?>!</h1>
            <p>Manage your lumber supply operations</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <p>0</p>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <button class="action-btn" onclick="window.location.href='supplier_products.php'">
                    <i class="fas fa-box"></i> Manage Products
                </button>
                <button class="action-btn" onclick="window.location.href='supplier_orders.php'">
                    <i class="fas fa-shopping-cart"></i> View Orders
                </button>
                <button class="action-btn" onclick="window.location.href='supplier_inventory.php'">
                    <i class="fas fa-warehouse"></i> Inventory
                </button>
            </div>
        </div>
    </div>
</body>
</html>