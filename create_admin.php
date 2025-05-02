<?php
require_once 'database/config.php';

$username = 'admin';
$password = 'admin052601';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin exists
    $check = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->rowCount() > 0) {
        // Update existing admin
        $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        echo "Admin password updated successfully!";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO admin (username, password, firstname, lastname, email) VALUES (?, ?, 'Admin', 'User', 'admin@elderwood.com')");
        $stmt->execute([$username, $hashed_password]);
        echo "Admin account created successfully!";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>