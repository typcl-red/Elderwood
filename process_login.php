<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

$response = array('success' => false, 'message' => '', 'redirect' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get username and password from form
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $response['message'] = "Please fill in all fields";
    } else {
        try {
            // Add error reporting
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Create User object with PDO connection
            $user = new User($pdo);
            
            $result = $user->login($username, $password);
            
            if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $result['role'];
                
                // Set redirect based on role
                switch($result['role']) {
                    case 'Seller':
                        $response['redirect'] = 'seller_dashboard.php';
                        break;
                    case 'Buyer':
                        $response['redirect'] = 'buyer_dashboard.php';
                        break;
                    case 'Laborer':
                        $response['redirect'] = 'laborer_dashboard.php';
                        break;
                    case 'Supplier':
                        $response['redirect'] = 'supplier_dashboard.php';
                        break;
                    default:
                        $response['redirect'] = 'main.php';
                }
                
                $response['success'] = true;
                $response['message'] = "Login successful";
            } else {
                $response['message'] = $result['error'];
            }
        } catch (Exception $e) {
            $response['message'] = "An error occurred during login: " . $e->getMessage();
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);