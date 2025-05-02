<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    header('Location: login.php');
    exit();
}

// Get user details for sidebar
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();

// Get all presentations sent by this supplier
$stmt = $pdo->prepare("
    SELECT so.*, i.product_name, i.description, i.length, i.width, i.height,
           u.firstname, u.lastname, u.contactno, u.address,
           si.seller_unique_id,
           ds.scheduled_date,
           i.quantity,
           GROUP_CONCAT(DISTINCT CONCAT(inv.length, ' × ', inv.width, ' × ', inv.height, ' (', inv.quantity, ' pcs)') SEPARATOR '|') as all_sizes
    FROM sup_orders so
    JOIN inventory i ON so.item_id = i.id
    JOIN users u ON so.seller_id = u.id
    JOIN seller_ids si ON u.id = si.user_id
    LEFT JOIN delivery_schedules ds ON so.id = ds.presentation_id
    LEFT JOIN inventory inv ON i.product_name = inv.product_name AND i.supplier_id = inv.supplier_id
    WHERE i.supplier_id = ?
    GROUP BY so.id
    ORDER BY so.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$presentations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presentations - ElderWood</title>
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
            background-color: var(--bg-brown);
            display: flex;
        }

        .inventory-header {
            margin-bottom: 20px;
        }

        .inventory-header h1 {
            color: var(--primary-brown);
            margin: 0;
        }
                .presentation-card {
                    background: white;
                    border-radius: 8px;
                    padding: 25px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 8px rgba(139, 69, 19, 0.1);
                    border: 1px solid var(--light-brown);
                }

                .seller-info {
                    display: flex;
                    align-items: flex-start;
                    gap: 20px;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 1px solid #eee;
                }

                .seller-info h3 {
                    color: var(--primary-brown);
                    margin: 0 0 10px 0;
                    font-size: 1.4em;
                }

                .seller-info p {
                    margin: 5px 0;
                    color: #666;
                }

                .seller-info i {
                    width: 20px;
                    color: var(--secondary-brown);
                }

                .product-info {
                    background-color: #faf6f1;
                    padding: 15px;
                    border-radius: 6px;
                    margin-bottom: 15px;
                }

                .product-info h4 {
                    color: var(--primary-brown);
                    margin: 0 0 10px 0;
                    font-size: 1.2em;
                }

                .product-info p {
                    margin: 8px 0;
                    color: #555;
                }

                .presentation-status {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #eee;
                }

                .status-badge {
                    padding: 6px 12px;
                    border-radius: 20px;
                    font-size: 0.9em;
                    font-weight: 500;
                }

                .status-pending {
                    background-color: #fff3cd;
                    color: #856404;
                    border: 1px solid #ffeeba;
                }

                .status-declined {
                    background-color: #ffebee;
                    color: #e57373;
                    border: 1px solid #ffcdd2;
                }

                .status-accepted {
                    background-color: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }

                .status-scheduled {
                    background-color: #e8f5e9;
                    color: #2e7d32;
                    border: 1px solid #c8e6c9;
                }
                .presentation-status small {
                    color: #888;
                    font-style: italic;
                }

                .main-content {
                    max-width: 900px;
                    margin: 20px auto;
                    padding: 0 20px;
                }

                .inventory-header {
                    background-color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin-bottom: 30px;
                    box-shadow: 0 2px 8px rgba(139, 69, 19, 0.1);
                }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            display: inline-block;
            margin-right: 10px;
        }
        .size-tag {
            display: inline-block;
            background-color: var(--light-brown);
            color: var(--primary-brown);
            padding: 8px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9em;
            box-shadow: 0 2px 4px rgba(139, 69, 19, 0.1);
            transition: transform 0.2s ease;
        }

        .size-tag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(139, 69, 19, 0.2);
        }

        .sizes-container {
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
        }
        .status-ontheway {
    background-color: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}
.btn-arrive {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    display: none; /* Default state is hidden */
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    transition: all 0.3s ease;
}

.btn-arrive:hover {
    background-color: #45a049;
}

#statusButtons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}
    </style>
