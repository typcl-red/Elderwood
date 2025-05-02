<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

// Fetch trending products data
// Update the SQL query to match your database structure
$trendingStmt = $pdo->prepare("
    SELECT 
        product_name,
        SUM(quantity) as total_sold,
        SUM(total_amount) as total_revenue,
        length_feet,
        width_feet,
        height_feet
    FROM daily_sales
    WHERE seller_id = ? 
    AND sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY product_name, length_feet, width_feet, height_feet
    ORDER BY total_sold DESC
    LIMIT 5
");

$trendingStmt->execute([$_SESSION['user_id']]);
$trendingData = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for chart
$productNames = [];
$productSales = [];
$productQuantities = [];

foreach ($trendingData as $data) {
    $dimensions = $data['length_feet'] . "' × " . $data['width_feet'] . "' × " . $data['height_feet'] . "'";
    $productNames[] = $data['product_name'] . "\n(" . $dimensions . ")";
    $productSales[] = floatval($data['total_revenue']);
    $productQuantities[] = intval($data['total_sold']);
}
?>

<div class="chart-container">
    <h2 class="chart-header">Trending Products (Last 7 Days)</h2>
    <div class="chart-wrapper">
        <canvas id="trendingProductsChart"></canvas>
    </div>
</div>

<style>
    .chart-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-top: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        width: 100%;
    }

    .chart-header {
        color: #8B4513;
        margin-bottom: 20px;
        text-align: center;
        font-size: 1.5rem;
    }

    .chart-wrapper {
        position: relative;
        height: 400px;
        width: 100%;
    }

    @media screen and (max-width: 768px) {
        .chart-wrapper {
            height: 300px;
        }
    }
</style>

<script>
    new Chart(document.getElementById('trendingProductsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($productNames); ?>,
            datasets: [{
                label: 'Sales Revenue (₱)',
                data: <?php echo json_encode($productSales); ?>,
                backgroundColor: '#DEB887',
                borderColor: '#8B4513',
                borderWidth: 1,
                yAxisID: 'y'
            }, {
                label: 'Quantity Sold',
                data: <?php echo json_encode($productQuantities); ?>,
                backgroundColor: '#8B4513',
                borderColor: '#654321',
                borderWidth: 1,
                yAxisID: 'y1'
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
                    text: 'Top 5 Products by Sales'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
</script>