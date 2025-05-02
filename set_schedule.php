<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $presentationId = $_POST['presentationId'];
    $scheduleDate = $_POST['scheduleDate'];
    $hour = $_POST['hour'];
    $minute = $_POST['minute'];
    $ampm = $_POST['ampm'];

    try {
        // Get supplier_id from the presentation
        $stmt = $pdo->prepare("SELECT i.supplier_id FROM sup_orders so JOIN inventory i ON so.item_id = i.id WHERE so.id = ?");
        $stmt->execute([$presentationId]);
        $result = $stmt->fetch();
        $supplierId = $result['supplier_id'];

        // Convert time to 24-hour format
        $hour24 = ($ampm === 'PM' && $hour != 12) ? $hour + 12 : ($ampm === 'AM' && $hour == 12 ? 0 : $hour);
        $dateTime = date('Y-m-d H:i:s', strtotime("$scheduleDate $hour24:$minute:00"));

        // Insert the schedule with seller and supplier IDs
        $stmt = $pdo->prepare("INSERT INTO delivery_schedules (presentation_id, seller_id, supplier_id, scheduled_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$presentationId, $_SESSION['user_id'], $supplierId, $dateTime]);

        // Update the presentation status
        $stmt = $pdo->prepare("UPDATE sup_orders SET status = 'Scheduled' WHERE id = ?");
        $stmt->execute([$presentationId]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}