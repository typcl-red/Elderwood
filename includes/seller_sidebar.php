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
        position: relative;
        overflow: hidden;
    }

    .profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .change-photo-btn {
        position: absolute;
        bottom: 0;
        right: 0;
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
        margin: 5px;
    }

    .change-photo-btn:hover {
        background: var(--secondary-brown);
    }

    .profile-details {
        margin: 15px 0;
    }

    .profile-details h3 {
        margin-bottom: 10px;
        font-size: 1.2rem;
        color: white;
    }

    .profile-details p {
        font-size: 0.9rem;
        color: var(--light-brown);
        margin: 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .profile-details i {
        width: 20px;
        text-align: center;
    }

    .nav-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-links li {
        margin: 5px 0;
    }

    .nav-links a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        gap: 10px;
    }

    .nav-links a:hover {
        background-color: var(--secondary-brown);
    }

    .nav-links i {
        width: 24px;
        font-size: 1.1rem;
    }

    .logout-btn {
        position: absolute;
        bottom: 20px;
        left: 0;
        width: 100%;
        padding: 0 20px;
    }

    .logout-btn a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: white;
        text-decoration: none;
        background-color: var(--secondary-brown);
        border-radius: 8px;
        transition: all 0.3s ease;
        gap: 10px;
    }

    .logout-btn a:hover {
        background-color: #8B0000;
    }

    /* Main Content Styles */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: 30px;
        width: calc(100% - var(--sidebar-width));
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .sidebar {
            width: 60px;
        }

        .profile-section {
            padding: 10px;
        }

        .profile-details, 
        .nav-links span, 
        .logout-btn span {
            display: none;
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

        .main-content {
            margin-left: 60px;
            width: calc(100% - 60px);
        }
    }
</style>
