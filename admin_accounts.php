<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Fetch users grouped by role
$roles = ['Seller', 'Buyer', 'Supplier', 'Laborer'];
$usersByRole = [];

foreach ($roles as $role) {
    $stmt = $pdo->prepare("SELECT id, firstname, lastname, email, contactno, address FROM users WHERE role = ? ORDER BY lastname, firstname");
    $stmt->execute([$role]);
    $usersByRole[$role] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts - ElderWood Admin</title>
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

        /* Sidebar Styles */
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

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 30px;
            background-color: var(--bg-white);
        }

        /* Add specific styles for accounts page */
        .role-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn-update, .btn-delete {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
        }

        .btn-update {
            background-color: #007bff;
            color: white !important;
            border: none;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white !important;
            border: none;
        }

        .btn-update:hover {
            background-color: #0056b3;
        }

        .btn-delete:hover {
            background-color: #c82333;
        }

        .fas {
            font-size: 12px;
        }

        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }

        .role-header h2 {
            color: var(--text-dark);
            font-size: 1.5rem;
        }

        .user-count {
            background: var(--light-gray);
            padding: 5px 15px;
            border-radius: 20px;
            color: var(--primary-gray);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
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

        .status-active {
            color: #28a745;
        }

        .status-inactive {
            color: #dc3545;
        }

    </style>
</head>
<body>
    <!-- Include the sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>User Accounts</h1>
            <p>Manage user accounts by role</p>
        </div>

        <?php foreach ($roles as $role): ?>
        <div class="role-section">
            <div class="role-header">
                <h2><?php echo $role; ?>s</h2>
                <span class="user-count"><?php echo count($usersByRole[$role]); ?> users</span>
            </div>

            <?php if (!empty($usersByRole[$role])): ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersByRole[$role] as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['contactno']); ?></td>
                        <td><?php echo htmlspecialchars($user['address']); ?></td>
                        <td>
                            <a href="view_user.php?id=<?php echo $user['id']; ?>&role=<?php echo $role; ?>" class="btn-view">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="update_user.php?id=<?php echo $user['id']; ?>" class="btn-update">
                                <i class="fas fa-edit"></i> Update
                            </a>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No <?php echo strtolower($role); ?>s found.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'delete_user.php?id=' + userId;
            }
        }
    </script>
</body>
</html>