<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_FILES['payment_proof']) || !isset($_POST['invoice_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

try {
    $invoice_id = $_POST['invoice_id'];
    $file = $_FILES['payment_proof'];
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/payments/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    $target_path = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Update database
        $stmt = $pdo->prepare("
            UPDATE invoices 
            SET payment_proof = ?, payment_status = 'paid' 
            WHERE invoice_id = ? AND seller_id = ?
        ");
        
        $stmt->execute([$target_path, $invoice_id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}