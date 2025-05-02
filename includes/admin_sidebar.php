<?php
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}
?>

<div class="sidebar">
    <div class="admin-profile">
        <div class="admin-info">
            <h3>Admin User</h3>
            <p>Administrator</p>
        </div>
    </div>

    <ul class="nav-links">
        <li>
            <a href="admin_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="admin_accounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_accounts.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Accounts</span>
            </a>
        </li>
        <li>
            <a href="admin_reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
        </li>
    </ul>

    <div class="logout-section">
        <a href="admin_logout.php" style="color: #ff4444;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
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

    .nav-links a:hover,
    .nav-links a.active {
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
</style>