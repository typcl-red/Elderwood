<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check if receipt ID and status are provided
if (!isset($_POST['receipt_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$receiptId = $_POST['receipt_id'];
$status = $_POST['status'];
$userId = $_SESSION['user_id'];

// Verify that the receipt belongs to the user
$query = "SELECT r.* FROM receipts r
         JOIN orders o ON r.order_id = o.order_id
         WHERE r.receipt_id = ? AND o.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $receiptId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$result = mysqli_fetch_assoc($result);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Receipt not found or does not belong to user']);
    exit();
}

// Update the payment status
$query = "UPDATE receipts SET payment_status = ? WHERE receipt_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'si', $status, $receiptId);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating payment status']);
}
?>