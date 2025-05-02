<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Seller') {
    header('Location: login.php');
    exit();
}

// Get notifications for the seller
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - ElderWood</title>
    <!-- Add your CSS here -->
</head>
<body>
    <div class="notifications-container">
        <h1>Notifications</h1>
        <?php if (empty($notifications)): ?>
            <p>No notifications found.</p>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="notification-content">
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                    </div>
                    <?php if (!$notification['is_read']): ?>
                        <button onclick="markAsRead(<?php echo $notification['id']; ?>)">Mark as Read</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function markAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
    </script>
</body>
</html> 