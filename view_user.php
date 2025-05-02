<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$user_id = isset($_GET['id']) ? $_GET['id'] : null;
$role = isset($_GET['role']) ? $_GET['role'] : null;

if (!$user_id || !$role) {
    header('Location: admin_accounts.php');
    exit();
}

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = ?");
$stmt->execute([$user_id, $role]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_accounts.php');
    exit();
}

// Fetch role-specific information
$roleData = [];
if ($role === 'Seller') {
    // Fetch inventory
    $stmt = $pdo->prepare("SELECT * FROM product_inventory WHERE seller_id = ?");
    $stmt->execute([$user_id]);
    $roleData['inventory'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch product catalog
    $stmt = $pdo->prepare("SELECT * FROM product_catalog WHERE seller_id = ?");
    $stmt->execute([$user_id]);
    $roleData['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch invoices
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE seller_id = ?");
    $stmt->execute([$user_id]);
    $roleData['invoices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch gcash details
    $stmt = $pdo->prepare("SELECT * FROM gcash WHERE seller_id = ?");
    $stmt->execute([$user_id]);
    $roleData['gcash'] = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - ElderWood Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-gray: #666666;
            --secondary-gray: #808080;
            --light-gray: #E8E8E8;
            --bg-white: #F5F5F5;
            --text-dark: #333333;
            --sidebar-width: 250px;
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
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            background-color: var(--bg-white);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background-color: white;
        }

        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--light-gray);
        }

        .users-table th {
            background-color: var(--bg-white);
            color: var(--text-dark);
            font-weight: bold;
        }

        .users-table tr:hover {
            background-color: var(--bg-white);
        }

        .data-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <a href="admin_accounts.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>

        <div class="user-details">
            <h2><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>'s Details</h2>
            <p>Role: <?php echo htmlspecialchars($user['role']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Contact: <?php echo htmlspecialchars($user['contactno']); ?></p>
            <p>Address: <?php echo htmlspecialchars($user['address']); ?></p>
        </div>

        <?php if ($role === 'Seller'): ?>
            <?php if (!empty($roleData['inventory'])): ?>
                <div class="data-section">
                    <h3>Inventory</h3>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Length (feet)</th>
                                <th>Width (feet)</th>
                                <th>Height (feet)</th>
                                <th>Quantity</th>
                                <th>Total Square Feet</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roleData['inventory'] as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['length_feet']); ?></td>
                                <td><?php echo htmlspecialchars($item['width_feet']); ?></td>
                                <td><?php echo htmlspecialchars($item['height_feet']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['total_square_feet']); ?></td>
                                <td>
                                    <?php if (!empty($item['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Product Image" style="max-width: 100px;">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($roleData['products'])): ?>
                <div class="data-section">
                    <h3>Product Catalog</h3>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Length (feet)</th>
                                <th>Width (feet)</th>
                                <th>Height (feet)</th>
                                <th>Price per Sq.ft</th>
                                <th>Quantity</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roleData['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['length_feet']); ?></td>
                                <td><?php echo htmlspecialchars($product['width_feet']); ?></td>
                                <td><?php echo htmlspecialchars($product['height_feet']); ?></td>
                                <td><?php echo htmlspecialchars($product['price_per_sqft']); ?></td>
                                <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                <td>
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="Product Image" style="max-width: 100px;">
                                    <?php else: ?>
                                        No image
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($roleData['gcash'])): ?>
                <div class="data-section">
                    <h3>GCash Details</h3>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Account Name</th>
                                <th>Account Number</th>
                                <th>QR Code</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo htmlspecialchars($roleData['gcash']['gcash_name']); ?></td>
                                <td><?php echo htmlspecialchars($roleData['gcash']['gcash_number']); ?></td>
                                <td>
                                    <?php if (!empty($roleData['gcash']['gcash_qr'])): ?>
                                        <img src="<?php echo htmlspecialchars($roleData['gcash']['gcash_qr']); ?>" alt="GCash QR" style="max-width: 150px;">
                                    <?php else: ?>
                                        No QR Code
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($roleData['gcash']['created_at']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>