</head>
<body>
    <?php include 'includes/supplier_sidebar.php'; ?>

    <div class="main-content">
        
        <div class="inventory-header">
            <h1>Product Presentations</h1>
        </div>

        <?php if (empty($presentations)): ?>
            <div class="no-data">
                <p>No presentations found.</p>
            </div>
        <?php else: ?>
            <!-- Pending Presentations -->
            <div class="presentations-section">
                <h2>Pending Presentations</h2>
                <?php foreach ($presentations as $presentation): ?>
                    <?php if ($presentation['status'] === 'Pending'): ?>
                        <div class="presentation-card">
                            <div class="seller-info">
                                <div>
                                    <h3><?php echo htmlspecialchars($presentation['firstname'] . ' ' . $presentation['lastname']); ?></h3>
                                    <p><i class="fas fa-id-badge"></i> Seller ID: <?php echo htmlspecialchars($presentation['seller_unique_id']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($presentation['contactno']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($presentation['address']); ?></p>
                                </div>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($presentation['product_name']); ?></h4>
                                <div class="sizes-container">
                                    <?php 
                                    if (!empty($presentation['all_sizes'])) {
                                        $sizes = explode('|', $presentation['all_sizes']);
                                        foreach ($sizes as $size) {
                                            $size = preg_replace('/\.00/', '', $size);
                                            echo '<span class="size-tag">' . htmlspecialchars($size) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p>Description: <?php echo htmlspecialchars($presentation['description']); ?></p>
                            </div>
                            <div class="presentation-status">
                                <div class="status-wrapper">
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock"></i> Pending
                                    </span>
                                </div>
                                <small>Presented on: <?php echo date('F j, Y, g:i a', strtotime($presentation['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Scheduled Presentations -->
            <div class="presentations-section">
                <h2>Scheduled Presentations</h2>
                <?php foreach ($presentations as $presentation): ?>
                    <?php if ($presentation['status'] === 'Scheduled' || $presentation['status'] === 'On The Way'): ?>
                        <div class="presentation-card">
                            <div class="seller-info">
                                <div>
                                    <h3><?php echo htmlspecialchars($presentation['firstname'] . ' ' . $presentation['lastname']); ?></h3>
                                    <p><i class="fas fa-id-badge"></i> Seller ID: <?php echo htmlspecialchars($presentation['seller_unique_id']); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($presentation['contactno']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($presentation['address']); ?></p>
                                </div>
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($presentation['product_name']); ?></h4>
                                <div class="sizes-container">
                                    <?php 
                                    if (!empty($presentation['all_sizes'])) {
                                        $sizes = explode('|', $presentation['all_sizes']);
                                        foreach ($sizes as $size) {
                                            $size = preg_replace('/\.00/', '', $size);
                                            echo '<span class="size-tag">' . htmlspecialchars($size) . '</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p>Description: <?php echo htmlspecialchars($presentation['description']); ?></p>
                            </div>
                            <!-- In the Scheduled Presentations section, update the presentation-status div -->
                            <div class="presentation-status">
                                <div class="status-wrapper">
                                    <span class="status-badge <?php echo $presentation['status'] === 'On The Way' ? 'status-ontheway' : 'status-scheduled'; ?>">
                                        <i class="<?php echo $presentation['status'] === 'On The Way' ? 'fas fa-truck' : 'fas fa-calendar-check'; ?>"></i>
                                        <?php echo htmlspecialchars($presentation['status']); ?>
                                    </span>
                                    
                                    <!-- Add View Schedule button -->
                                    <button class="btn-view-schedule" onclick="viewSchedule(
                                        '<?php echo date('F j, Y, g:i a', strtotime($presentation['scheduled_date'])); ?>', 
                                        '<?php echo htmlspecialchars($presentation['firstname'] . ' ' . $presentation['lastname']); ?>', 
                                        '<?php echo htmlspecialchars($presentation['product_name']); ?>', 
                                        '<?php echo htmlspecialchars($presentation['all_sizes']); ?>', 
                                        '<?php echo htmlspecialchars($presentation['quantity']); ?>',
                                        '<?php echo htmlspecialchars($presentation['id']); ?>'
                                    )">
                                        <i class="fas fa-eye"></i> View Schedule
                                    </button>

                                    <?php if (!empty($invoice)): ?>
                                        <a href="supplier_view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn-view-invoice">
                                            <i class="fas fa-file-invoice"></i> View Invoice
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <small>Presented on: <?php echo date('F j, Y, g:i a', strtotime($presentation['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Declined Presentations -->
            <div class="presentations-section">
                <h2>Declined Presentations</h2>
                <?php foreach ($presentations as $presentation): ?>
                    <?php if ($presentation['status'] === 'Declined'): ?>
                        <div class="presentation-card">
                            <!-- Existing presentation card content -->
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add this CSS -->
    <style>
        .presentations-section {
            margin-bottom: 40px;
        }
        
        .presentations-section h2 {
            color: var(--primary-brown);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-brown);
        }
    </style>

    <!-- Add Schedule View Modal -->
    <div id="viewScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Delivery Schedule Details</h2>
            <div class="schedule-details">
                <div class="calendar-icon">
                    <i class="fas fa-calendar-alt fa-3x"></i>
                </div>
                <div class="schedule-info">
                    <p id="scheduleDateTime" class="schedule-date"></p>
                    <div class="delivery-details">
                        <p><strong>Seller:</strong> <span id="sellerName"></span></p>
                        <p><strong>Product:</strong> <span id="productName"></span></p>
                        <p><strong>Sizes:</strong> <span id="productSizes"></span></p>
                        <div class="delivery-status">
                            <p><strong>Status:</strong> <span id="deliveryStatus">Scheduled</span></p>
                            <div id="statusButtons">
                                <button id="changeStatusBtn" class="btn-delivery-status" onclick="updateDeliveryStatus()">
                                    <i class="fas fa-truck"></i> Mark as On The Way
                                </button>
                            </div>
                            <div id="statusLogs" class="status-logs">
                                <h4><i class="fas fa-history"></i> Delivery Updates</h4>
                                <div id="arriveSection" style="margin-bottom: 15px;">
                                    <button id="arriveBtn" class="btn-arrive" onclick="markAsArrived()" data-presentation-id="">
                                        <i class="fas fa-check-circle"></i> Arrive
                                    </button>
                                    <small class="arrive-note">
                                        Click the "Arrive" button when the product has arrived at the location.
                                    </small>
                                </div>
                                <ul id="logsList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .status-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-view-schedule {
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .btn-view-schedule:hover {
            background-color: var(--secondary-brown);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        /* Update the modal-content style */
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* Changed from 15% to 5% to move it up */
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        /* Update the schedule-details style */
        .schedule-details {
            text-align: center;
            padding: 20px; /* Reduced padding */
            color: var(--primary-brown);
            background-color: #fff8dc;
            border-radius: 8px;
            margin-top: 10px; /* Reduced margin */
        }

        /* Update the delivery-details style */
        .delivery-details {
            text-align: left;
            margin-top: 15px; /* Reduced margin */
            padding: 15px;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-height: 60vh; /* Add max height */
            overflow-y: auto; /* Add scroll if content is too long */
        }

        .delivery-details p {
            margin: 10px 0;
            font-size: 1.1em;
        }

        .delivery-details strong {
            color: var(--primary-brown);
            margin-right: 10px;
        }
    </style>

    <script>
        // Update the viewSchedule function
        function viewSchedule(dateTime, sellerName, productName, sizes, quantity, presentationId) {
            document.getElementById('scheduleDateTime').textContent = dateTime;
            document.getElementById('sellerName').textContent = sellerName;
            document.getElementById('productName').textContent = productName;
            
            // Format sizes to be more readable
            const formattedSizes = sizes.split('|').map(size => {
                return size.replace(/×/g, 'x').replace(/\.00/g, '');
            }).join('\n');
            
            document.getElementById('productSizes').innerHTML = formattedSizes.replace(/\n/g, '<br>');
            
            // Check current status and update buttons accordingly
            fetch('get_delivery_status.php?presentation_id=' + presentationId)
                .then(response => response.json())
                .then(data => {
                    const onTheWayBtn = document.getElementById('changeStatusBtn');
                    const arriveBtn = document.getElementById('arriveBtn');
                    const statusElement = document.getElementById('deliveryStatus');
                    
                    if (data.status === 'On The Way') {
                        statusElement.textContent = 'On The Way';
                        onTheWayBtn.style.display = 'none';
                        arriveBtn.style.display = 'flex';
                        document.querySelector('.arrive-note').style.display = 'block';
                        arriveBtn.setAttribute('data-presentation-id', presentationId);
                    } else {
                        statusElement.textContent = data.status;
                        onTheWayBtn.style.display = 'flex';
                        arriveBtn.style.display = 'none';
                        document.querySelector('.arrive-note').style.display = 'none';
                        onTheWayBtn.setAttribute('data-presentation-id', presentationId);
                    }
                });
            
            document.getElementById('viewScheduleModal').style.display = 'block';
        }
         // Close modal when clicking the X
    document.querySelector('.close').onclick = function() {
        document.getElementById('viewScheduleModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('viewScheduleModal')) {
            document.getElementById('viewScheduleModal').style.display = 'none';
        }
    }
    </script>

    <!-- Add additional styles -->
    <style>
        .delivery-details {
            text-align: left;
            margin-top: 20px;
            padding: 15px;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delivery-details p {
            margin: 10px 0;
            font-size: 1.1em;
        }

        .delivery-details strong {
            color: var(--primary-brown);
            margin-right: 10px;
        }
    </style>

   
</body>
</html>

<!-- Add these styles -->
<style>
    .delivery-status {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .btn-delivery-status {
        background-color: var(--primary-brown);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 20px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        transition: all 0.3s ease;
    }

    .btn-delivery-status:disabled {
        background-color: #4CAF50; /* Green color for On The Way status */
        color: white;
        cursor: not-allowed;
        transform: none;
        opacity: 1; /* Keep the button fully visible even when disabled */
    }

    .status-logs {
        margin-top: 20px;
        padding: 15px;
        background-color: #faf6f1;
        border-radius: 8px;
    }

    .status-logs h4 {
        color: var(--primary-brown);
        margin: 0 0 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .status-logs ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .status-logs li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        color: #666;
        font-size: 0.9em;
    }
</style>

<!-- Add this JavaScript function -->
<script>
    function updateDeliveryStatus() {
        const button = document.getElementById('changeStatusBtn');
        const statusElement = document.getElementById('deliveryStatus');
        const presentationId = button.getAttribute('data-presentation-id');
        
        fetch('update_delivery_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'presentation_id=' + presentationId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update status text
                statusElement.textContent = 'On The Way';
                
                // Disable and style the button
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-truck"></i> Delivery On The Way';
                button.style.backgroundColor = '#4CAF50';
                
                // Add status update to logs
                const logsList = document.getElementById('logsList');
                const now = new Date().toLocaleString();
                const logEntry = document.createElement('li');
                logEntry.innerHTML = `<i class="fas fa-truck"></i> Status updated to "On The Way" - ${now}`;
                logsList.insertBefore(logEntry, logsList.firstChild);
                
                // Reload the page after a short delay
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                alert('Error updating status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status');
        });
    }

    function markAsArrived() {
        const button = document.getElementById('arriveBtn');
        const presentationId = button.getAttribute('data-presentation-id');
        
        if (confirm('Mark this delivery as arrived?')) {
            fetch('mark_as_arrived.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'presentation_id=' + presentationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add status update to logs
                    const logsList = document.getElementById('logsList');
                    const now = new Date().toLocaleString();
                    const logEntry = document.createElement('li');
                    logEntry.innerHTML = `<i class="fas fa-check-circle"></i> Delivery has arrived - ${now}`;
                    logsList.insertBefore(logEntry, logsList.firstChild);
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }
    }
</script>
