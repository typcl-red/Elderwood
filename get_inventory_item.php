<?php
session_start();
require_once 'database/config.php';

if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? AND supplier_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($item) {
            echo json_encode($item);
        } else {
            echo json_encode(['error' => 'Item not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error']);
    }
}
?>