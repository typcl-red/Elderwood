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

// Fetch daily sales data
// Modify the SQL query to use the correct tables
$stmt = $pdo->prepare("
    SELECT ds.*, 
           pc.price_per_sqft as selling_price,
           (ds.total_amount - (pc.price_per_sqft * ds.quantity)) as profit,
           (SELECT SUM(ds2.total_amount - (pc2.price_per_sqft * ds2.quantity)) 
            FROM daily_sales ds2 
            JOIN product_catalog pc2 ON ds2.product_name = pc2.product_name 
            WHERE ds2.seller_id = ?) as total_profit,
           (SELECT SUM((pc3.price_per_sqft * pi.quantity) - (pc3.price_per_sqft * pi.quantity))
            FROM product_inventory pi 
            JOIN product_catalog pc3 ON pi.product_name = pc3.product_name 
            WHERE pi.seller_id = ?) as potential_profit
    FROM daily_sales ds
    JOIN product_catalog pc ON ds.product_name = pc.product_name
    WHERE ds.seller_id = ?
    ORDER BY ds.sale_date DESC, ds.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            --sidebar-width: 250px;
        }

        /* Add existing styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-brown);
            min-height: 100vh;
            display: flex;
        }

        /* Add sidebar styles from seller_dashboard.php */
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
            overflow: hidden;
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

        .nav-links a:hover,
        .nav-links a.active {
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

        /* Update main content styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 30px;
            width: calc(100% - var(--sidebar-width));
        }

        /* Keep your existing sales-container styles */
    </style>
</head>
<body>
    <!-- Add sidebar -->
    <!-- Add burger menu button -->
    <div class="burger-menu">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Update sidebar class -->
    <div class="sidebar" id="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']) ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
            </div>
        </div>

        <ul class="nav-links">
            <li><a href="seller_dashboard.php"><i class="fas fa-home"></i><span>Home</span></a></li>
            <li><a href="my_products.php"><i class="fas fa-box"></i><span>My Products</span></a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i><span>Orders</span></a></li>
            <li><a href="daily_sales.php" class="active"><i class="fas fa-chart-line"></i><span>Daily Sales</span></a></li>
            <li><a href="inventory.php"><i class="fas fa-warehouse"></i><span>Product Inventory</span></a></li>
            <li>
                <a href="presentations.php"><!-- Removed 'active' class here -->
                    <i class="fas fa-box-open"></i>
                    <span>Presentations</span>
                    <?php if (!empty($presentations)): ?>
                        <span class="notification-badge"><?php echo count($presentations); ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="gcash_settings.php"><i class="fas fa-money-bill"></i><span>GCash Settings</span></a></li>
        </ul>

        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Wrap your existing content in main-content div -->
    <div class="main-content">
        <div class="report-container">
            <div class="report-header">
                <h1>Daily Sales Report</h1>
                <hr class="header-line">
                <div class="header-details">
                    <div class="info-container">
                        <div class="seller-info">Seller: <?= htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']) ?></div>
                        <div class="date-info">Date: <?= date('F d, Y') ?></div>
                    </div>
                </div>
            </div>

            <style>
                .info-container {
                    width: 100%;
                    background-color: #8B4513;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 4px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .seller-info, .date-info {
                    font-size: 18px;
                }

                .table-header {
                    display: grid;
                    grid-template-columns: 1fr 1.2fr 0.8fr 0.8fr 1fr;
                    background-color: #f8f9fa;
                    color: #8B4513;
                    border-bottom: 2px solid #8B4513;
                    padding: 10px 0;
                }

                .header-cell {
                    padding: 8px 15px;
                    text-align: left;
                    font-weight: bold;
                }
            </style>
            <?php if (empty($sales)): ?>
                <div class="no-sales">
                    <i class="fas fa-info-circle"></i> No sales recorded yet.
                </div>
            <?php else: ?>
                <div class="sales-table">
                    <div class="table-header">
                        <div class="header-cell"><strong>Product</strong></div>
                        <div class="header-cell"><strong>Size (L × W × H)</strong></div>
                        <div class="header-cell"><strong>Quantity</strong></div>
                        <div class="header-cell"><strong>Total Amount</strong></div>
                        <div class="header-cell"><strong>Profit</strong></div>
                    </div>

                    <!-- Update the table row display -->
                    <div class="table-body">
                        <?php 
                        $totalAmount = 0;
                        $totalProfit = 0;
                        foreach ($sales as $sale): 
                            $totalAmount += $sale['total_amount'];
                            $totalProfit += $sale['profit'];
                        ?>
                            <div class="table-row">
                                <div class="table-cell"><?= htmlspecialchars($sale['product_name']) ?></div>
                                <div class="table-cell"><?= intval($sale['length_feet']) . "' × " . intval($sale['width_feet']) . "' × " . intval($sale['height_feet']) . "'" ?></div>
                                <div class="table-cell"><?= $sale['quantity'] ?> pcs</div>
                                <div class="table-cell">₱<?= number_format($sale['total_amount'], 2) ?></div>
                                <div class="table-cell">₱<?= number_format($sale['profit'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Update total row -->
                        <div class="table-row total-row">
                            <div class="table-cell"></div>
                            <div class="table-cell"></div>
                            <div class="table-cell"></div>
                            <div class="table-cell">Total Sales: ₱<?= number_format($totalAmount, 2) ?></div>
                            <div class="table-cell">Total Profit: ₱<?= number_format($totalProfit, 2) ?></div>
                        </div>
                    </div>

                    <style>
                        .total-row {
                            border-top: 2px solid #333;
                            font-weight: bold;
                            margin-top: 10px;
                        }

                        .total-label {
                            text-align: right;
                            padding-right: 15px;
                            font-weight: bold;
                        }
                    </style>
                </div>

                <style>
                    .table-header {
                        display: grid;
                        grid-template-columns: 1fr 1.2fr 0.8fr 0.8fr 1fr;
                        background-color: #ffffff;
                        border-bottom: 1px solid #ddd;
                        padding: 10px 0;
                    }

                    .header-cell {
                        padding: 8px 15px;
                        text-align: left;
                        font-weight: bold;
                        color: #333;
                    }

                    .table-row {
                        display: grid;
                        grid-template-columns: 1fr 1.2fr 0.8fr 0.8fr 1fr;
                        border-bottom: 1px solid #ddd;
                    }

                    .table-cell {
                        padding: 8px 15px;
                        text-align: left;
                    }

                    .total-row {
                        border-top: 1px solid #ddd;
                        font-weight: bold;
                    }

                    .total-label {
                        text-align: right;
                        padding-right: 15px;
                    }

                    .table-cell:last-child {
                        text-align: right;
                    }
                </style>
            <?php endif; ?>
        </div>

        <!-- Add Product Profit Calculator here -->
        <div class="profit-calculator">
            <h2>Product Profit Calculator</h2>
            <form id="profitForm" class="calculator-form">
                <div class="form-group">
                    <label for="product">Select Product:</label>
                    <select name="product" id="product" required>
                        <option value="">Choose a product</option>
                        <?php
                        // Fetch seller's products from product_inventory
                        $productStmt = $pdo->prepare("
                            SELECT DISTINCT pi.*, pc.price_per_sqft 
                            FROM product_inventory pi
                            JOIN product_catalog pc ON pi.product_name = pc.product_name
                            WHERE pi.seller_id = ?
                        ");
                        $productStmt->execute([$_SESSION['user_id']]);
                        $products = $productStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($products as $product) {
                            echo "<option value='" . htmlspecialchars($product['product_name']) . "' 
                                  data-quantity='" . $product['quantity'] . "'
                                  data-price='" . $product['price_per_sqft'] . "'>" . 
                                  htmlspecialchars($product['product_name']) . " (" . 
                                  $product['length_feet'] . "' × " . 
                                  $product['width_feet'] . "' × " . 
                                  $product['height_feet'] . "')</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="investment">Investment Amount (₱):</label>
                    <input type="number" id="investment" name="investment" step="0.01" required>
                </div>
                <button type="submit">Calculate Profit</button>
            </form>

            <div id="profitResults" class="results-container" style="display: none;">
                <h3>Profit Analysis</h3>
                <div class="results-grid">
                    <div class="result-item">
                        <span>Remaining Stock:</span>
                        <span id="remainingStock">0 pcs</span>
                    </div>
                    <div class="result-item">
                        <span>Sold Quantity:</span>
                        <span id="soldQuantity">0 pcs</span>
                    </div>
                    <div class="result-item">
                        <span>Current Earnings:</span>
                        <span id="currentEarnings">₱0.00</span>
                    </div>
                    <div class="result-item">
                        <span>Potential Earnings:</span>
                        <span id="potentialEarnings">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add the sales analysis chart -->
        <?php include 'sales_analysis.php'; ?>
        
        <!-- Add the trending products chart -->
        <?php include 'trending_products.php'; ?>

        <style>
            .profit-calculator {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin-top: 30px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .profit-calculator h2 {
                color: var(--primary-brown);
                margin-bottom: 20px;
            }

            .calculator-form {
                display: grid;
                gap: 20px;
                max-width: 500px;
            }

            .form-group {
                display: grid;
                gap: 8px;
            }

            .form-group label {
                color: #666;
                font-weight: 500;
            }

            .form-group select,
            .form-group input {
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 16px;
            }

            .calculator-form button {
                background: var(--primary-brown);
                color: white;
                border: none;
                padding: 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }

            .results-container {
                margin-top: 20px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 4px;
            }

            .results-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin-top: 15px;
            }

            .result-item {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                background: white;
                border-radius: 4px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
        </style>

        <script>
            document.getElementById('profitForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const productSelect = document.getElementById('product');
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const investment = parseFloat(document.getElementById('investment').value);
                const productName = selectedOption.value;
                
                try {
                    const response = await fetch(`get_sales_data.php?product=${encodeURIComponent(productName)}`);
                    const data = await response.json();
                    
                    const soldQuantity = data.sold_quantity;
                    const remainingQuantity = data.remaining_quantity;
                    const currentEarnings = data.total_earnings;
                    const pricePerUnit = data.price_per_unit;
                    const totalQuantity = data.total_quantity;
                    
                    // Calculate potential earnings (total quantity * price - investment)
                    const potentialEarnings = (totalQuantity * pricePerUnit) - investment;
                    
                    // Display results
                    document.getElementById('remainingStock').textContent = `${remainingQuantity} pcs`;
                    document.getElementById('soldQuantity').textContent = `${soldQuantity} pcs`;
                    document.getElementById('currentEarnings').textContent = `₱${currentEarnings.toFixed(2)}`;
                    document.getElementById('potentialEarnings').textContent = `₱${potentialEarnings.toFixed(2)}`;
                    
                    document.getElementById('profitResults').style.display = 'block';
                } catch (error) {
                    console.error('Error fetching sales data:', error);
                }
            });
        </script>

        

        <style>
            /* Update styles */
            .profit-calculator {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin-top: 30px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .sales-analysis {
                margin-top: 30px;
            }

            .analysis-container {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .chart-header {
                color: var(--primary-brown);
                margin-bottom: 20px;
                text-align: center;
            }

            .charts-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Add the chart initialization code
            <?php
            // Fetch last 7 days of sales data
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(sale_date) as date,
                    SUM(total_amount) as daily_sales,
                    SUM(quantity) as daily_quantity
                FROM daily_sales 
                WHERE seller_id = ? 
                AND sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
                GROUP BY DATE(sale_date)
                ORDER BY date ASC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare data for charts
            $dates = [];
            $sales = [];
            $quantities = [];

            foreach ($salesData as $data) {
                $dates[] = date('M d', strtotime($data['date']));
                $sales[] = floatval($data['daily_sales']);
                $quantities[] = intval($data['daily_quantity']);
            }
            ?>

            // Sales Chart
            new Chart(document.getElementById('salesChart'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Daily Sales (₱)',
                        data: <?php echo json_encode($sales); ?>,
                        borderColor: '#8B4513',
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Sales Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value;
                                }
                            }
                        }
                    }
                }
            });

            // Quantity Chart
            new Chart(document.getElementById('quantityChart'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Items Sold',
                        data: <?php echo json_encode($quantities); ?>,
                        backgroundColor: '#DEB887',
                        borderColor: '#8B4513',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Quantity Sold'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>

    <style>
        /* Add burger menu styles */
        .burger-menu {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-brown);
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Update existing sidebar styles */
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
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        /* Add responsive styles */
        @media screen and (max-width: 768px) {
            .burger-menu {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }
    </style>

    <!-- Add JavaScript for burger menu -->
    <script>
        const burgerMenu = document.querySelector('.burger-menu');
        const sidebar = document.querySelector('.sidebar');

        burgerMenu.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !burgerMenu.contains(e.target) && 
                sidebar.classList.contains('active')) {
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