<?php
session_start();
require_once 'database/config.php'; // Make sure this includes your database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the User class
require_once 'classes/User.php';

$user = new User($pdo);
$userDetails = $user->getUserById($_SESSION['user_id']);

// Get all orders for the current buyer
$stmt = $pdo->prepare("
    SELECT 
        ol.order_id,
        ol.product_name,
        ol.length_feet,
        ol.width_feet,
        ol.height_feet,
        ol.quantity,
        ol.status,
        ol.created_at,
        r.receipt_number,
        r.id as receipt_id
    FROM order_list ol
    LEFT JOIN receipt r ON ol.order_id = r.order_id
    WHERE ol.buyer_id = :user_id
    ORDER BY ol.created_at DESC
");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order List - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
            --sidebar-width: 250px;
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
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background-color: var(--light-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-image i {
            font-size: 64px;
            color: var(--primary-brown);
        }

        .change-photo-btn {
            background-color: var(--secondary-brown);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin: 10px auto;
            width: fit-content;
            font-size: 0.9rem;
        }

        .change-photo-btn:hover {
            background-color: var(--primary-brown);
        }

        .profile-details h3 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .profile-details p {
            font-size: 0.9rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .nav-links {
            list-style: none;
            padding: 0;
        }

        .nav-links li {
            padding: 15px 25px;
            transition: background-color 0.3s;
            cursor: pointer;
            position: relative;
        }

        .nav-links li:hover {
            background-color: var(--secondary-brown);
        }

        .nav-links a, .nav-links button {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            width: 100%;
            font-size: 1rem;
            cursor: pointer;
        }

        .nav-links i {
            width: 20px;
        }

        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .page-title {
            color: var(--primary-brown);
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .orders-container {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px;
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
        }

        .orders-table th, 
        .orders-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .orders-table th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #333;
        }

        .orders-table td {
            vertical-align: middle;
        }

        .product-name {
            color: #333;
            font-weight: 500;
        }

        .size-info {
            color: #555;
            white-space: nowrap;
        }

        .quantity-info {
            color: #333;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.9em;
            background-color: #fff3cd;
            color: #856404;
            display: inline-block;
        }
        .status-badge[data-status="Product Received"] {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .date-search-cell {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-right: 10px;
        }

        .order-search-icon {
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff3cd;
            color: #8B4513;
            border-radius: 50%;
            transition: all 0.3s ease;
            border: 1px solid #8B4513;
        }

        .order-search-icon:hover {
            background-color: #8B4513;
            color: #fff;
            transform: scale(1.1);
        }

        .date-search-cell span {
            color: #333;
            font-size: 0.95em;
        }

        .search-icon {
            color: #8B4513;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            background-color: #fff3cd;
        }

        .search-icon:hover {
            background-color: #8B4513;
            color: white;
            transform: scale(1.1);
        }

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

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
            z-index: 1;
        }

        .close-modal:hover {
            color: #333;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .search-container {
            position: relative;
            margin-bottom: 20px;
        }

        .search-icon {
            cursor: pointer;
            padding: 10px;
            background-color: #8B4513;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .search-results-container {
            position: fixed;
            top: 0;
            right: 0;
            width: 400px;
            height: 100vh;
            background-color: white;
            box-shadow: -2px 0 5px rgba(0,0,0,0.1);
            z-index: 999;
            transition: transform 0.3s ease;
        }

        .search-header {
            padding: 20px;
            background-color: #f5f5f5;
            border-bottom: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #searchInput {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .close-search {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .search-results {
            padding: 20px;
            overflow-y: auto;
            height: calc(100vh - 80px);
        }

        .product-result {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .product-result:hover {
            background-color: #f9f9f9;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }

        .product-info {
            flex: 1;
        }

        .product-info h4 {
            margin: 0 0 5px 0;
            color: #8B4513;
        }

        .seller-name {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .product-details {
            font-size: 0.9em;
            color: #444;
        }

        .product-price {
            font-weight: bold;
            color: #8B4513;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .search-results-container {
                width: 100%;
            }
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-icon {
            cursor: pointer;
            padding: 8px;
            background-color: #8B4513;
            color: white;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.3s ease;
        }

        .search-icon:hover {
            background-color: #A0522D;
        }

        .related-products-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .related-products-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .related-product-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            position: relative;
            min-height: 180px;
        }

        .related-product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .related-product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            padding-bottom: 50px;
        }

        .seller-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .product-specs {
            font-size: 0.9em;
            color: #444;
            margin-bottom: 10px;
        }

        .product-price {
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 15px;
        }

        .order-button {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: absolute;
            bottom: 10px;
            right: 0;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .order-button:hover {
            background-color: #A0522D;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .order-button:active {
            transform: translateY(0);
            box-shadow: none;
        }

        @media (max-width: 768px) {
            .related-products-content {
                width: 95%;
                max-height: 90vh;
            }
        }

        .product-image-container {
            position: relative;
            width: 100%;
            height: 200px;
            margin-bottom: 15px;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .search-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(139, 69, 19, 0.9);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .product-image-container:hover .search-icon {
            opacity: 1;
        }

        .search-icon:hover {
            background-color: rgba(139, 69, 19, 1);
            transform: scale(1.1);
        }

        .date-search-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-icon {
            color: #8B4513;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff3cd;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .search-icon:hover {
            background-color: #8B4513;
            color: white;
            transform: scale(1.1);
        }

        .product-detail-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .order-details-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .product-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .product-info-details {
            margin-top: 10px;
        }

        .order-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .quantity-control button {
            width: 35px;
            height: 35px;
            border: none;
            background-color: #8B4513;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.2em;
        }

        .quantity-control input {
            width: 60px;
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
        }

        .price-summary {
            background-color: #f8f8f8;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .price-summary p {
            margin: 8px 0;
            font-size: 1.1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-summary .total-price {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
            font-size: 1.3em;
            font-weight: bold;
            color: #8B4513;
        }

        .price-summary span {
            font-weight: 600;
        }

        .add-to-cart-btn {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background-color 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: #A0522D;
        }

        #sizeSelect {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1em;
            background-color: white;
            cursor: pointer;
        }

        .receipt-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #ff4444;
            border-radius: 50%;
            margin-left: 8px;
            vertical-align: middle;
        }

        .view-receipt-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .view-receipt-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .view-receipt-btn i {
            font-size: 1em;
        }

        /* Receipt Modal Styles */
        .receipt-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .receipt-modal-content {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between; /* Ensure content is spaced */
        height: auto; /* Adjust height as needed */
        }
        .receipt-details {
            text-align: center;
        }

        .receipt-details h2 {
            color: #000;
            margin-bottom: 5px;
            font-size: 1.3em;
            font-weight: bold;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
            font-size: 0.9em;
        }

        .receipt-info {
            text-align: left;
            margin: 30px 0;
            padding: 0 15px;
        }

        .receipt-row {
            margin: 15px 0;
        }

        .receipt-row p {
            margin: 0;
            font-size: 1em;
            color: #000;
        }

        .receipt-row strong {
            font-weight: bold;
            display: inline-block;
            width: 80px;
        }

        .receipt-total {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
            color: #663300;
            font-weight: bold;
        }

        .print-receipt-btn {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: auto;
            margin: 20px auto 0;
        }

        .cart-count {
            background-color: #ff4444;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.8em;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .nav-links li {
            position: relative;
        }

        .order-now-btn {
            background-color: #8B4513;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-now-btn:hover {
            background-color: #A0522D;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .order-now-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-now-btn i {
            font-size: 1.2em;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(-20px);
            }
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .delete-btn, .receive-btn {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
            transition: all 0.3s ease;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .receive-btn {
            background-color: #28a745;
            color: white;
        }
        /* Add styles for disabled state */
        .delete-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .save-receipt-btn {
        background-color: #17a2b8;
        color: white;
        border: none;
        padding: 3px 6px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        position: absolute; /* Position the button absolutely */
        bottom: 3px; /* Align it to the bottom */
        left: 20px; /* Align it to the left */
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: background-color 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .save-receipt-btn:hover {
        background-color: #138496;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .pay-product-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 3px 6px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        position: absolute;
        bottom: 3px;
        right: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: background-color 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .pay-product-btn:hover {
        background-color: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .receipt-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding: 0 20px;
    }

    .save-receipt-btn, .pay-product-btn {
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }

    .save-receipt-btn {
        background-color: #17a2b8;
        color: white;
        border: none;
    }

    .pay-product-btn {
        background-color: #28a745;
        color: white;
        border: none;
    }

    .save-receipt-btn:hover, .pay-product-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;  /* Add this to prevent text wrapping */
            min-width: auto;  /* Remove fixed min-width */
            text-align: center;
        }

        /* Status-specific colors remain the same */
        .status-badge[data-status="Ready for Pick Up"] {
            background-color: #e8d5f9;
            color: #6a1b9a;
        }

        .status-badge[data-status="Product Received"] {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge[data-status="Cancelled"] {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-badge[data-status="Completed"] {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
                <button class="change-photo-btn" onclick="showProfileUploadModal()">
                    <i class="fas fa-camera"></i> Change Profile Photo
                </button>
            </div>
        </div>

        <ul class="nav-links">
            <li>
                <a href="buyer_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="order_list.php">
                    <i class="fas fa-list-alt"></i>
                    <span>Order List</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1 class="page-title">My Orders</h1>
        
        <div class="search-container">
            <div class="search-icon" onclick="toggleSearch()">
                <i class="fas fa-search"></i>
            </div>
            
            <!-- Search Results Container -->
            <div id="searchResultsContainer" class="search-results-container" style="display: none;">
                <div class="search-header">
                    <input type="text" id="searchInput" placeholder="Search products..." oninput="searchProducts()">
                    <button class="close-search" onclick="toggleSearch()">×</button>
                </div>
                <div id="searchResults" class="search-results">
                    <!-- Results will be populated here -->
                </div>
            </div>
        </div>

        <div class="orders-container">
            <h1>My Orders</h1>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th style="width: 20%">Product Name</th>
                        <th style="width: 20%">Size</th>
                        <th style="width: 10%">Quantity</th>
                        <th style="width: 15%">Status</th>
                        <th style="width: 20%">Order Date</th>
                        <th style="width: 15%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                        <tr>
                            <td class="product-name">
                                <?php echo htmlspecialchars($order['product_name']); ?>
                                <?php if (!empty($order['receipt_path'])): ?>
                                    <span class="receipt-indicator"></span>
                                <?php endif; ?>
                            </td>
                            <td class="size-info">
                                L: <?php echo htmlspecialchars($order['length_feet']); ?>' x 
                                W: <?php echo htmlspecialchars($order['width_feet']); ?>' x 
                                H: <?php echo htmlspecialchars($order['height_feet']); ?>'
                            </td>
                            <td class="quantity-info">
                                <?php echo htmlspecialchars($order['quantity']); ?> pieces
                            </td>
                            <td>
                            <span class="status-badge" data-status="<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                            <td class="date-search-cell">
    <span>
        <?php 
        $date = new DateTime($order['created_at']);
        echo $date->format('Y-m-d H:i:s'); 
        ?>
    </span>
    <?php if (!empty($order['receipt_id'])): ?>
        <button class="view-receipt-btn" onclick="viewReceiptDetails(<?php echo $order['receipt_id']; ?>)">
            <i class="fas fa-file-invoice"></i> View Receipt
        </button>
    <?php elseif ($order['status'] !== 'Tallied'): ?>
        <i class="fas fa-search order-search-icon" 
           title="Search related products"
           onclick="showRelatedProducts(
               '<?php echo htmlspecialchars($order['product_name']); ?>', 
               '<?php echo htmlspecialchars($order['length_feet']); ?>',
               '<?php echo htmlspecialchars($order['width_feet']); ?>',
               '<?php echo htmlspecialchars($order['height_feet']); ?>'
           )">
        </i>
    <?php endif; ?>
</td>
                            <td>
                            <div class="action-buttons">
                                    <?php if ($order['status'] === 'Ready for Pick Up'): ?>
                                        <button class="receive-btn" onclick="markAsReceived(<?php echo $order['order_id']; ?>)">
                                            <i class="fas fa-check-circle"></i> Received
                                        </button>
                                    <?php endif; ?>
                                    <button class="delete-btn" onclick="deleteOrder(event, <?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Profile Photo Upload Modal -->
    <div id="profileUploadModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeProfileUploadModal()">&times;</span>
            <h2>Change Profile Photo</h2>
            <form id="profilePhotoForm" onsubmit="uploadProfilePhoto(event)">
                <div class="form-group">
                    <label for="profilePhoto">Choose Photo</label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" required>
                </div>
                <button type="submit" class="submit-btn">Upload Photo</button>
            </form>
        </div>
    </div>

    <!-- Add this related products container -->
    <div id="relatedProductsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeRelatedProducts()">&times;</span>
            <h3>Related Products</h3>
            <div id="relatedProductsList">
                <!-- Results will be populated here -->
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeOrderDetails()">&times;</span>
            <h3>Order Details</h3>
            <div class="order-details-container">
                <div class="product-preview">
                    <img id="orderProductImage" src="" alt="Product Image" class="product-detail-image">
                    <div class="product-info-details">
                        <h4 id="orderProductName"></h4>
                        <p id="orderSellerName" class="seller-info"></p>
                    </div>
                </div>
                <div class="order-form">
                    <div class="form-group">
                        <label for="sizeSelect">Select Size:</label>
                        <select id="sizeSelect" onchange="updatePrice()">
                            <!-- Sizes will be populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantityInput">Quantity:</label>
                        <div class="quantity-control">
                            <button onclick="adjustQuantity(-1)">-</button>
                            <input type="number" id="quantityInput" value="1" min="1" onchange="updatePrice()">
                            <button onclick="adjustQuantity(1)">+</button>
                        </div>
                    </div>
                    <div class="price-summary">
                        <p>Price per piece: <span id="pricePerPiece">₱0.00</span></p>
                        <p>Quantity: <span id="summaryQuantity">1</span> piece(s)</p>
                        <p class="total-price">Total Price: <span id="totalPrice">₱0.00</span></p>
                    </div>
                    <button class="order-now-btn" onclick="placeOrder()">
                        <i class="fas fa-check"></i>
                        Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="receipt-modal">
        <div class="receipt-modal-content">
            <span class="close-modal" onclick="closeReceiptModal()">&times;</span>
            <img id="receiptImage" class="receipt-image" src="" alt="Receipt">
        </div>
    </div>

    <script>
        function showProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'block';
        }

        function closeProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'none';
        }

        function uploadProfilePhoto(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            fetch('update_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile photo updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating profile photo: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error uploading photo: ' + error.message);
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        function toggleSearch() {
            const container = document.getElementById('searchResultsContainer');
            const currentDisplay = container.style.display;
            container.style.display = currentDisplay === 'none' ? 'block' : 'none';
            
            if (currentDisplay === 'none') {
                document.getElementById('searchInput').focus();
            }
        }

        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            if (searchTerm.length < 2) {
                document.getElementById('searchResults').innerHTML = '<p>Please enter at least 2 characters to search</p>';
                return;
            }

            // Show loading indicator
            document.getElementById('searchResults').innerHTML = '<p>Searching...</p>';

            // Fetch search results
            fetch('search_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'search=' + encodeURIComponent(searchTerm)
            })
            .then(response => response.json())
            .then(data => {
                const resultsContainer = document.getElementById('searchResults');
                if (data.length === 0) {
                    resultsContainer.innerHTML = '<p>No products found</p>';
                    return;
                }

                resultsContainer.innerHTML = data.map(product => `
                    <div class="product-result">
                        <img src="${product.image_path || 'images/default-product.jpg'}" alt="${product.product_name}" class="product-image">
                        <div class="product-info">
                            <h4>${product.product_name}</h4>
                            <div class="seller-info">
                                Seller: ${product.seller_name}
                            </div>
                            <div class="product-specs">
                                Size: L: ${product.length_feet}' x W: ${product.width_feet}' x H: ${product.height_feet}'
                                <br>
                                Quantity: ${product.quantity} pcs
                            </div>
                            <div class="product-price">
                                ₱${product.amount}
                            </div>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error:', error);
                resultsContainer.innerHTML = '<p class="error">Error loading search results</p>';
            });
        }

        function showRelatedProducts(productName, length, width, height) {
            console.log('Showing related products for:', { productName, length, width, height });
            
            const modal = document.getElementById('relatedProductsModal');
            const productsList = document.getElementById('relatedProductsList');
            
            // Show modal and loading state
            modal.style.display = 'block';
            productsList.innerHTML = '<div class="loading">Loading related products...</div>';
            
            // Create FormData
            const formData = new FormData();
            formData.append('product_name', productName);
            formData.append('length', length);
            formData.append('width', width);
            formData.append('height', height);

            // Fetch related products
            fetch('get_related_products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Received data:', data);
                
                if (!data.success) {
                    productsList.innerHTML = `<p class="error">${data.message}</p>`;
                    return;
                }

                if (!data.data || data.data.length === 0) {
                    productsList.innerHTML = '<p class="no-results">No related products found</p>';
                    return;
                }

                // Display the related products
                productsList.innerHTML = data.data.map(product => `
                    <div class="related-product-item">
                        <img src="${product.image_path || 'images/default-product.jpg'}" 
                             alt="${product.product_name}" 
                             class="related-product-image"
                             onerror="this.src='images/default-product.jpg'">
                        <div class="related-product-info">
                            <h4>${product.product_name}</h4>
                            <div class="seller-info">
                                Seller: ${product.seller_name}
                            </div>
                            <div class="product-specs">
                                Size: L: ${product.length_feet}' x W: ${product.width_feet}' x H: ${product.height_feet || 0}'
                                <br>
                                Quantity: ${product.quantity} pcs
                            </div>
                            <div class="product-price">
                                Price per sq.ft: ₱${product.price_per_sqft}
                            </div>
                            <button class="order-button" onclick="showOrderDetails(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                                <i class="fas fa-shopping-cart"></i>
                                Checkout
                            </button>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error:', error);
                productsList.innerHTML = '<p class="error">Error loading related products</p>';
            });
        }

        function closeRelatedProducts() {
            document.getElementById('relatedProductsModal').style.display = 'none';
        }

        // Add event listener for clicking outside the modal
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('relatedProductsModal');
            if (event.target === modal) {
                closeRelatedProducts();
            }
        });

        let currentProduct = null;

        function showOrderDetails(product) {
            currentProduct = product;
            const modal = document.getElementById('orderDetailsModal');
            
            // Set product details
            document.getElementById('orderProductImage').src = product.image_path || 'images/default-product.jpg';
            document.getElementById('orderProductName').textContent = product.product_name;
            document.getElementById('orderSellerName').textContent = `Seller: ${product.seller_name}`;
            
            // Format price with commas and 2 decimal places
            const formattedPrice = parseFloat(product.price_per_sqft).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('pricePerPiece').textContent = `₱${formattedPrice}`;
            
            // Populate size dropdown
            const sizeSelect = document.getElementById('sizeSelect');
            sizeSelect.innerHTML = product.available_sizes.map(size => `
                <option value="${size.length_feet},${size.width_feet},${size.height_feet}">
                    L: ${size.length_feet}' x W: ${size.width_feet}' x H: ${size.height_feet}'
                    (${size.quantity} available)
                </option>
            `).join('');
            
            // Reset quantity
            document.getElementById('quantityInput').value = 1;
            document.getElementById('summaryQuantity').textContent = '1';
            
            // Update initial price
            updatePrice();
            
            // Show modal
            modal.style.display = 'block';
        }

        function adjustQuantity(change) {
            const input = document.getElementById('quantityInput');
            const newValue = parseInt(input.value) + change;
            if (newValue >= 1) {
                input.value = newValue;
                document.getElementById('summaryQuantity').textContent = newValue;
                updatePrice();
            }
        }

        function updatePrice() {
            if (!currentProduct) return;
            
            const quantity = parseInt(document.getElementById('quantityInput').value);
            const pricePerPiece = parseFloat(currentProduct.price_per_sqft);
            const totalPrice = quantity * pricePerPiece;
            
            // Format total price with commas and 2 decimal places
            const formattedTotalPrice = totalPrice.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            
            document.getElementById('totalPrice').textContent = `₱${formattedTotalPrice}`;
        }

        function placeOrder() {
            if (!currentProduct) return;
            
            const [length, width, height] = document.getElementById('sizeSelect').value.split(',');
            const quantity = parseInt(document.getElementById('quantityInput').value);
            
            // Show loading state
            const orderButton = document.querySelector('.order-now-btn');
            const originalButtonText = orderButton.innerHTML;
            orderButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            orderButton.disabled = true;
            
            const orderData = {
                product_name: currentProduct.product_name,
                length_feet: parseFloat(length),
                width_feet: parseFloat(width),
                height_feet: parseFloat(height),
                quantity: quantity
            };

            // Send order to server
            fetch('place_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully! The seller will be notified.');
                    closeOrderDetails();
                    location.reload(); // Reload to show the new order in the list
                } else {
                    alert('Error placing order: ' + data.message);
                    // Reset button state
                    orderButton.innerHTML = originalButtonText;
                    orderButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order. Please try again.');
                // Reset button state
                orderButton.innerHTML = originalButtonText;
                orderButton.disabled = false;
            });
        }

        function closeOrderDetails() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        }

        function deleteOrder(event, orderId) {
            event.preventDefault(); // Prevent any default button behavior
            
            if (!confirm('Are you sure you want to delete this order?')) {
                return;
            }

            // Find and disable the button
            const button = event.target.closest('.delete-btn');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            fetch('delete_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from the table
                    const row = button.closest('tr');
                    row.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        row.remove();
                    }, 300);
                } else {
                    alert('Error deleting order: ' + data.message);
                    // Reset button state
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-trash"></i> Delete';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting order. Please try again.');
                // Reset button state
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash"></i> Delete';
            });
        }
        function payProduct(receiptId) {
    // Redirect to payment options page
    window.location.href = `payment_options.php?receipt_id=${receiptId}`;
}
function viewReceiptDetails(orderId) {
    console.log('Viewing receipt for order ID:', orderId); // Add this debug line
    // ... rest of your code
}

