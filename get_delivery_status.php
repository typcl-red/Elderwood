<?php
session_start();
require_once 'database/config.php';

if (isset($_GET['presentation_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT status FROM delivery_schedules WHERE presentation_id = ?");
        $stmt->execute([$_GET['presentation_id']]);
        $status = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'status' => $status]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing presentation ID']);
}