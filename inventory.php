<?php
require_once 'includes/session.php';
require_once 'database/config.php';
require_once 'classes/User.php';  // Add User class requirement

// Temporary debugging code - remove after fixing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a seller
checkLogin();
checkRole(['Seller']);

$seller_id = $_SESSION['user_id'];

// Get user details
$user = new User($pdo);
$userDetails = $user->getUserById($seller_id);

// Get seller's products with total quantity grouped by product name
$stmt = $pdo->prepare("
    SELECT 
        product_name,
        image_path,
        SUM(quantity) as total_quantity,
        GROUP_CONCAT(
            CONCAT(length_feet, ' x ', width_feet, ' x ', height_feet, ' ft (', quantity, ' pcs)')
            ORDER BY length_feet, width_feet, height_feet
            SEPARATOR ', '
        ) as size_details
    FROM product_inventory 
    WHERE seller_id = ?
    GROUP BY product_name
");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Add this where you fetch products
foreach($products as $product) {
    error_log("Image path: " . print_r($product['image_path'], true));
}

// Place this function at the top of the file, outside of any loops
function formatSizeDetails($size) {
    if (!isset($size['length_feet']) || !isset($size['width_feet']) || 
        !isset($size['height_feet']) || !isset($size['quantity']) ||
        $size['length_feet'] == 0 || $size['width_feet'] == 0 || 
        $size['height_feet'] == 0 || $size['quantity'] == 0) {
        return null;
    }
    return sprintf("%d' x %d' x %d' (%d pcs)", 
        (int)$size['length_feet'], 
        (int)$size['width_feet'], 
        (int)$size['height_feet'], 
        (int)$size['quantity']
    );
}

// When checking if product exists
if (isset($_POST['product_name'])) {
    try {
        // Validate input data
        if (empty($_POST['product_name'])) {
            throw new Exception('Product name is required');
        }
        if (empty($_POST['length_feet'])) {
            throw new Exception('Length is required');
        }
        if (empty($_POST['width_feet'])) {
            throw new Exception('Width is required');
        }
        if (empty($_POST['height_feet'])) {
            throw new Exception('Height is required');
        }
        if (empty($_POST['quantity'])) {
            throw new Exception('Quantity is required');
        }

        $product_name = $_POST['product_name'];
        $length_feet = $_POST['length_feet'];
        $width_feet = $_POST['width_feet'];
        $height_feet = $_POST['height_feet'];
        $quantity = $_POST['quantity'];
        $seller_id = $_SESSION['user_id'];

        // Debug log
        error_log("Received data: " . print_r($_POST, true));

        // Check if product exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM product_inventory
            WHERE seller_id = ? 
            AND product_name = ?
            AND length_feet = ?
            AND width_feet = ?
            AND height_feet = ?
        ");

        $stmt->execute([$seller_id, $product_name, $length_feet, $width_feet, $height_feet]);
        $exists = $stmt->fetchColumn() > 0;

        if ($exists) {
            throw new Exception('You already have this product with the same dimensions in your inventory');
        }

        // Insert into product_inventory
        $stmt = $pdo->prepare("
            INSERT INTO product_inventory (
                seller_id,
                product_name,
                length_feet,
                width_feet,
                height_feet,
                quantity,
                total_square_feet,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        // Calculate total_square_feet
        $total_square_feet = $length_feet * $width_feet * $height_feet;

        $result = $stmt->execute([
            $seller_id,
            $product_name,
            $length_feet,
            $width_feet,
            $height_feet,
            $quantity,
            $total_square_feet
        ]);

        if (!$result) {
            throw new Exception('Database insert failed');
        }

        $inventory_id = $pdo->lastInsertId();

        // Handle image upload if present
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $image_path = handleImageUpload($_FILES['product_image'], $inventory_id);
            
            if ($image_path) {
                $stmt = $pdo->prepare("
                    UPDATE product_inventory 
                    SET image_path = ? 
                    WHERE product_id = ?
                ");
                $stmt->execute([$image_path, $inventory_id]);
            }
        }

        echo json_encode(['success' => true, 'message' => 'Product added successfully to inventory']);

    } catch (Exception $e) {
        error_log("Error in inventory.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Function to handle image upload
function handleImageUpload($file, $inventory_id) {
    $target_dir = "./uploads/inventory/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = "product_" . $inventory_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Remove any potential double slashes
    $target_file = str_replace('//', '/', $target_file);
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Return path relative to script location
        return $target_file;
    }
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

      
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }

        .inventory-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .add-product-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-product-btn:hover {
            background-color: var(--secondary-brown);
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            position: relative;
            background-color: var(--bg-brown);
            margin: 20px auto;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            cursor: pointer;
            color: var(--primary-brown);
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-brown);
            border-radius: 8px;
            font-size: 16px;
        }

        .size-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .size-group .form-group {
            margin-bottom: 0;
        }

        .size-group .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-brown);
            font-weight: bold;
            font-size: 0.9rem;
        }

        .size-group .form-group select,
        .size-group .form-group input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--light-brown);
            border-radius: 8px;
            font-size: 0.9rem;
            background-color: #fafafa;
            transition: all 0.3s ease;
            height: 41px; /* Match the height of select boxes */
            box-sizing: border-box;
        }

        .size-group .form-group select:focus,
        .size-group .form-group input[type="number"]:focus {
            outline: none;
            border-color: var(--primary-brown);
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.2);
        }

        .size-group .form-group input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .size-group .form-group input[type="number"]::-webkit-outer-spin-button,
        .size-group .form-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            appearance: none;
            margin: 0;
        }

        @media screen and (max-width: 768px) {
            .size-group {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px;
            }
        }

        .total-area {
            margin-top: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            color: var(--primary-brown);
        }

        /* Product List Styles */
        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden; /* Ensure content doesn't overflow */
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }

        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--light-brown);
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }

        .image-preview.empty {
            color: var(--primary-brown);
            font-size: 14px;
        }

        .file-input-container {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-label {
            display: block;
            background-color: var(--primary-brown);
            color: white;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            background-color: var(--secondary-brown);
        }

        .file-input {
            display: none;
        }

        .product-card h3 {
            color: var(--primary-brown);
            margin-bottom: 10px;
        }

        .product-details {
            color: var(--secondary-brown);
            margin-bottom: 5px;
        }

        .submit-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-brown);
        }

        .error-message {
            color: #ff3333;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .existing-products {
            margin-top: 5px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid var(--light-brown);
            border-radius: 8px;
        }

        .existing-products h4 {
            color: var(--primary-brown);
            margin: 0 0 10px 0;
            font-size: 14px;
        }

        .product-names-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .product-name-tag {
            background-color: var(--light-brown);
            color: var(--primary-brown);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .product-name-tag:hover {
            background-color: var(--primary-brown);
            color: white;
        }

        /* Profile photo specific styles */
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

        .profile-section {
            position: relative;
        }

        .size-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .size-list li {
            margin-bottom: 5px;
            color: #666;
        }

        h4 {
            color: #8B4513;
            margin-bottom: 10px;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }

        .product-card h3 {
            color: #8B4513;
            margin-bottom: 10px;
        }

        .total-quantity {
            color: #666;
            margin-bottom: 15px;
        }

        .size-details h4 {
            color: #8B4513;
            margin-bottom: 10px;
        }

        .size-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .size-list li {
            margin-bottom: 8px;
            color: #666;
            padding: 5px 10px;
            background: #f8f8f8;
            border-radius: 4px;
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
        <?php include 'includes/seller_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="inventory-container">
            <div class="header">
                <h1>Product Inventory</h1>
                <button class="add-product-btn" id="addProductBtn">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>

            <!-- Add Product Modal -->
            <div id="addProductModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <h2>Add New Product</h2>
                    <form id="addProductForm" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="productName">Product Name</label>
                            <input type="text" id="productName" name="productName" required>
                            <div class="error-message" id="productName-error"></div>
                            <div class="existing-products">
                                <h4>Existing Products:</h4>
                                <div class="product-names-list">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT DISTINCT product_name, image_path FROM product_inventory WHERE seller_id = ?");
                                    $stmt->execute([$seller_id]);
                                    $existingProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Keep track of displayed product names to avoid duplicates
                                    $displayedProducts = [];
                                    
                                    foreach($existingProducts as $product): 
                                        // Only display if not already shown
                                        if (!in_array($product['product_name'], $displayedProducts)):
                                            $displayedProducts[] = $product['product_name'];
                                    ?>
                                        <span class="product-name-tag" 
                                              data-image-path="<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES); ?>"
                                              onclick="selectProductName('<?php echo htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['image_path'], ENT_QUOTES); ?>')"
                                        ><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="file-input-container" id="imageUploadContainer">
                            <div id="imagePreview" class="image-preview empty">
                                Click or drag an image here
                            </div>
                            <label for="productImage" class="file-input-label">
                                <i class="fas fa-upload"></i> Choose Product Image
                            </label>
                            <input type="file" id="productImage" name="productImage" class="file-input" accept="image/*" required>
                            <div class="error-message" id="productImage-error"></div>
                        </div>

                        <div class="size-group">
                            <div class="form-group">
                                <label for="lengthFeet">Length (feet)</label>
                                <select id="lengthFeet" name="lengthFeet" required>
                                    <option value="">Select length</option>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> feet</option>
                                    <?php endfor; ?>
                                </select>
                                <div class="error-message" id="lengthFeet-error"></div>
                            </div>

                            <div class="form-group">
                                <label for="widthFeet">Width (feet)</label>
                                <select id="widthFeet" name="widthFeet" required>
                                    <option value="">Select width</option>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> feet</option>
                                    <?php endfor; ?>
                                </select>
                                <div class="error-message" id="widthFeet-error"></div>
                            </div>

                            <div class="form-group">
                                <label for="heightFeet">Height (feet)</label>
                                <select id="heightFeet" name="heightFeet" required>
                                    <option value="">Select height</option>
                                    <?php for($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> feet</option>
                                    <?php endfor; ?>
                                </select>
                                <div class="error-message" id="heightFeet-error"></div>
                            </div>

                            <div class="form-group">
                                <label for="quantity">Quantity (pieces)</label>
                                <input type="number" id="quantity" name="quantity" min="1" required>
                                <div class="error-message" id="quantity-error"></div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">Add Product</button>
                    </form>
                </div>
            </div>

            <!-- Product List -->
            <div class="product-list">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <?php
                        // Check if image path exists and create proper URL path
                        $imagePath = !empty($product['image_path']) ? 
                                    './' . ltrim($product['image_path'], './') : 
                                    './images/default-product.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             class="product-image"
                             onerror="this.onerror=null; this.src='./images/default-product.jpg';">
                        
                        <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        
                        <p class="total-quantity">
                            Total Quantity: <?php echo htmlspecialchars($product['total_quantity']); ?> pieces
                        </p>

                        <div class="size-details">
                            <h4>Size Details:</h4>
                            <?php if (!empty($product['size_details'])): ?>
                                <ul class="size-list">
                                    <?php 
                                    $sizes = explode(', ', $product['size_details']);
                                    foreach ($sizes as $size): ?>
                                        <li><?php echo htmlspecialchars($size); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p>No size details available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
        // Modal functionality
        const modal = document.getElementById('addProductModal');
        const addBtn = document.getElementById('addProductBtn');
        const closeBtn = document.querySelector('.close-btn');
        const form = document.getElementById('addProductForm');
        const lengthSelect = document.getElementById('lengthFeet');
        const widthSelect = document.getElementById('widthFeet');
        const heightSelect = document.getElementById('heightFeet');
        const quantityInput = document.getElementById('quantity');

        // Product name input handler
        const productNameInput = document.getElementById('productName');
        productNameInput.addEventListener('input', function(e) {
            const value = e.target.value.trim().toLowerCase();
            
            // Filter visible product tags
            document.querySelectorAll('.product-name-tag').forEach(tag => {
                if (tag.textContent.toLowerCase().includes(value)) {
                    tag.style.display = 'inline-block';
                } else {
                    tag.style.display = 'none';
                }
            });

            // Check if product name exists
            const existingTag = Array.from(document.querySelectorAll('.product-name-tag'))
                .find(tag => tag.textContent.toLowerCase() === value.toLowerCase());
            
            if (existingTag) {
                // Get image path from the tag's data
                const imagePath = existingTag.getAttribute('data-image-path');
                handleExistingProduct(imagePath);
            } else {
                // Show image upload for new products
                const imageUploadContainer = document.getElementById('imageUploadContainer');
                const imageInput = document.getElementById('productImage');
                imageUploadContainer.style.display = 'block';
                imageInput.setAttribute('required', 'required');
            }
        });

        // Function to handle existing product selection
        function handleExistingProduct(imagePath) {
            const imageUploadContainer = document.getElementById('imageUploadContainer');
            const imageInput = document.getElementById('productImage');
            
            if (imagePath) {
                // Hide image upload for existing products
                imageUploadContainer.style.display = 'none';
                imageInput.removeAttribute('required');
            }
        }

        // Function to select product name
        function selectProductName(name, imagePath) {
            document.getElementById('productName').value = name;
            handleExistingProduct(imagePath);
        }

        // Reset form handler
        function resetForm() {
            addProductForm.reset();
            const imageUploadContainer = document.getElementById('imageUploadContainer');
            const imageInput = document.getElementById('productImage');
            const imagePreview = document.getElementById('imagePreview');
            
            // Show image upload
            imageUploadContainer.style.display = 'block';
            imageInput.setAttribute('required', 'required');
            imagePreview.style.backgroundImage = '';
            imagePreview.textContent = 'Click or drag an image here';
            imagePreview.classList.add('empty');
        }

        // Modal handlers
        addBtn.onclick = function() {
            modal.style.display = 'block';
            resetForm();
        }

        closeBtn.onclick = function() {
            modal.style.display = 'none';
            resetForm();
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
                resetForm();
            }
        }

        // Image preview functionality
        const imageInput = document.getElementById('productImage');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.style.backgroundImage = `url(${e.target.result})`;
                    imagePreview.textContent = '';
                    imagePreview.classList.remove('empty');
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.backgroundImage = '';
                imagePreview.textContent = 'Click or drag an image here';
                imagePreview.classList.add('empty');
            }
        });

        // Form submission
        addProductForm.onsubmit = function(e) {
            e.preventDefault();
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(error => error.style.display = 'none');
            
            // Get form values
            const formData = new FormData();
            const productName = document.getElementById('productName').value.trim();
            const lengthFeet = lengthSelect.value;
            const widthFeet = widthSelect.value;
            const heightFeet = heightSelect.value;
            const quantity = quantityInput.value;
            const productImage = document.getElementById('productImage').files[0];

            // Add all form fields
            formData.append('product_name', productName);
            formData.append('length_feet', lengthFeet);
            formData.append('width_feet', widthFeet);
            formData.append('height_feet', heightFeet);
            formData.append('quantity', quantity);
            
            if (productImage) {
                formData.append('product_image', productImage);
            }

            // Debug log
            console.log('Submitting form with data:', {
                product_name: productName,
                length_feet: lengthFeet,
                width_feet: widthFeet,
                height_feet: heightFeet,
                quantity: quantity,
                has_image: !!productImage
            });
            
            // Submit form
            fetch('inventory.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data); // Debug log
                if (data.success) {
                    // Reset form and close modal
                    resetForm();
                    modal.style.display = 'none';
                    location.reload();
                } else {
                    // Show specific error message
                    alert(data.message || 'Error adding product. Please check all fields and try again.');
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('Error adding product: ' + error.message);
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
    </script>
</body>
</html> 