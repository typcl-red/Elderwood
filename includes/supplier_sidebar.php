<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #8B4513;
        position: fixed;
        left: 0;
        top: 0;
        color: white;
        padding: 20px;
        overflow-y: auto;
    }

    .profile-section {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #DEB887;
    }

    .profile-image {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 15px;
        background-color: #DEB887;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .profile-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .profile-image i {
        font-size: 64px;
        color: #8B4513;
    }

    .profile-details h3 {
        margin-bottom: 10px;
        font-size: 1.2rem;
    }

    .profile-details p {
        font-size: 0.9rem;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .nav-links {
        list-style: none;
        padding: 0;
    }

    .nav-links li {
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 5px;
        transition: background-color 0.3s;
    }

    .nav-links li:hover {
        background-color: #A0522D;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .nav-links i {
        width: 20px;
        text-align: center;
    }

    .change-photo-btn {
        background-color: var(--secondary-brown);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
        margin: 10px auto;
        width: fit-content;
        font-size: 0.9rem;
    }

    .change-photo-btn:hover {
        background-color: var(--primary-brown);
    }
</style>

<!-- Add burger menu button -->
<button id="sidebarToggle" class="sidebar-toggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar">
    <div class="profile-section">
        <div class="profile-image">
            <?php if (!empty($userDetails['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($userDetails['profile_photo']); ?>" alt="Profile Photo">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="profile-details">
            <h3><?php echo htmlspecialchars($userDetails['firstname'] . ' ' . $userDetails['lastname']); ?></h3>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userDetails['address']); ?></p>
            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($userDetails['contactno']); ?></p>
            <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($userDetails['role']); ?></p>
            <button class="change-photo-btn" onclick="showProfileUploadModal()">
                <i class="fas fa-camera"></i> Change Profile Photo
            </button>
        </div>
    </div>

    <ul class="nav-links">
        <li>
            <a href="supplier_dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="supplier_inventory.php">
                <i class="fas fa-box"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li>
            <a href="supplier_presentations.php">
                <i class="fas fa-box-open"></i>
                <span>Presentations</span>
            </a>
        </li>
        <li>
            <a href="supplier_inventory.php">
                <i class="fas fa-warehouse"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<!-- Add this right before closing </div> of sidebar -->
</ul>
</div>

<!-- Profile Upload Modal -->
<!-- Add these in the head section or before your closing body tag -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

<!-- Update the modal HTML -->
<div id="profileUploadModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal" onclick="closeProfileUploadModal()">&times;</span>
        <h2>Change Profile Photo</h2>
        <form id="profilePhotoForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profilePhoto">Choose Photo</label>
                <input type="file" id="profilePhoto" name="profilePhoto" accept="image/*" required>
            </div>
            <button type="submit" class="upload-btn">Upload Photo</button>
        </form>
    </div>
</div>

<!-- First, add these styles in the style section -->
<style>
    /* Update modal display function */
    function showProfileUploadModal() {
        const modal = document.getElementById('profileUploadModal');
        modal.style.display = 'flex';
    }

    .modal {
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 90%;
        max-width: 400px;
        position: relative;
        margin: 0 auto;
    }

    .close-modal {
        position: absolute;
        right: 15px;
        top: 10px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .form-group {
        margin: 20px 0;
    }

    .upload-btn {
        background-color: #8B4513;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        width: 100%;
    }
</style>

<!-- Then, move the JavaScript to a proper script tag -->
<script>
function showProfileUploadModal() {
    const modal = document.getElementById('profileUploadModal');
    modal.style.display = 'flex';
}

function closeProfileUploadModal() {
    document.getElementById('profileUploadModal').style.display = 'none';
}

document.getElementById('profilePhotoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const photoFile = document.getElementById('profilePhoto').files[0];
    formData.append('profilePhoto', photoFile);

    fetch('includes/update_photo_supplier.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error uploading photo: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading photo');
    });
});
</script>

<style>
    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #8B4513;
        position: fixed;
        left: 0;
        top: 0;
        color: white;
        padding: 20px;
        overflow-y: auto;
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: #8B4513;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.2em;
    }

    /* Add responsive styles */
    @media screen and (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }

        .profile-section {
            padding: 10px 0;
        }

        .profile-image {
            width: 80px;
            height: 80px;
        }

        .nav-links li {
            padding: 8px 10px;
        }
    }
</style>

<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });

    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const toggle = document.getElementById('sidebarToggle');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !toggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    });
</script>