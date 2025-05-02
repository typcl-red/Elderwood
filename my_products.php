<?php
require_once 'includes/session.php';
require_once 'database/config.php';
require_once 'classes/User.php';

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

$seller_id = $_SESSION['user_id'];

// Get user details
$user = new User($pdo);
$userDetails = $user->getUserById($seller_id);

// Get seller's products from catalog with inventory details
$stmt = $pdo->prepare("
    SELECT 
        c.product_name,
        c.price_per_sqft,
        c.catalog_id,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                i.length_feet, 'x', 
                i.width_feet, 'x',
                i.height_feet
            )
            ORDER BY i.length_feet, i.width_feet, i.height_feet
            SEPARATOR ', '
        ) as dimensions,
        SUM(i.quantity) as total_quantity,
        MAX(i.image_path) as image_path
    FROM product_catalog c
    LEFT JOIN product_inventory i ON 
        c.product_name = i.product_name AND 
        c.seller_id = i.seller_id
    WHERE c.seller_id = ?
    GROUP BY c.product_name, c.price_per_sqft, c.catalog_id
    ORDER BY c.created_at DESC
");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();

// Add this near the top of the file after the database connection
function debugLog($message) {
    error_log(print_r($message, true));
}

// Modify the process_add_product.php endpoint to include better error handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the form data
        $productName = $_POST['product_name'] ?? '';
        $sellerId = $_SESSION['user_id'];
        
        // Debug log the incoming data
        debugLog("Adding product: " . $productName . " for seller: " . $sellerId);
        
        // First check if product already exists in catalog
        $checkStmt = $pdo->prepare("SELECT catalog_id FROM product_catalog WHERE product_name = ? AND seller_id = ?");
        $checkStmt->execute([$productName, $sellerId]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Product already exists in your catalog']);
            exit;
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Insert into product_catalog
        $catalogStmt = $pdo->prepare("
            INSERT INTO product_catalog (product_name, seller_id, created_at)
            VALUES (?, ?, NOW())
        ");
        
        $result = $catalogStmt->execute([$productName, $sellerId]);
        
        if (!$result) {
            throw new Exception("Failed to insert into product_catalog");
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        debugLog("Error adding product: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            --sidebar-width: 250px;
            --primary-brown-rgb: 139, 69, 19; /* Adjust these values to match your theme color */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--primary-brown);
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            overflow-y: auto;
        }

        .profile-section {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--light-brown);
            position: relative;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            background-color: var(--light-brown);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-image i {
            font-size: 40px;
            color: var(--primary-brown);
        }

        .profile-details h3 {
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .profile-details p {
            margin: 5px 0;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .nav-links {
            list-style: none;
            padding: 20px 0;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
        }

        .nav-links li a:hover,
        .nav-links li a.active {
            background-color: var(--secondary-brown);
        }

        .nav-links li a i {
            width: 20px;
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

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .inventory-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: var(--primary-brown);
            font-size: 2em;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            position: relative;
        }

        .product-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            min-width: 250px;
        }

        .product-dropdown.show {
            display: block;
        }

        .dropdown-product {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }

        .dropdown-product:hover {
            background-color: #f5f5f5;
        }

        .dropdown-product:last-child {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .product-name {
            font-weight: bold;
            color: var(--dark-brown);
        }

        .product-size, .product-quantity {
            font-size: 0.9em;
            color: #666;
        }

        .no-products {
            padding: 15px;
            text-align: center;
            color: #666;
        }

        .error-message {
            padding: 15px;
            text-align: center;
            color: #ff0000;
        }

        .inventory-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .inventory-item:hover {
            background-color: #f8f9fa;
        }

        .inventory-item:last-child {
            border-bottom: none;
        }

        .inventory-item-details {
            margin-top: 5px;
            font-size: 0.9em;
            color: #666;
        }

        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .product-card h3 {
            color: var(--primary-brown);
            margin-bottom: 10px;
        }

        .product-details {
            margin: 15px 0;
        }

        .product-details p {
            margin: 5px 0;
            color: #666;
        }

        /* Modal and Button Styles */
        .update-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .update-modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-brown);
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--secondary-brown);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-brown);
            font-weight: bold;
        }

        .price-input {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--light-brown);
            border-radius: 5px;
            font-size: 16px;
        }

        .submit-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: var(--secondary-brown);
        }

        .action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .update-btn {
            background-color: #0275d8;
            color: white;
        }

        .add-btn {
            background-color: var(--primary-brown);
            color: white;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .update-btn:hover {
            background-color: #025aa5;
        }

        .add-btn:hover {
            background-color: var(--secondary-brown);
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .product-price {
            font-size: 1.2rem;
            color: var(--primary-brown);
            font-weight: bold;
            margin: 10px 0;
        }

        .no-price {
            color: #666;
            font-style: italic;
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

        /* Add these styles to your existing CSS */
        .product-image-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .product-image-placeholder i {
            font-size: 48px;
            color: #ccc;
        }

        .no-dimensions {
            color: #666;
            font-style: italic;
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            background-color: #f5f5f5;
        }

        /* Add these styles to your existing CSS */
        .product-size {
            font-size: 0.9em;
            color: #666;
            margin: 5px 0;
            line-height: 1.4;
        }

        .dimensions-list {
            margin-top: 5px;
        }

        .dimension-item {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
            padding: 2px 6px;
            background-color: #f5f5f5;
            border-radius: 3px;
            font-size: 0.9em;
        }

        .highlighted-row {
            animation: highlightRow 2s ease-in-out;
            background-color: rgba(var(--primary-brown-rgb), 0.1);
        }

        .highlighted-product {
            animation: highlightProduct 2s ease-in-out forwards;
            font-weight: bold;
            color: var(--primary-brown);
            position: relative;
            display: inline-block;
            transform-origin: left center;
        }

        @keyframes highlightRow {
            0% {
                background-color: rgba(var(--primary-brown-rgb), 0.3);
            }
            100% {
                background-color: rgba(var(--primary-brown-rgb), 0.1);
            }
        }

        @keyframes highlightProduct {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
            100% {
                transform: scale(1.1);
            }
        }

        /* Add a subtle glow effect */
        .highlighted-product::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 4px;
            background-color: rgba(var(--primary-brown-rgb), 0.1);
            z-index: -1;
            animation: glowEffect 2s ease-in-out infinite;
        }

        @keyframes glowEffect {
            0% {
                box-shadow: 0 0 5px rgba(var(--primary-brown-rgb), 0.3);
            }
            50% {
                box-shadow: 0 0 15px rgba(var(--primary-brown-rgb), 0.5);
            }
            100% {
                box-shadow: 0 0 5px rgba(var(--primary-brown-rgb), 0.3);
            }
        }
    </style>
    <style>
    .burger-menu {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 2000;
        background: var(--primary-brown);
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
    }

    /* Update sidebar styles */
    .sidebar {
        transition: transform 0.3s ease;
        z-index: 1999;
    }

    @media screen and (max-width: 768px) {
        .burger-menu {
            display: flex;
        }

        .sidebar {
            transform: translateX(-100%);
            width: 250px;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            width: 100%;
            padding-top: 70px;
        }

        .profile-section, 
        .nav-links span, 
        .logout-btn span {
            display: block;
        }
    }
</style>
</head>
<button class="burger-menu" id="burgerMenu">
    <i class="fas fa-bars"></i>
</button>
<body>
    <!-- Sidebar (same as inventory.php) -->
    <div class="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <button class="change-photo-btn" onclick="showProfileUploadModal()">
                <i class="fas fa-camera"></i>
            </button>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
            </div>
        </div>

        <ul class="nav-links">
            <li>
                <a href="seller_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'seller_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="my_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i>
                    <span>My Products</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="daily_sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'daily_sales.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Daily Sales</span>
                </a>
            </li>
            <li>
                <a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i>
                    <span>Product Inventory</span>
                </a>
            </li>
            <li>
                <a href="presentations.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'presentations.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Presentations</span>
                    <?php if (!empty($presentations)): ?>
                        <span class="notification-badge"><?php echo count($presentations); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="gcash_settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'gcash_settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill"></i>
                    <span>GCash Settings</span>
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="inventory-container">
            <div class="header">
                <h1>My Products</h1>
                <div class="header-actions">
                    <button class="action-btn add-btn" onclick="toggleProductDropdown()">
                        <i class="fas fa-plus"></i> Add Existing Product
                    </button>
                    <div id="productDropdown" class="product-dropdown">
                        <!-- Inventory products will be loaded here -->
                    </div>
                </div>
            </div>

            <!-- Product List -->
            <div class="product-list">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['image_path']) && file_exists($product['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image-placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <div class="product-details">
                            <p><strong>Available Sizes:</strong> 
                                <?php 
                                $dimensions = array_filter(
                                    explode(', ', $product['dimensions']),
                                    function($dim) {
                                        $parts = explode('x', $dim);
                                        return count($parts) === 3 && !empty($parts[0]) && !empty($parts[1]) && !empty($parts[2]);
                                    }
                                );
                                if (!empty($dimensions)) {
                                    echo htmlspecialchars(implode(', ', $dimensions)) . ' ft';
                                } else {
                                    echo '<span class="no-dimensions">No sizes available</span>';
                                }
                                ?>
                            </p>
                            <p><strong>Total Quantity:</strong> <?php echo $product['total_quantity']; ?> pieces</p>
                            <p class="product-price">
                                <strong>Price per sq.ft:</strong> 
                                <?php if ($product['price_per_sqft']): ?>
                                    ₱<?php echo number_format($product['price_per_sqft'], 2); ?>
                                <?php else: ?>
                                    <span class="no-price">No price set</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="product-actions">
                            <?php if ($product['price_per_sqft']): ?>
                                <button class="action-btn update-btn" onclick="showUpdateModal('<?php 
                                    echo htmlspecialchars(json_encode([
                                        'product_name' => $product['product_name'],
                                        'price_per_sqft' => $product['price_per_sqft'],
                                        'catalog_id' => $product['catalog_id']
                                    ]), ENT_QUOTES); 
                                ?>')">
                                    <i class="fas fa-edit"></i> Update Price
                                </button>
                            <?php else: ?>
                                <button class="action-btn add-btn" onclick="showUpdateModal('<?php 
                                    echo htmlspecialchars(json_encode([
                                        'product_name' => $product['product_name'],
                                        'price_per_sqft' => '',
                                        'catalog_id' => ''
                                    ]), ENT_QUOTES); 
                                ?>')">
                                    <i class="fas fa-plus"></i> Add Price
                                </button>
                            <?php endif; ?>
                            <button class="action-btn delete-btn" onclick="deleteProduct('<?php 
                                echo htmlspecialchars(json_encode([
                                    'product_name' => $product['product_name']
                                ]), ENT_QUOTES); 
                            ?>')">
                                <i class="fas fa-times"></i> Delete Product
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="update-modal">
        <div class="update-modal-content">
            <span class="close-modal" onclick="closeAddProductModal()">×</span>
            <h2>Add Existing Product</h2>
            <form id="addProductForm">
                <div class="form-group">
                    <label for="productSelect">Select Product</label>
                    <select id="productSelect" name="product_name" class="price-input" required onchange="fillProductDetails()">
                        <option value="">Select a product...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="length">Length (feet)</label>
                    <input type="number" id="length" name="length_feet" class="price-input" required readonly>
                </div>
                <div class="form-group">
                    <label for="width">Width (feet)</label>
                    <input type="number" id="width" name="width_feet" class="price-input" required readonly>
                </div>
                <div class="form-group">
                    <label for="height">Height (feet)</label>
                    <input type="number" id="width" name="height_feet" class="price-input" required readonly>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" class="price-input" required>
                </div>
                <button type="submit" class="submit-btn">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Update Price Modal -->
    <div id="updateModal" class="update-modal">
        <div class="update-modal-content">
            <span class="close-modal" id="closeModalBtn">×</span>
            <h2>Update Product Price</h2>
            <form id="updatePriceForm">
                <input type="hidden" id="updateCatalogId" name="catalog_id">
                <input type="hidden" id="updateProductName" name="product_name">
                
                <div class="form-group">
                    <label for="updatePrice">Price per sq.ft (₱)</label>
                    <input type="number" id="updatePrice" name="price_per_sqft" step="0.01" min="0" class="price-input" required>
                </div>
                
                <button type="submit" class="submit-btn">Update Price</button>
            </form>
        </div>
    </div>

    <!-- Profile Photo Upload Modal -->
    <div id="profileUploadModal" class="profile-upload-modal">
        <div class="profile-upload-content">
            <span class="close-modal" onclick="closeProfileUploadModal()">×</span>
            <h2>Change Profile Photo</h2>
            <form id="profilePhotoForm">
                <div class="form-group">
                    <label for="profilePhoto">Choose Photo</label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" onchange="previewImage(this)" required>
                </div>
                <img id="profilePreview" src="<?php echo !empty($userDetails['profile_photo']) ? htmlspecialchars($userDetails['profile_photo']) : 'assets/images/default-profile.png'; ?>" alt="Profile Preview">
                <button type="submit" class="submit-btn">Upload Photo</button>
            </form>
        </div>
    </div>

    <script>
        const burgerMenu = document.getElementById('burgerMenu');
    const sidebar = document.querySelector('.sidebar');

    burgerMenu.addEventListener('click', (e) => {
        e.stopPropagation();
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
        // Modal Close Button Event Listener
        document.getElementById('closeModalBtn').addEventListener('click', closeUpdateModal);

        // Update Modal Functions
        function showUpdateModal(productData) {
            try {
                const data = JSON.parse(productData);
                document.getElementById('updateCatalogId').value = data.catalog_id || '';
                document.getElementById('updateProductName').value = data.product_name || '';
                document.getElementById('updatePrice').value = data.price_per_sqft || '';
                document.getElementById('updateModal').style.display = 'block';
            } catch (error) {
                console.error('Error parsing product data:', error);
            }
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').style.display = 'none';
        }

        // Update Price Form Handler
        document.getElementById('updatePriceForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('process_update_price.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Price updated successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating price');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating price');
            });
        };

        // Delete Product Function
        function deleteProduct(productData) {
            try {
                const data = JSON.parse(decodeURIComponent(productData));
                if (confirm('Are you sure you want to remove this product from My Products? The product will still be available in your inventory.')) {
                    fetch('process_delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_name: data.product_name
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Product removed successfully');
                            location.reload();
                        } else {
                            alert(data.message || 'Error removing product');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error removing product');
                    });
                }
            } catch (error) {
                console.error('Error parsing product data:', error);
                alert('Error parsing product data');
            }
        }

        function toggleProductDropdown() {
            const dropdown = document.getElementById('productDropdown');
            if (!dropdown.classList.contains('show')) {
                loadInventoryProducts();
            }
            dropdown.classList.toggle('show');
        }

        function loadInventoryProducts() {
            fetch('get_inventory_products.php')
                .then(response => response.json())
                .then(data => {
                    const dropdown = document.getElementById('productDropdown');
                    dropdown.innerHTML = '';
                    
                    if (data.length === 0) {
                        dropdown.innerHTML = '<div class="no-products">No available products to add</div>';
                        return;
                    }

                    data.forEach(product => {
                        const dimensions = product.dimensions.split(', ')
                            .filter(dim => {
                                const parts = dim.split('x');
                                return parts.length === 3 && parts.every(part => part !== '0');
                            })
                            .map(dim => `${dim} ft`)
                            .join(', ');

                        const productDiv = document.createElement('div');
                        productDiv.className = 'dropdown-product';
                        productDiv.innerHTML = `
                            <div class="product-info">
                                <div class="product-name">${product.product_name}</div>
                                <div class="product-size">Sizes: ${dimensions || 'No sizes available'}</div>
                                <div class="product-quantity">Quantity: ${product.total_quantity} pieces</div>
                            </div>
                        `;
                        productDiv.onclick = () => addToMyProducts(encodeURIComponent(JSON.stringify(product)));
                        dropdown.appendChild(productDiv);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    document.getElementById('productDropdown').innerHTML = 
                        '<div class="error-message">Error loading products</div>';
                });
        }

        function addToMyProducts(productData) {
            try {
                const product = JSON.parse(decodeURIComponent(productData));
                if (confirm(`Add "${product.product_name}" to My Products?`)) {
                    const formData = new FormData();
                    formData.append('productName', product.product_name);
                    formData.append('productId', product.product_id);
                    formData.append('length_feet', product.length_feet);
                    formData.append('width_feet', product.width_feet);
                    formData.append('height_feet', product.height_feet);
                    formData.append('quantity', product.quantity);
                    formData.append('image_path', product.image_path || '');
                    
                    fetch('process_add_product.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Product added successfully');
                            location.reload();
                        } else {
                            alert(data.message || 'Error adding product');
                        }
                        document.getElementById('productDropdown').classList.remove('show');
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error adding product');
                    });
                }
            } catch (error) {
                console.error('Error parsing product data:', error);
                alert('Error parsing product data');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const productDropdown = document.getElementById('productDropdown');
            const addButton = document.querySelector('.add-btn');

            if (!addButton.contains(event.target) && !productDropdown.contains(event.target)) {
                productDropdown.classList.remove('show');
            }
        });

        // Add Product Form Handler
        document.getElementById('addProductForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('process_add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product added successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error adding product');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product');
            });
        };

        function showProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'block';
        }

        function closeProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'none';
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('profilePhotoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('update_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile photo updated successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating profile photo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating profile photo');
            });
        });

        // Add this to your existing script section in my_products.php
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there's a product to highlight
            const highlightProduct = sessionStorage.getItem('highlightProduct');
            if (highlightProduct) {
                // Find the product row
                const productRows = document.querySelectorAll('tr');
                productRows.forEach(row => {
                    const productNameCell = row.querySelector('td:first-child');
                    if (productNameCell && productNameCell.textContent.trim() === highlightProduct) {
                        // Highlight the row
                        row.style.backgroundColor = '#fff3cd';
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
                // Clear the stored product name
                sessionStorage.removeItem('highlightProduct');
            }
        });
    </script>
</body>
</html> 