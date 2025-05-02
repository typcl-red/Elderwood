<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    header('Location: login.php');
    exit();
}

// Get supplier's details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();

// Get inventory items
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE supplier_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$inventoryItems = $stmt->fetchAll();

// Group items by product name
$groupedItems = [];
foreach ($inventoryItems as $item) {
    $productName = $item['product_name'];
    if (!isset($groupedItems[$productName])) {
        $groupedItems[$productName] = [];
    }
    $groupedItems[$productName][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ElderWood</title>
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
            font-family: Arial, sans-serif;
            background-color: var(--bg-brown);
            display: flex;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }

        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .inventory-header h1 {
            color: var(--primary-brown);
        }

        .add-item-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .inventory-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .inventory-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .inventory-table th, 
        .inventory-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .inventory-table th {
            background-color: var(--light-brown);
            color: var(--primary-brown);
            font-weight: bold;
        }

        .inventory-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .edit-btn, .delete-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            background-color: #4CAF50;
            color: white;
        }

        .edit-btn {
            background-color: var(--light-brown);
            color: var(--primary-brown);
        }

        .delete-btn {
            background-color: #ff4444;
            color: white;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .status-in-stock {
            background-color: #28a745;
            color: white;
        }

        .status-low-stock {
            background-color: #ffc107;
            color: black;
        }

        .status-out-of-stock {
            background-color: #dc3545;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--primary-brown);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn {
            background-color: var(--primary-brown);
            color: white;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }
        .dimensions-group .dimensions-inputs {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
            
                .dimensions-inputs input {
                    width: 100px;
                }
            
                .dimensions-inputs span {
                    color: var(--primary-brown);
                    font-weight: bold;
                }
                .dimension-input-group {
                            display: flex;
                            flex-direction: column;
                            gap: 5px;
                        }
                        
                        .dimension-input-group input {
                            width: 100px;
                        }
                        
                        .dimension-input-group input[type="number"] {
                            text-align: center;
                        }
    </style>
</head>
<body>
    <?php include 'includes/supplier_sidebar.php'; ?>

    <div class="main-content">
        <div class="inventory-header">
            <h1>Inventory Management</h1>
            <button class="add-item-btn" onclick="showAddItemModal()">
                <i class="fas fa-plus"></i> Add New Item
            </button>
        </div>

        <div class="inventory-table">
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Dimensions (L×W×H)</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groupedItems as $productName => $items): ?>
                    <tr>
                        <td rowspan="<?php echo count($items); ?>"><?php echo htmlspecialchars($productName); ?></td>
                        <?php $first = true; foreach ($items as $item): ?>
                        <?php if (!$first): ?>
                        <tr>
                        <?php endif; ?>
                            <td><?php echo (int)htmlspecialchars($item['length']) . ' × ' . 
                                   (int)htmlspecialchars($item['width']) . ' × ' . 
                                   (int)htmlspecialchars($item['height']); ?> cm</td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $item['status'])); ?>">
                                    <?php echo htmlspecialchars($item['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($item['updated_at'])); ?></td>
                            <td class="action-buttons">
                                <button class="edit-btn" onclick="editItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-btn" onclick="deleteItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php if ($first): ?>
                                <button class="present-btn" onclick="presentItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        <?php if (!$first): ?>
                        </tr>
                        <?php endif; ?>
                        <?php $first = false; endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Item Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Item</h2>
            <form id="itemForm">
                <input type="hidden" id="itemId" name="itemId">
                <div class="form-group">
                    <label for="productName">Product Name</label>
                    <input type="text" id="productName" name="productName" required>
                </div>
                <div class="form-group dimensions-group">
                    <label>Dimensions (cm)</label>
                    <div class="dimensions-inputs">
                        <input type="number" id="length" name="length" step="1" placeholder="Length" required>
                        <span>×</span>
                        <input type="number" id="width" name="width" step="1" placeholder="Width" required>
                        <span>×</span>
                        <input type="number" id="height" name="height" step="1" placeholder="Height" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="save-btn">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddItemModal() {
            document.getElementById('modalTitle').textContent = 'Add New Item';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('itemModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('itemModal').style.display = 'none';
        }

        function editItem(itemId) {
            fetch(`get_inventory_item.php?id=${itemId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').textContent = 'Edit Item';
                    document.getElementById('itemId').value = data.id;
                    document.getElementById('productName').value = data.product_name;
                    document.getElementById('quantity').value = data.quantity;
                    document.getElementById('length').value = Math.round(data.length);
                    document.getElementById('width').value = Math.round(data.width);
                    document.getElementById('height').value = Math.round(data.height);
                    document.getElementById('description').value = data.description;
                    document.getElementById('status').value = data.status;
                    document.getElementById('itemModal').style.display = 'block';
                });
        }

        function deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                fetch('delete_inventory_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: itemId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting item');
                    }
                });
            }
        }

        function presentItem(itemId) {
            window.location.href = `present_item.php?id=${itemId}`;
        }

        document.getElementById('itemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('save_inventory_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Error saving item');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('itemModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>