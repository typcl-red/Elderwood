<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get total counts for each user type
$stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users WHERE role IN ('Buyer', 'Seller', 'Supplier') GROUP BY role");
$stmt->execute();
$counts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $counts[$row['role']] = $row['count'];
}

// Set default values if no users found
$totalBuyers = isset($counts['Buyer']) ? $counts['Buyer'] : 0;
$totalSellers = isset($counts['Seller']) ? $counts['Seller'] : 0;
$totalSuppliers = isset($counts['Supplier']) ? $counts['Supplier'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-gray: #666666;
            --secondary-gray: #808080;
            --light-gray: #E8E8E8;
            --bg-white: #F5F5F5;
            --text-dark: #333333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-white);
            display: flex;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            padding: 20px;
            position: fixed;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .admin-profile {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .admin-info h3 {
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .admin-info p {
            color: var(--secondary-gray);
        }

        .nav-links {
            list-style: none;
            margin-top: 30px;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover, .nav-links a.active {
            background-color: var(--light-gray);
        }

        .nav-links i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .logout-section {
            position: absolute;
            bottom: 20px;
            width: calc(100% - 40px);
        }

        .logout-section a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #ff4444;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-section a:hover {
            background-color: #ffeeee;
        }

        .logout-section i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .stat-card p {
            font-size: 24px;
            color: var(--primary-gray);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Buyers</h3>
                <p><?php echo $totalBuyers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Sellers</h3>
                <p><?php echo $totalSellers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Suppliers</h3>
                <p><?php echo $totalSuppliers; ?></p>
            </div>
        </div>
    </div>
</body>
</html>