<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $stmt = $pdo->prepare("
            SELECT co.*, u.firstname, u.lastname 
            FROM cus_orders co
            JOIN order_list ol ON co.order_id = ol.order_id
            JOIN users u ON ol.buyer_id = u.id
            WHERE co.order_id = :order_id
        ");
        
        $stmt->execute(['order_id' => $data['order_id']]);
        $tally = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tally) {
            $tally['buyer_name'] = $tally['firstname'] . ' ' . $tally['lastname'];
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'tally' => $tally
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Tally not found']);
        }
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
    }
}