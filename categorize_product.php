<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a Seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

$presentation_id = isset($_GET['id']) ? $_GET['id'] : null;
$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : null;

if (!$presentation_id || !$item_id) {
    header('Location: presentations.php');
    exit();
}

// Fetch presentation and product details
$stmt = $pdo->prepare("
    SELECT so.*, i.product_name, i.description, i.length, i.width, i.height, i.quantity,
           u.firstname, u.lastname, u.id as supplier_id
    FROM sup_orders so
    JOIN inventory i ON so.item_id = i.id
    JOIN users u ON i.supplier_id = u.id
    WHERE so.id = ? AND so.item_id = ?
");
$stmt->execute([$presentation_id, $item_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Display any messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Inventory - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        body {
            font-family: 'Arial', sans-serif;
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--primary-brown);
            margin-bottom: 20px;
        }

        .product-details {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-brown);
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .btn-submit {
            background-color: var(--primary-brown);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-submit:hover {
            background-color: var(--secondary-brown);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: var(--primary-brown);
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="presentations.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Presentations
        </a>

        <h1>Add to Inventory</h1>

        <div class="product-details">
            <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($product['firstname'] . ' ' . $product['lastname']); ?></p>
            <p><strong>Dimensions:</strong> <?php 
                $length = preg_replace('/\.00/', '', $product['length']);
                $width = preg_replace('/\.00/', '', $product['width']);
                $height = preg_replace('/\.00/', '', $product['height']);
                echo htmlspecialchars($length) . ' × ' . htmlspecialchars($width) . ' × ' . htmlspecialchars($height); 
            ?></p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($product['quantity']); ?></p>
        </div>
        <form method="POST" action="process_inventory.php" enctype="multipart/form-data">
            <input type="hidden" name="seller_id" value="<?php echo $_SESSION['user_id']; ?>">
            <input type="hidden" name="presentation_id" value="<?php echo $presentation_id; ?>">
            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">
            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">

        <!-- After the product details div and before the form -->
        <div class="size-input-section">
            <h3>Input Actual Sizes</h3>
            <div class="size-input-form">
                <div class="input-group">
                    <input type="number" id="length" placeholder="Length" step="1" min="1">
                    <input type="number" id="width" placeholder="Width" step="1" min="1">
                    <input type="number" id="height" placeholder="Height" step="1" min="1">
                    <input type="number" id="quantity" placeholder="Quantity" min="1">
                    <button type="button" onclick="addSize()" class="btn-add-size">
                        <i class="fas fa-plus"></i> Add Size
                    </button>
                </div>
            </div>

            <div class="sizes-list">
                <h4>Added Sizes:</h4>
                <div id="sizesList"></div>
                <input type="hidden" name="sizes_json" id="sizesJson">
            </div>
        </div>

        <!-- Add this CSS -->
        <style>
            .size-input-section {
                margin: 20px 0;
                padding: 15px;
                background-color: #f8f9fa;
                border-radius: 8px;
            }

            .input-group {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
            }

            .input-group input {
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
                width: 100px;
            }

            .btn-add-size {
                background-color: var(--primary-brown);
                color: white;
                border: none;
                padding: 8px 15px;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 10px;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .compute-btn:hover {
                background-color: var(--secondary-brown);
            }

            .computation-result {
                background-color: #f8f9fa;
                padding: 8px;
                border-radius: 4px;
                margin: 5px 0;
                font-size: 0.9em;
                color: var(--primary-brown);
            }
            .sizes-list {
                margin-top: 20px;
            }

            .size-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                background-color: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                margin-bottom: 5px;
            }

            .size-item button {
                background-color: #dc3545;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
            }
            .save-invoice-btn {
                background-color: var(--primary-brown);
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 15px;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .save-invoice-btn:hover {
                background-color: var(--secondary-brown);
            }
        </style>

        <!-- Add this JavaScript before the closing body tag -->
        <script>
            let sizes = [];

            function addSize() {
                const length = document.getElementById('length').value;
                const width = document.getElementById('width').value;
                const height = document.getElementById('height').value;
                const quantity = document.getElementById('quantity').value;

                if (!length || !width || !height || !quantity) {
                    alert('Please fill in all size and quantity fields');
                    return;
                }

                const sizeObj = {
                    length: parseFloat(length),
                    width: parseFloat(width),
                    height: parseFloat(height),
                    quantity: parseInt(quantity)
                };

                sizes.push(sizeObj);
                updateSizesList();
                clearInputs();
            }

            function removeSize(index) {
                sizes.splice(index, 1);
                updateSizesList();
            }

            function updateSizesList() {
                const sizesList = document.getElementById('sizesList');
                const sizesJson = document.getElementById('sizesJson');
                
                sizesList.innerHTML = sizes.map((size, index) => `
                    <div class="size-item">
                        <span>${size.length} × ${size.width} × ${size.height} (${size.quantity} pcs)</span>
                        <button onclick="removeSize(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `).join('');

                sizesJson.value = JSON.stringify(sizes);
            }

            function clearInputs() {
                document.getElementById('length').value = '';
                document.getElementById('width').value = '';
                document.getElementById('height').value = '';
                document.getElementById('quantity').value = '';
            }
        </script>
            <div class="form-group">
                <label for="image_path">Product Image:</label>
                <input type="file" name="image_path" id="image_path" accept="image/*" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Add to Inventory
            </button>
        </form>
        <!-- Add this before the closing container div -->
        <div class="saved-products">
            <h3>Saved Products</h3>
            <div class="saved-products-list">
                <?php
                // Fetch saved products for this presentation
                $stmt = $pdo->prepare("
                    SELECT * FROM product_inventory 
                    WHERE seller_id = ? AND product_name = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$_SESSION['user_id'], $product['product_name']]);
                $saved_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($saved_products): ?>
                    <div class="saved-items-grid">
                        <?php foreach ($saved_products as $item): ?>
                            <div class="saved-item" 
                                data-id="<?php echo htmlspecialchars($item['product_id']); ?>"
                                data-length="<?php echo htmlspecialchars($item['length_feet']); ?>"
                                data-width="<?php echo htmlspecialchars($item['width_feet']); ?>"
                                data-height="<?php echo htmlspecialchars($item['height_feet']); ?>"
                                data-quantity="<?php echo htmlspecialchars($item['quantity']); ?>">
                                <div class="size-info">
                                    <span class="dimensions"><?php 
                                        echo htmlspecialchars($item['length_feet']) . ' × ' . 
                                             htmlspecialchars($item['width_feet']) . ' × ' . 
                                             htmlspecialchars($item['height_feet']); 
                                    ?></span>
                                    <span class="quantity"><?php echo htmlspecialchars($item['quantity']); ?> pcs</span>
                                    <div class="computation-result" id="result_<?php echo htmlspecialchars($item['product_id']); ?>"></div>
                                    <button type="button" class="compute-btn" onclick="computeSize(
                                        <?php echo sprintf('%s,%s,%s,%s,\'%s\'',
                                            $item['length_feet'],
                                            $item['width_feet'],
                                            $item['height_feet'],
                                            $item['quantity'],
                                            $item['product_id']
                                        ); ?>
                                    )">
                                        <i class="fas fa-calculator"></i> Compute
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="price-section" id="price_section" style="display: none;">
                        <input type="number" class="price-input" id="product_price" 
                               placeholder="Enter price" step="0.01" min="0">
                        <button type="button" class="calculate-total-btn" onclick="calculateTotalPrice()">
                            <i class="fas fa-peso-sign"></i> Calculate Total Price
                        </button>
                        <div class="final-total" id="product_final_total"></div>
                    </div>
                <?php else: ?>
                    <p>No saved products yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <style>
            .price-input-container {
                                        margin-top: 10px;
                                        display: flex;
                                        gap: 10px;
                                    }
                                
                                    .price-input {
                                        padding: 8px;
                                        border: 1px solid #ddd;
                                        border-radius: 4px;
                                        flex: 1;
                                    }
                                
                                    .calculate-total-btn {
                                        background-color: var(--primary-brown);
                                        color: white;
                                        border: none;
                                        padding: 8px 15px;
                                        border-radius: 4px;
                                        cursor: pointer;
                                    }
                                
                                    .final-total {
                                        margin-top: 10px;
                                        font-weight: bold;
                                        color: var(--primary-brown);
                                        font-size: 1.1em;
                                    }
            .saved-products {
                margin-top: 30px;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 8px;
            }

            .saved-products h3 {
                color: var(--primary-brown);
                margin-bottom: 15px;
            }

            .saved-items-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .saved-item {
                background-color: white;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                display: flex;
                gap: 10px;
                align-items: center;
                flex-wrap: wrap;
            }

            .price-section .price-input {
                flex: 1;
                min-width: 150px;
            }

            .price-section .final-total {
                width: 100%;
                margin-top: 10px;
                padding: 10px;
                background-color: #f8f9fa;
                border-radius: 4px;
            }
            .send-copy-btn {
                background-color: #28a745;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 15px;
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
            }

            .send-copy-btn:hover {
                background-color: #218838;
            }
        </style>
            <script>
                let volumeTotals = {};
                let computedItems = new Set();

                function computeSize(length, width, height, quantity, itemId) {
                    const volumePerPiece = (length * width * height) / 12;
                    const totalVolume = volumePerPiece * quantity;
                    volumeTotals[itemId] = totalVolume;
                    computedItems.add(itemId);
                    
                    const resultElement = document.getElementById('result_' + itemId);
                    resultElement.innerHTML = `Total: ${totalVolume.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

                    // Check if all items have been computed
                    const totalItems = document.querySelectorAll('.compute-btn').length;
                    if (computedItems.size === totalItems) {
                        document.getElementById('price_section').style.display = 'block';
                        let grandTotal = 0;
                        for (let id in volumeTotals) {
                            grandTotal += volumeTotals[id];
                        }
                        document.getElementById('product_final_total').innerHTML = `
                            <div>Grand Total: ${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} </div>
                            <div>Final Price: ₱${finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <button type="button" class="save-invoice-btn" onclick="saveInvoice(${price}, ${grandTotal}, ${finalPrice})">
                                <i class="fas fa-save"></i> Save Invoice
                            </button>
                        `;
                    }
                }

                function calculateTotalPrice() {
                    const price = parseFloat(document.getElementById('product_price').value);
                    if (!price || price <= 0) {
                        alert('Please enter a valid price');
                        return;
                    }

                    let grandTotal = 0;
                    for (let id in volumeTotals) {
                        grandTotal += volumeTotals[id];
                    }
                    
                    const finalPrice = grandTotal * price;
                    document.getElementById('product_final_total').innerHTML = `
                        <div>Grand Total: ${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} cu.ft</div>
                        <div>Final Price: ₱${finalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                        <button type="button" class="save-invoice-btn" onclick="saveInvoice(${price}, ${grandTotal}, ${finalPrice})">
                            <i class="fas fa-save"></i> Save Invoice
                        </button>
                    `;
                }

                function saveInvoice(pricePerUnit, grandTotalVolume, totalBill) {
                    // Collect all the computed items
                    const items = [];
                    for (let id in volumeTotals) {
                        const itemElement = document.querySelector(`[data-id="${id}"]`);
                        items.push({
                            product_id: id,
                            length_feet: parseFloat(itemElement.dataset.length),
                            width_feet: parseFloat(itemElement.dataset.width),
                            height_feet: parseFloat(itemElement.dataset.height),
                            quantity: parseInt(itemElement.dataset.quantity),
                            total_volume: volumeTotals[id]
                        });
                    }

                    // Send data to server
                    fetch('save_invoice.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            supplier_id: <?php echo $product['supplier_id']; ?>,
                            product_name: '<?php echo addslashes($product['product_name']); ?>',
                            price_per_unit: pricePerUnit,
                            grand_total_volume: grandTotalVolume,
                            total_bill: totalBill,
                            items: items
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Invoice saved successfully!');
                            sendToSupplier(data.invoice_id); // Automatically send invoice
                        } else {
                            alert('Error saving invoice: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error saving invoice');
                    });
                }
 function sendToSupplier(invoiceId) {
                fetch('send_invoice_copy.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        invoice_id: invoiceId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Response:', data); // Log response data
                    if (data.success) {
                        alert('Invoice sent to supplier successfully!');
                        location.reload();
                    } else {
                        alert('Error sending invoice: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error sending invoice');
                });
            }
            </script>
           
</body>
</html>