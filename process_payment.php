<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    $proof_of_payment = null;
    
    if ($_POST['payment_method'] === 'Gcash' && isset($_FILES['payment_proof'])) {
        $file = $_FILES['payment_proof'];
        $fileName = 'payment_proof_' . time() . '_' . $file['name'];
        $uploadPath = 'uploads/payment_proofs/' . $fileName;
        
        if (!file_exists('uploads/payment_proofs')) {
            mkdir('uploads/payment_proofs', 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $proof_of_payment = $uploadPath;
        } else {
            echo json_encode(['success' => false, 'error' => 'Error uploading file']);
            exit();
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO payments (order_id, receipt_id, payment_method, proof_of_payment, amount)
        VALUES (:order_id, :receipt_id, :payment_method, :proof_of_payment, :amount)
    ");

    $stmt->execute([
        'order_id' => $_POST['order_id'],
        'receipt_id' => $_POST['receipt_id'],
        'payment_method' => $_POST['payment_method'],
        'proof_of_payment' => $proof_of_payment,
        'amount' => $_POST['amount']
    ]);

    // Update order status
    $updateStmt = $pdo->prepare("
        UPDATE order_list 
        SET status = 'Processing' 
        WHERE order_id = :order_id
    ");
    $updateStmt->execute(['order_id' => $_POST['order_id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>