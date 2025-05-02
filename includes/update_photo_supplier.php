<?php
session_start();
require_once '../database/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_FILES['profilePhoto']) || $_FILES['profilePhoto']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }

    $file = $_FILES['profilePhoto'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type. Please upload a JPEG, PNG, or GIF image.');
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../uploads/profile_photos';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = 'uploads/profile_photos/' . $filename;
    $fullPath = '../' . $targetPath;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception('Failed to upload image');
    }

    // Update user's profile photo in database
    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt->execute([$targetPath, $_SESSION['user_id']]);

    // Delete old profile photo if exists
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $oldPhoto = $stmt->fetchColumn();

    if ($oldPhoto && file_exists('../' . $oldPhoto) && $oldPhoto !== $targetPath) {
        unlink('../' . $oldPhoto);
    }

    $response['success'] = true;
    $response['message'] = 'Profile photo updated successfully';

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    // Clean up uploaded file if there was an error
    if (isset($fullPath) && file_exists($fullPath)) {
        unlink($fullPath);
    }
}

header('Content-Type: application/json');
echo json_encode($response);