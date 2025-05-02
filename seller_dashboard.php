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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - ElderWood</title>
    <!-- Font Awesome for icons -->
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

        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--light-brown);
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: var(--primary-brown);
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

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: var(--primary-brown);
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .dashboard-stats {
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

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .sidebar {
                width: 60px;
                padding: 20px 0;
            }

            .profile-section, .nav-links span, .logout-btn span {
                display: none;
            }

            .main-content {
                margin-left: 60px;
                width: calc(100% - 60px);
            }

            .nav-links a {
                padding: 12px 20px;
                justify-content: flex-start;
            }

            .nav-links i {
                width: auto;
                margin: 0;
            }

            .logout-btn a {
                padding: 15px;
                justify-content: center;
            }

            .logout-btn i {
                margin: 0;
            }
        }

        /* Add these styles to the CSS section */
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
    </style>
</head>
<body>
    <!-- Sidebar -->
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
                <?php
                // Get seller's unique ID
                $stmt = $pdo->prepare("SELECT seller_unique_id FROM seller_ids WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $sellerID = $stmt->fetch();
                
                // If seller ID doesn't exist, generate and insert one
                if (!$sellerID) {
                    $uniqueID = 'S' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare("INSERT INTO seller_ids (user_id, seller_unique_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $uniqueID]);
                    $sellerID = ['seller_unique_id' => $uniqueID];
                }
                ?>
                <p><strong><i class="fas fa-id-badge"></i> Seller ID:</strong> <?php echo htmlspecialchars($sellerID['seller_unique_id']); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
            </div>
        </div>

        <ul class="nav-links">
            <li>
                <a href="seller_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="my_products.php">
                    <i class="fas fa-box"></i>
                    <span>My Products</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="daily_sales.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Daily Sales</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class="fas fa-warehouse"></i>
                    <span>Product Inventory</span>
                </a>
            </li>
            <li>
                <a href="presentations.php" class="active">
                    <i class="fas fa-box-open"></i>
                    <span>Presentations</span>
                    <?php if (!empty($presentations)): ?>
                        <span class="notification-badge"><?php echo count($presentations); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="gcash_settings.php">
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
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($userDetails['firstname']); ?>!</h1>
            <p>Here's your business overview</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>Today's Sales</h3>
                <p>₱0.00</p>
            </div>
            <div class="stat-card">
                <h3>Total Deliveries</h3>
                <p>0</p>
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
            width: 250px;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            width: 100%;
            padding-top: 60px;
        }

        .profile-section {
            display: block;
        }

        .nav-links span {
            display: inline;
        }
    }
</style>

<!-- Add this button right after the body tag -->
<button class="burger-menu" id="burgerMenu">
    <i class="fas fa-bars"></i>
</button>

<!-- Add this script before the closing body tag -->
<script>
    const burgerMenu = document.getElementById('burgerMenu');
    const sidebar = document.querySelector('.sidebar');

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