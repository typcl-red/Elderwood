<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a supplier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Supplier') {
    header('Location: login.php');
    exit();
}

// Get invoice and supplier details
$stmt = $pdo->prepare("
    SELECT i.*, u.firstname, u.lastname, u.contactno, u.address, si.seller_unique_id,
           s.firstname as supplier_firstname, s.lastname as supplier_lastname,
           ii.length_feet, ii.width_feet, ii.height_feet, ii.quantity, ii.total_volume
    FROM invoices i
    JOIN users u ON i.seller_id = u.id
    JOIN seller_ids si ON u.id = si.user_id
    JOIN users s ON i.supplier_id = s.id
    JOIN invoice_items ii ON i.invoice_id = ii.invoice_id
    WHERE i.invoice_id = ? AND i.sent_to_supplier = 1
");
$stmt->execute([$_GET['id']]);
$items = $stmt->fetchAll();
$invoice = $items[0]; // First row contains all the common invoice data
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice View - ElderWood</title>
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
        }

        .invoice-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .invoice-header h1 {
            color: var(--primary-brown);
            margin: 0;
        }

        .invoice-details {
            margin-bottom: 30px;
        }

        .invoice-details p {
            margin: 5px 0;
            color: #333;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .invoice-table th, .invoice-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .invoice-table th {
            background-color: var(--light-brown);
            color: var(--primary-brown);
        }

        .total-section {
            text-align: right;
            margin-top: 20px;
        }

        .total-section p {
            margin: 5px 0;
            font-size: 1.1em;
        }

        .print-button {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-print {
            background-color: var(--primary-brown);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            background-color: var(--secondary-brown);
        }

        @media print {
            body {
                background: white;
            }
            .invoice-container {
                box-shadow: none;
                margin: 0;
                padding: 20px;
            }
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div style="margin-bottom: 20px;">
            <a href="javascript:history.back()" style="text-decoration: none; color: #8B4513; font-weight: bold;">
                ← Back
            </a>
        </div>

        <h1 style="text-align: center; color: #8B4513;">Invoice</h1>
        <p style="text-align: center;">Date: <?php echo date('F j, Y', strtotime($invoice['created_at'])); ?></p>

        <hr style="border: 1px solid #8B4513; margin: 20px 0;">

        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div style="flex: 1;">
                <h3>Supplier Details</h3>
                <p>Name: <?php echo htmlspecialchars($invoice['supplier_firstname'] . ' ' . $invoice['supplier_lastname']); ?></p>
            </div>
            <div style="flex: 1;">
                <h3>Seller Details</h3>
                <p>Name: <?php echo htmlspecialchars($invoice['firstname'] . ' ' . $invoice['lastname']); ?></p>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <h3>Product Details</h3>
            <p>Product Name: <?php echo htmlspecialchars($invoice['product_name']); ?></p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <th style="padding: 8px; text-align: left; background-color: #DEB887; color: #8B4513;">Dimensions (L × W × H)</th>
                <th style="padding: 8px; text-align: left; background-color: #DEB887; color: #8B4513;">Quantity</th>
                <th style="padding: 8px; text-align: left; background-color: #DEB887; color: #8B4513;">Total</th>
                <th style="padding: 8px; text-align: left; background-color: #DEB887; color: #8B4513;">Total</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td style="padding: 8px;"><?php echo (int)$item['length_feet'] . ' × ' . (int)$item['width_feet'] . ' × ' . (int)$item['height_feet'] . ' feet'; ?></td>
                <td style="padding: 8px;"><?php echo $item['quantity'] . ' pcs'; ?></td>
                <td style="padding: 8px;"><?php echo rtrim(rtrim(number_format($item['total_volume'], 2), '0'), '.'); ?></td>
                <td style="padding: 8px;">₱<?php echo number_format($item['total_volume'] * $invoice['price_per_unit'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <hr style="border: 1px solid #8B4513; margin: 20px 0;">

        <div style="text-align: right; margin-top: 20px;">
            <p><strong>Grand Total Volume:</strong> <?php echo rtrim(rtrim(number_format($invoice['grand_total_volume'], 2), '0'), '.'); ?></p>
            <p><strong>Price per Unit:</strong> ₱<?php echo rtrim(rtrim(number_format($invoice['price_per_unit'], 2), '0'), '.'); ?></p>
            <p style="font-weight: bold; color: #8B4513; font-size: 1.2em;">Total Bill: ₱<?php echo number_format($invoice['total_bill'], 2); ?></p>
        </div>

        <?php if ($invoice['payment_proof']): ?>
        <div style="margin-top: 20px;">
            <h3>Proof of Payment</h3>
            <img src="<?php echo htmlspecialchars($invoice['payment_proof']); ?>" alt="Payment Proof" style="max-width: 400px; display: block; margin: 10px 0;">
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="background-color: #8B4513; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">Print Invoice</button>
        </div>
    </div>
</body>
</html>