
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
            <a href="tasks.php" class="active">
                <i class="fas fa-tasks"></i>
                <span>My Tasks</span>
            </a>
        </li>
        <li>
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