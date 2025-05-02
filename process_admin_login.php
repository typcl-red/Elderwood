<?php
session_start();
require_once 'database/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
    exit();
}

try {
    // First, let's verify the admin exists
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // For debugging, let's log the actual values
    error_log('Attempted login with username: ' . $username);
    error_log('Password attempt: ' . $password);
    error_log('Admin found in database: ' . ($admin ? 'Yes' : 'No'));
    
    if ($admin) {
        error_log('Stored hashed password: ' . $admin['password']);
        // Create a new hash of the input password for comparison
        $passwordMatch = password_verify($password, $admin['password']);
        error_log('Password match result: ' . ($passwordMatch ? 'True' : 'False'));

        if ($passwordMatch) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['firstname'] . ' ' . $admin['lastname'];
            echo json_encode(['success' => true]);
            exit();
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}