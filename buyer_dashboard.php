<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Buyer') {
    header('Location: login.php');
    exit();
}

$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

// Get all products from all sellers
$stmt = $pdo->prepare("
    SELECT 
        c.product_name,
        c.price_per_sqft,
        c.catalog_id,
        c.seller_id,
        GROUP_CONCAT(CONCAT(i.length_feet, 'x', i.width_feet) SEPARATOR ', ') as dimensions,
        SUM(i.quantity) as total_quantity,
        i.image_path,
        u.firstname as seller_firstname,
        u.lastname as seller_lastname
    FROM product_catalog c
    JOIN product_inventory i ON 
        c.product_name = i.product_name AND 
        c.seller_id = i.seller_id
    JOIN users u ON 
        c.seller_id = u.ID
    GROUP BY c.product_name, c.price_per_sqft, c.catalog_id, c.seller_id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-brown);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
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

        .change-photo-btn {
            background-color: var(--secondary-brown);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 10px auto;
            width: fit-content;
        }

        .change-photo-btn:hover {
            background-color: var(--primary-brown);
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background-color: var(--light-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-image i {
            font-size: 64px;
            color: var(--primary-brown);
        }

        .profile-details h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .profile-details p {
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }

        .nav-links li {
            padding: 15px 25px;
            transition: background-color 0.3s;
        }

        .nav-links li:hover {
            background-color: var(--secondary-brown);
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links i {
            width: 20px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .page-title {
            color: var(--primary-brown);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details h3 {
            color: var(--primary-brown);
            margin-bottom: 10px;
        }

        .product-details p {
            margin: 5px 0;
            color: #666;
        }

        .product-price {
            font-size: 1.2rem;
            color: var(--primary-brown);
            font-weight: bold;
            margin: 10px 0;
        }

        .checkout-btn {
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .checkout-btn:hover {
            background-color: var(--secondary-brown);
        }

        .seller-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }

        .cart-count {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .product-details-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .product-details-btn:hover {
            background-color: var(--secondary-brown);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .modal-content h2 {
            color: var(--primary-brown);
            margin-bottom: 25px;
            text-align: center;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-brown);
            font-weight: bold;
            font-size: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-brown);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-brown);
        }

        .form-group input[type="number"] {
            -moz-appearance: textfield;
        }

        .form-group input[type="number"]::-webkit-outer-spin-button,
        .form-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .submit-btn {
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: var(--secondary-brown);
        }

        .size-quantity {
            margin: 5px 0;
            padding: 5px;
            background-color: #f8f8f8;
            border-radius: 3px;
            font-size: 0.9rem;
        }

        .center-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px);
            padding: 20px;
            position: relative;
            text-align: center;
        }

        .product-details-btn {
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 30px 60px;
            border-radius: 15px;
            font-size: 1.5rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            white-space: nowrap;
        }

        .product-details-btn:hover {
            background-color: var(--secondary-brown);
            transform: translate(-50%, -52%);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .product-details-btn i {
            font-size: 1.8rem;
        }

        /* Add these styles to your existing CSS */
        .search-prompt {
            text-align: center;
            margin: 150px 0 1px 0;
            width: 100%;
        }

        .search-prompt h2 {
            color: #8B4513;
            font-size: 1.8em;
            font-weight: 500;
            margin-bottom: 1px;
        }

        /* Adjust Product Details button spacing */
        .product-details-btn {
            margin-top: 1px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
                <button class="change-photo-btn" onclick="showProfileUploadModal()">
                    <i class="fas fa-camera"></i> Change Profile Photo
                </button>
            </div>
        </div>

        <ul class="nav-links">
            <li>
                <a href="buyer_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="order_list.php">
                    <i class="fas fa-list-alt"></i>
                    <span>Order List</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">Welcome to ElderWood</h1>
        
        <!-- Add this section above the Product Details button -->
        <div class="search-prompt">
            <h2>Tell us what you're looking for</h2>
        </div>

        <div class="center-container">
            <button class="product-details-btn" onclick="showProductDetails()">
                <i class="fas fa-clipboard-list"></i>
                Product Details
            </button>
        </div>

        <!-- Existing product details section -->
        <div class="product-details">
            <!-- ... existing product details code ... -->
        </div>
    </div>

    <!-- Product Details Modal -->
    <div id="productDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeProductDetails()">&times;</span>
            <h2>Product Inquiry Form</h2>
            <form id="productInquiryForm" onsubmit="submitProductInquiry(event)">
                <div class="form-group">
                    <label for="inquiryProduct">Lumber Name</label>
                    <input type="text" id="inquiryProduct" required>
                </div>
                <div class="form-group">
                    <label for="length">Length (feet)</label>
                    <input type="number" id="length" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label for="width">Width (feet)</label>
                    <input type="number" id="width" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label for="height">Height (feet)</label>
                    <input type="number" id="height" required min="1" step="0.01">
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity (pieces)</label>
                    <input type="number" id="quantity" required min="1">
                </div>
                <input type="hidden" name="height_feet" value="<?php echo htmlspecialchars($product['height_feet']); ?>">
                <button type="submit" class="submit-btn">Submit Inquiry</button>
            </form>
        </div>
    </div>

    <!-- Profile Photo Upload Modal -->
    <div id="profileUploadModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeProfileUploadModal()">&times;</span>
            <h2>Change Profile Photo</h2>
            <form id="profilePhotoForm" onsubmit="uploadProfilePhoto(event)">
                <div class="form-group">
                    <label for="profilePhoto">Choose Photo</label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" required>
                </div>
                <button type="submit" class="submit-btn">Upload Photo</button>
            </form>
        </div>
    </div>

    <script>
        function showProductDetails() {
            document.getElementById('productDetailsModal').style.display = 'block';
            document.getElementById('inquiryProduct').value = '';
        }

        function closeProductDetails() {
            document.getElementById('productDetailsModal').style.display = 'none';
        }

        function submitProductInquiry(event) {
            event.preventDefault();
            const formData = {
                product: document.getElementById('inquiryProduct').value,
                length: document.getElementById('length').value,
                width: document.getElementById('width').value,
                height: document.getElementById('height').value,
                quantity: document.getElementById('quantity').value
            };
            
            fetch('process_product_inquiry.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your inquiry has been submitted successfully.');
                    closeProductDetails();
                    // Clear form
                    event.target.reset();
                } else {
                    alert('Error submitting inquiry: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error submitting inquiry: ' + error.message);
            });
        }

        function showProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'block';
        }

        function closeProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'none';
        }

        function uploadProfilePhoto(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('update_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile photo updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating profile photo: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error uploading photo: ' + error.message);
            });
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        // Update cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const cartCount = document.querySelector('.cart-count');
            cartCount.textContent = cart.length;
        });
    </script>
</body>
</html> 