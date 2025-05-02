<?php
session_start();
require_once 'database/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();

        // First, delete related inventory records
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE supplier_id = ?");
        $stmt->execute([$_GET['id']]);

        // Then delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $_SESSION['success_message'] = "User and related records deleted successfully.";
        } else {
            $pdo->rollBack();
            $_SESSION['error_message'] = "User not found.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }
}

// Redirect back to the accounts page
header('Location: admin_accounts.php');
exit();
?>