function viewReceiptDetails(receiptId) {
    // Show loading state in modal
    const modal = document.getElementById('receiptModal');
    modal.style.display = 'block';
    const modalContent = document.querySelector('.receipt-modal-content');
    modalContent.innerHTML = '<div class="loading">Loading receipt details...</div>';
    
    // Fetch receipt details
    fetch(`get_receipt_details.php?receipt_id=${receiptId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                modalContent.innerHTML = `
                    <span class="close-modal" onclick="closeReceiptModal()">&times;</span>
                    <div class="receipt-details">
                        <h2>Official Receipt</h2>
                        <div class="receipt-header">
                            <p>Receipt #: ${data.receipt.receipt_number}</p>
                            <p>Date: ${data.receipt.receipt_date}</p>
                        </div>
                        <div class="receipt-info">
                            <div class="receipt-row">
                                <p><strong>Buyer:</strong> ${data.receipt.buyer_name}</p>
                            </div>
                            <div class="receipt-row">
                                <p><strong>Seller:</strong> ${data.receipt.seller_name}</p>
                            </div>
                            <div class="receipt-row">
                                <p><strong>Product:</strong> ${data.receipt.product_name}</p>
                            </div>
                            <div class="receipt-row">
                                <p><strong>Size:</strong> ${data.receipt.size}</p>
                            </div>
                            <div class="receipt-row">
                                <p><strong>Quantity:</strong> ${data.receipt.quantity} pieces</p>
                            </div>
                            <div class="receipt-row receipt-total">
                                <p><strong>Total Amount:</strong> ₱${parseFloat(data.receipt.total_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                            </div>
                        </div>
                        <div class="receipt-actions">
                            <button class="save-receipt-btn" onclick="saveReceipt(${receiptId})">
                                <i class="fas fa-save"></i> Save Receipt
                            </button>
                            <button class="pay-product-btn" onclick="payProduct(${receiptId})">
                                <i class="fas fa-credit-card"></i> Pay Product
                            </button>
                        </div>
                    </div>
                `;
            } else {
                throw new Error(data.message || 'Failed to load receipt');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalContent.innerHTML = `
                <span class="close-modal" onclick="closeReceiptModal()">&times;</span>
                <div class="error-message">
                    <p>Error loading receipt. Please try again.</p>
                    <p>Error details: ${error.message}</p>
                </div>
            `;
        });
}
        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        function printReceipt() {
            window.print();
        }

        // Add this new function to handle saving as image
        function saveAsImage() {
            const receiptElement = document.querySelector('.receipt-details');
            const saveButton = document.querySelector('.save-receipt-btn');
            
            // Temporarily hide the save button for the screenshot
            saveButton.style.display = 'none';
            
            html2canvas(receiptElement).then(canvas => {
                // Show the button again
                saveButton.style.display = 'flex';
                
                // Create download link
                const link = document.createElement('a');
                link.download = 'receipt.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
        function saveReceipt(receiptId) {
            const receiptElement = document.querySelector('.receipt-details');
            if (!receiptElement) {
                // If we're displaying an image receipt
                const receiptImage = document.getElementById('receiptImage');
                if (receiptImage) {
                    // Create a temporary link to download the image
                    const link = document.createElement('a');
                    link.href = receiptImage.src;
                    link.download = `receipt_${receiptId}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    return;
                }
                alert('No receipt content found to save');
                return;
            }
            
            // Use html2canvas to capture the receipt as an image
            html2canvas(receiptElement).then(canvas => {
                // Create download link
                const link = document.createElement('a');
                link.download = `receipt_${receiptId}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            }).catch(error => {
                console.error('Error saving receipt:', error);
                alert('Error saving receipt. Please try again.');
            });
        }
        function confirmPayment() {
    // Get form data
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    
    // Disable the button to prevent multiple submissions
    const button = document.querySelector('.confirm-payment-btn');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Submit the form data
    fetch('process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to order list page
            window.location.href = data.redirect || 'order_list.php';
        } else {
            // Show error message
            alert('Payment Error: ' + data.message);
            // Reset button
            button.disabled = false;
            button.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        // Reset button
        button.disabled = false;
        button.innerHTML = originalText;
    });
    
    return false; // Prevent form submission
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['payment_success']) && $_SESSION['payment_success']): ?>
        alert('<?php echo $_SESSION['payment_message']; ?>');
        <?php 
        // Clear the session variables
        unset($_SESSION['payment_success']);
        unset($_SESSION['payment_message']);
        ?>
    <?php endif; ?>
});

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        // Close receipt modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('receiptModal');
            if (event.target === modal) {
                closeReceiptModal();
            }
        });

        function markAsReceived(orderId) {
            if (!confirm('Confirm that you have received this product?')) {
                return;
            }

            const button = event.target.closest('.receive-btn');
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'Product Received'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the status cell
                    const row = button.closest('tr');
                    const statusCell = row.querySelector('.status-badge');
                    statusCell.textContent = 'Product Received';
                    statusCell.setAttribute('data-status', 'Product Received');
                    // Remove the receive button
                    button.remove();
                } else {
                    alert('Error updating status: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Received';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating status. Please try again.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check-circle"></i> Received';
            });
        }
    </script>
</body>
</html>