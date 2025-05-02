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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gcashName = $_POST['gcash_name'];
    $gcashNumber = $_POST['gcash_number'];
    $qrCode = $_FILES['qr_code'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($gcashName)) {
        $errors[] = 'GCash name is required';
    }
    
    if (empty($gcashNumber)) {
        $errors[] = 'GCash number is required';
    } elseif (!preg_match('/^09\d{9}$/', $gcashNumber)) {
        $errors[] = 'Invalid GCash number format';
    }
    
    if ($qrCode['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($qrCode['type'], $allowedTypes)) {
            $errors[] = 'Invalid QR code image format. Only JPG, JPEG, and PNG are allowed';
        }
    }
    
    if (empty($errors)) {
        try {
            // Upload QR code image if provided
            $qrCodePath = '';
            if ($qrCode['error'] === 0) {
                $uploadDir = 'uploads/gcash_qr/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = uniqid() . '_' . time() . '.' . pathinfo($qrCode['name'], PATHINFO_EXTENSION);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($qrCode['tmp_name'], $targetPath)) {
                    $qrCodePath = $targetPath;
                } else {
                    throw new Exception('Failed to upload QR code image');
                }
            }
            
            // Check if seller already has GCash details
            $stmt = $pdo->prepare("SELECT gcash_id FROM gcash WHERE seller_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $existingGcash = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingGcash) {
                // Update existing GCash details
                $stmt = $pdo->prepare("UPDATE gcash SET gcash_name = ?, gcash_number = ?, gcash_qr = ? WHERE seller_id = ?");
                $stmt->execute([$gcashName, $gcashNumber, $qrCodePath, $_SESSION['user_id']]);
            } else {
                // Insert new GCash details
                $stmt = $pdo->prepare("INSERT INTO gcash (seller_id, gcash_name, gcash_number, gcash_qr) VALUES (?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $gcashName, $gcashNumber, $qrCodePath]);
            }
            
            $success = 'GCash details updated successfully';
        } catch (Exception $e) {
            $errors[] = 'Error updating GCash details: ' . $e->getMessage();
        }
    }
}

// Get current GCash details
$stmt = $pdo->prepare("SELECT gcash_name, gcash_number, gcash_qr FROM gcash WHERE seller_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$gcashDetails = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Settings - ElderWood</title>
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
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-brown);
            min-height: 100vh;
            display: flex;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .dashboard-header {
            margin-bottom: 30px;
            text-align: center;
            width: 100%;
        }

        .gcash-form-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
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

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-brown);
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group input[type="file"] {
            border: none;
            padding: 0;
        }

        .current-qr {
            margin-top: 10px;
        }

        .current-qr img {
            max-width: 200px;
            border: 1px solid var(--light-brown);
            border-radius: 5px;
        }

        .submit-btn {
            background-color: #0066cc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #0052a3;
        }

        .gcash-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid var(--light-brown);
        }

        .gcash-details h2 {
            color: var(--primary-brown);
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-item strong {
            display: inline-block;
            width: 120px;
            color: var(--secondary-brown);
        }

        .qr-code-display {
            margin-top: 10px;
        }

        .qr-code-display img {
            max-width: 200px;
            border: 1px solid var(--light-brown);
            border-radius: 5px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

    <div class="main-content">
        <div class="dashboard-header">
            <h1>GCash Settings</h1>
            <p>Manage your GCash account details</p>
        </div>

        <div class="gcash-form-container">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($gcashDetails)): ?>
                <div class="gcash-details">
                    <h2>Current GCash Details</h2>
                    <div class="detail-item">
                        <strong>GCash Name:</strong>
                        <span><?php echo htmlspecialchars($gcashDetails['gcash_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <strong>GCash Number:</strong>
                        <span><?php echo htmlspecialchars($gcashDetails['gcash_number']); ?></span>
                    </div>
                    <?php if (!empty($gcashDetails['gcash_qr'])): ?>
                        <div class="detail-item">
                            <strong>QR Code:</strong>
                            <div class="qr-code-display">
                                <img src="<?php echo htmlspecialchars($gcashDetails['gcash_qr']); ?>" alt="GCash QR Code">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="gcash_name">GCash Name</label>
                    <input type="text" id="gcash_name" name="gcash_name" value="<?php echo htmlspecialchars($gcashDetails['gcash_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="gcash_number">GCash Number</label>
                    <input type="text" id="gcash_number" name="gcash_number" value="<?php echo htmlspecialchars($gcashDetails['gcash_number'] ?? ''); ?>" placeholder="09XXXXXXXXX" required>
                </div>

                <div class="form-group">
                    <label for="qr_code">QR Code Image</label>
                    <input type="file" id="qr_code" name="qr_code" accept="image/*">
                </div>

                <button type="submit" class="submit-btn"><?php echo !empty($gcashDetails) ? 'Update GCash Details' : 'Save GCash Details'; ?></button>
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
</body>
</html>