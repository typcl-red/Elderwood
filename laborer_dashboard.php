<?php
session_start();
require_once 'database/config.php';

// Check if user is logged in and is a laborer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Laborer') {
    header('Location: tasks.php');
    exit();
}

// Get user details
// Update the SQL query to get employer's name
$stmt = $pdo->prepare("SELECT u.*, s.seller_unique_id as employer_unique_id, 
                       e.firstname as employer_firstname, e.lastname as employer_lastname
                       FROM users u 
                       LEFT JOIN seller_ids s ON u.employer_id = s.user_id 
                       LEFT JOIN users e ON u.employer_id = e.id
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userDetails = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laborer Dashboard - ElderWood</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: var(--bg-brown);
            display: flex;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-brown);
        }

        .profile-details {
            padding: 10px;
        }

        .profile-details h3 {
            color: var(--primary-brown);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .profile-details p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-details i {
            margin-right: 8px;
            color: var(--primary-brown);
        }

        .nav-links {
            list-style: none;
            margin-bottom: 30px;
        }

        .nav-links li {
            margin-bottom: 5px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--primary-brown);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: var(--bg-brown);
            transform: translateX(5px);
        }

        .nav-links i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .logout-btn {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid var(--light-brown);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #d32f2f;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .logout-btn a:hover {
            background-color: #ffebee;
            transform: translateX(5px);
        }

        .logout-btn i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        .dashboard-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-brown);
        }

        .profile-image {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background-color: var(--light-brown);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .change-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: var(--primary-brown);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .change-photo-btn:hover {
            background: var(--secondary-brown);
        }

        .profile-upload-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .profile-upload-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #profilePreview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 20px auto;
            display: block;
        }

        .profile-image i {
            font-size: 50px;
            color: white;
        }

        .profile-details h3 {
            color: var(--primary-brown);
            margin-bottom: 5px;
        }

        .profile-details p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .nav-links {
            list-style: none;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: var(--primary-brown);
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: var(--bg-brown);
        }

        .nav-links i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            min-height: 100vh;
            background-color: var(--bg-brown);
        }

        .performance-stats {
            margin-top: 30px;
            padding: 20px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            background-color: var(--bg-brown);
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 20px;
            color: var(--primary-brown);
        }

        .stat-info h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-number {
            color: var(--primary-brown);
            font-size: 1.5rem;
            font-weight: bold;
        }

        .dashboard-headertop: 1px solid var(--light-brown);
        }

        .logout-btn a {
            display: flex;
            align-items: center;
            padding: 10px;
            color: #d32f2f;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .logout-btn a:hover {
            background-color: #ffebee;
        }

        .logout-btn i {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile-section">
            <div class="profile-image">
                <?php if (!empty($userDetails['profile_photo'])): ?>
                    <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
                <p><i class="fas fa-user-tie"></i> Employer: <?php echo htmlspecialchars($userDetails['employer_firstname'] . ' ' . $userDetails['employer_lastname']); ?></p>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
                <p style="display: flex; justify-content: flex-end; padding-right: 1px;">
                    <i class="fas fa-camera" style="color: var(--primary-brown); cursor: pointer;" onclick="showProfileUploadModal()"></i>
                </p>
            </div>
        </div>

        <ul class="nav-links">
            <li>
                <a href="tasks.php">
                    <i class="fas fa-tasks"></i>
                    <span>My Tasks</span>
                </a>
            </li>
            <li>
                <a href="laborer_dashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="logout-btn">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($userDetails['firstname']); ?>!</h1>
            <p>Here's your work overview</p>
        </div>

        <div class="performance-stats">
            <?php
            // Get laborer's performance stats
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'Ready for Pick Up' THEN 1 ELSE 0 END) as completed_tasks,
                    COUNT(DISTINCT DATE(status_updated_at)) as active_days
                FROM order_list 
                WHERE status_updated_by = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $stats = $stmt->fetch();

            $completion_rate = ($stats['total_tasks'] > 0) 
                ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 1) 
                : 0;
            ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Tasks</h3>
                        <p class="stat-number"><?php echo $stats['total_tasks']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed Tasks</h3>
                        <p class="stat-number"><?php echo $stats['completed_tasks']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completion Rate</h3>
                        <p class="stat-number"><?php echo $completion_rate; ?>%</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Days</h3>
                        <p class="stat-number"><?php echo $stats['active_days']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Photo Upload Modal -->
    <div id="profileUploadModal" class="profile-upload-modal">
        <div class="profile-upload-content">
            <span class="close-modal" onclick="closeProfileUploadModal()">&times;</span>
            <h2>Change Profile Photo</h2>
            <form id="profilePhotoForm">
                <div class="form-group">
                    <label for="profilePhoto">Choose Photo</label>
                    <input type="file" id="profilePhoto" name="profile_photo" accept="image/*" onchange="previewImage(this)" required>
                </div>
                <img id="profilePreview" src="<?php echo !empty($userDetails['profile_photo']) ? htmlspecialchars($userDetails['profile_photo']) : 'assets/images/default-profile.png'; ?>" alt="Profile Preview">
                <button type="submit" class="submit-btn">Upload Photo</button>
            </form>
        </div>
    </div>

    <script>
        function showProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'block';
        }

        function closeProfileUploadModal() {
            document.getElementById('profileUploadModal').style.display = 'none';
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.getElementById('profilePhotoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('update_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile photo updated successfully');
                    location.reload();
                } else {
                    alert(data.message || 'Error updating profile photo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating profile photo');
            });
        });
    </script>
</body>
</html>