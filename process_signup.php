<?php
session_start();
require_once 'database/config.php';
require_once 'classes/User.php';

$user = new User($pdo);
$response = array('success' => false, 'message' => '');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $contactno = trim($_POST['contactno']);
    $address = trim($_POST['address']);
    $role = trim($_POST['role']);
    $employer_id = ($role === 'Laborer') ? trim($_POST['employer_id']) : null;

    // Validate input
    $errors = array();

    // Check if email already exists
    if ($user->emailExists($email)) {
        $errors[] = "Email already exists";
    }

    // Check if username already exists
    if ($user->usernameExists($username)) {
        $errors[] = "Username already exists";
    }

    // Validate employer_id for Laborers
    if ($role === 'Laborer') {
        if (empty($employer_id)) {
            $errors[] = "Employer ID is required for Laborers";
        } else {
            // Check if employer exists in seller_ids table and is a Seller
            $stmt = $pdo->prepare("
                SELECT u.id, u.role 
                FROM users u 
                JOIN seller_ids s ON u.id = s.user_id 
                WHERE s.seller_unique_id = ?
            ");
            $stmt->execute([$employer_id]);
            $employer = $stmt->fetch();
            
            if (!$employer) {
                $errors[] = "Invalid Seller ID";
            } elseif ($employer['role'] !== 'Seller') {
                $errors[] = "Employer must be a Seller";
            } else {
                // Store the actual user_id of the seller instead of the seller_unique_id
                $employer_id = $employer['id'];
            }
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        if ($user->create($lastname, $firstname, $email, $username, $password, $contactno, $address, $role, $employer_id)) {
            $response['success'] = true;
            $response['message'] = "Registration successful! Please log in.";
        } else {
            $response['message'] = "Something went wrong. Please try again.";
        }
    } else {
        $response['message'] = implode("<br>", $errors);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);