<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'elderwood';


// Create database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Add connection test
    error_log('Database connection successful');
} catch(PDOException $e) {
    error_log('Connection failed: ' . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}