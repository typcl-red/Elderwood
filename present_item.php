<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier' || !isset($_GET['id'])) {
    header('Location: supplier_inventory.php');
    exit();
}

// Get the item details and all sizes for this product
$stmt = $pdo->prepare("
    SELECT * FROM inventory 
    WHERE product_name = (SELECT product_name FROM inventory WHERE id = ?) 
    AND supplier_id = ?
    ORDER BY length, width, height");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$items = $stmt->fetchAll();
$item = $items[0]; // Keep the first item for other details

if (!$item) {
    header('Location: supplier_inventory.php');
    exit();
}

// Get list of sellers
$stmt = $pdo->prepare("SELECT id, username, profile_photo, address, contactno FROM users WHERE role = 'Seller' ORDER BY username");
$stmt->execute();
$sellers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Present Item - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing styles from supplier_inventory.php */
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-brown);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .item-details {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .sellers-list {
            display: grid;
            gap: 15px;
        }

        .seller-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .seller-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .seller-card.selected {
            border-color: var(--primary-brown);
            background-color: var(--bg-brown);
        }

        .seller-profile-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .seller-info {
            flex: 1;
        }

        .seller-name {
            font-weight: bold;
            color: var(--primary-brown);
        }

        .seller-details {
            font-size: 0.9em;
            color: #666;
        }

        .send-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: none;
        }

        .send-btn.visible {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Present Item to Sellers</h1>
            <a href="supplier_inventory.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <div class="item-details">
            <h2><?php echo htmlspecialchars($item['product_name']); ?></h2>
            <p>Dimensions Available:</p>
            <ul>
                <?php foreach ($items as $size): ?>
                    <li><?php echo (int)$size['length'] . ' × ' . (int)$size['width'] . ' × ' . (int)$size['height']; ?> cm 
                        (Quantity: <?php echo htmlspecialchars($size['quantity']); ?>)</li>
                <?php endforeach; ?>
            </ul>
            <p>Description: <?php echo htmlspecialchars($item['description']); ?></p>
        </div>

        <h3>Select a Seller to Present:</h3>
        <div class="sellers-list">
            <?php foreach ($sellers as $seller): ?>
                <div class="seller-card" onclick="selectSeller(this)" 
                    data-seller-id="<?php echo (int)$seller['id']; ?>"
                    data-item-id="<?php echo (int)$_GET['id']; ?>">
                    <img src="<?php echo !empty($seller['profile_photo']) ? htmlspecialchars($seller['profile_photo']) : 'assets/images/default-profile.jpg'; ?>" 
                        alt="Profile Photo" 
                        class="seller-profile-photo"
                        onerror="this.src='assets/images/default-profile.jpg'">
                    <div class="seller-info">
                        <div class="seller-name"><?php echo htmlspecialchars($seller['username']); ?></div>
                        <div class="seller-details">
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($seller['address']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($seller['contactno']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="send-button-container">
            <button id="sendButton" class="send-btn" onclick="presentToSeller(event)">
                <i class="fas fa-paper-plane"></i> Send Presentation
            </button>
        </div>
    </div>

    <script>
        function selectSeller(card) {
            // Remove selection from all cards
            document.querySelectorAll('.seller-card').forEach(c => c.classList.remove('selected'));
            // Add selection to clicked card
            card.classList.add('selected');
            // Show send button
            document.getElementById('sendButton').classList.add('visible');
        }

        function presentToSeller(event) {
            const selectedCard = document.querySelector('.seller-card.selected');
            if (!selectedCard) {
                alert('Please select a seller first');
                return;
            }

            const sellerId = selectedCard.getAttribute('data-seller-id');
            const itemId = selectedCard.getAttribute('data-item-id');

            if (confirm('Present this item to the selected seller?')) {
                fetch('send_presentation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        seller_id: sellerId,
                        item_id: itemId
                    })
                })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid server response');
                    }
                })
                .then(data => {
                    if (data.success) {
                        alert('Item presented successfully!');
                        window.location.href = 'supplier_inventory.php';
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error occurred: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>