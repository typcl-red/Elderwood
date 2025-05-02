<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['invoice_id'])) {
    header('Location: login.php');
    exit();
}

$invoice_id = $_POST['invoice_id'];
$upload_dir = 'uploads/payments/';

// Create directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle file upload
if (isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    $file_name = time() . '_' . $file['name'];
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        try {
            // Update invoice with payment proof and date
            $stmt = $pdo->prepare("
                UPDATE invoices 
                SET payment_proof = ?, 
                    payment_date = CURRENT_TIMESTAMP 
                WHERE invoice_id = ?
            ");
            $stmt->execute([$file_path, $invoice_id]);
            
            header('Location: view_invoice.php?id=' . $invoice_id . '&payment=success');
            exit();
        } catch (Exception $e) {
            header('Location: view_invoice.php?id=' . $invoice_id . '&payment=error');
            exit();
        }
    }
}

header('Location: view_invoice.php?id=' . $invoice_id . '&payment=error');
exit();