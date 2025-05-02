<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

// Fetch last 7 days of sales data
// Update the SQL query to include trending products
$stmt = $pdo->prepare("
    SELECT 
        DATE(sale_date) as date,
        SUM(total_amount) as daily_sales
    FROM daily_sales 
    WHERE seller_id = ? 
    AND sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY date ASC
");

// Add new query for trending products
$trendingStmt = $pdo->prepare("
    SELECT 
        product_name,
        SUM(quantity) as total_sold,
        SUM(total_amount) as total_revenue
    FROM daily_sales 
    WHERE seller_id = ? 
    AND sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY product_name
    ORDER BY total_sold DESC
    LIMIT 5
");
$trendingStmt->execute([$_SESSION['user_id']]);
$trendingData = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare trending data
$productNames = [];
$productSales = [];
foreach ($trendingData as $data) {
    $productNames[] = $data['product_name'];
    $productSales[] = floatval($data['total_revenue']);
}

// Update HTML structure
?>
<div class="chart-container">
    <h2 class="chart-header">Sales Analysis (Last 7 Days)</h2>
    <div class="charts-grid">
        <div class="chart-wrapper">
            <canvas id="salesChart"></canvas>
        </div>
        <div class="chart-wrapper">
            <canvas id="trendingChart"></canvas>
        </div>
    </div>
</div>

<style>
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        width: 100%;
    }

    .chart-wrapper {
        position: relative;
        height: 300px;
        width: 100%;
        margin-bottom: 20px;
    }

    @media screen and (max-width: 768px) {
        .chart-wrapper {
            height: 250px;
        }
    }
</style>

<script>
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Daily Sales (₱)',
                data: <?php echo json_encode($sales); ?>,
                borderColor: '#8B4513',
                backgroundColor: 'rgba(139, 69, 19, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
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
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Add trending products chart
    new Chart(document.getElementById('trendingChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($productNames); ?>,
            datasets: [{
                label: 'Product Revenue (₱)',
                data: <?php echo json_encode($productSales); ?>,
                backgroundColor: '#DEB887',
                borderColor: '#8B4513',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Trending Products'
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>