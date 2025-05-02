<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElderWood - Home</title>
    <style>
        /* Hell world. */
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
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-brown);
            min-height: 100vh;
            display: flex;
            position: relative;
            overflow-x: hidden;
        }

        /* Loading Screen */
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bg-brown);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: all 0.8s ease;
        }

        .loading-screen.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        .loading-container {
            position: relative;
            width: 250px;
            height: 250px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loading-logo {
            width: 180px;
            height: 180px;
            object-fit: contain;
            position: relative;
            z-index: 2;
            transition: all 0.5s ease;
        }

        .loading-logo.fade {
            opacity: 0;
            transform: scale(0.8);
        }

        .loading-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top: 4px solid var(--primary-brown);
            border-right: 4px solid var(--primary-brown);
            border-radius: 50%;
            animation: spin 2s linear infinite;
            transition: all 0.5s ease;
        }

        .loading-circle:before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border: 4px solid transparent;
            border-bottom: 4px solid var(--secondary-brown);
            border-left: 4px solid var(--secondary-brown);
            border-radius: 50%;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .background-logo {
            position: fixed;
            top: 50%;
            left: 25%;
            transform: translate(-50%, -50%);
            width: 50vw;
            height: 50vw;
            max-width: 600px;
            max-height: 600px;
            opacity: 0;
            pointer-events: none;
            z-index: 0;
            transition: opacity 1s ease 0.3s;
        }

        .background-logo.show {
            opacity: 0.1;
        }

        .background-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .split-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            opacity: 0;
            transform: translateY(20px);
            transition: all 1s ease;
        }

        .split-container.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Left Side */
        .left-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: rgba(139, 69, 19, 0.05);
            text-align: center;
        }

        .content {
            max-width: 800px;
            width: 100%;
            padding: 0 20px;
            position: relative;
        }

        .content h1 {
            color: var(--primary-brown);
            font-size: clamp(2.5rem, 5vw, 4rem);
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .content p {
            color: var(--secondary-brown);
            font-size: clamp(1.1rem, 1.8vw, 1.4rem);
            line-height: 1.8;
            margin-bottom: 40px;
            text-align: center;
        }

        /* Right Side */
        .right-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background-color: rgba(139, 69, 19, 0.1);
        }

        .auth-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .auth-btn {
            display: block;
            width: 100%;
            padding: 18px;
            margin: 15px 0;
            border-radius: 12px;
            border: none;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .login-btn {
            background-color: var(--primary-brown);
            color: white;
        }

        .signup-btn {
            background-color: white;
            color: var(--primary-brown);
            border: 2px solid var(--primary-brown);
        }

        .auth-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        /* Floating Form Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
            padding: 20px 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            background-color: var(--bg-brown);
            margin: 20px auto;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close-btn {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 24px;
            color: var(--primary-brown);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            transform: scale(1.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--primary-brown);
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--light-brown);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-brown);
            box-shadow: 0 0 5px rgba(139, 69, 19, 0.3);
        }

        .form-title {
            color: var(--primary-brown);
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .submit-btn {
            background-color: var(--primary-brown);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background-color: var(--secondary-brown);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            color: #ff3333;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }

            .left-side, .right-side {
                padding: 30px 20px;
            }

            .background-logo {
                left: 50%;
                width: 70vw;
                height: 70vw;
                opacity: 0.08;
            }

            .loading-container {
                width: 200px;
                height: 200px;
            }

            .loading-logo {
                width: 140px;
                height: 140px;
            }

            .content {
                padding: 0 15px;
            }

            .auth-container {
                margin-top: 20px;
            }

            .auth-btn {
                padding: 15px;
                font-size: 1.1rem;
            }

            .modal {
                padding: 10px;
            }
            
            .modal-content {
                margin: 10px auto;
                padding: 20px;
                width: 95%;
            }
            
            .form-group {
                margin-bottom: 15px;
            }
        }

        /* Add these styles for the response message */
        .response-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }

        .response-message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .response-message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        /* Add these styles after your existing styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: #4CAF50;
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 2000;
            transform: translateX(200%);
            transition: transform 0.3s ease;
            font-weight: bold;
        }

        .notification.show {
            transform: translateX(0);
        }

        @media screen and (max-width: 768px) {
            .notification {
                top: 10px;
                right: 10px;
                left: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="loading-screen">
        <div class="loading-container">
            <div class="loading-circle"></div>
            <img src="assets/images/ELDERWOOD_LOGO.png" alt="ElderWood Loading" class="loading-logo">
        </div>
    </div>
    <div class="background-logo">
        <img src="assets/images/ELDERWOOD_LOGO.png" alt="ElderWood Watermark">
    </div>
    <div class="split-container">
        <div class="left-side">
            <div class="content">
                <h1>Welcome to ElderWood</h1>
                <p>Streamlining lumberyard operations for a sustainable future. Experience the perfect blend of tradition and innovation in wood management.</p>
            </div>
        </div>
        <div class="right-side">
            <div class="auth-container">
                <a href="login.php" class="auth-btn login-btn">Log In</a>
                <a href="signup.php" class="auth-btn signup-btn">Sign Up</a>
            </div>
        </div>
    </div>

    <!-- Add login modal before the signup modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="loginCloseBtn">&times;</span>
            <h2 class="form-title">Login to Your Account</h2>
            <div id="loginResponseMessage" class="response-message"></div>
            <form id="loginForm" action="process_login.php" method="POST">
                <div class="form-group">
                    <label for="login-username">Username</label>
                    <input type="text" id="login-username" name="username" required>
                    <div class="error-message" id="login-username-error"></div>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                    <div class="error-message" id="login-password-error"></div>
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signupModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 class="form-title">Create an Account</h2>
            <div id="responseMessage" class="response-message"></div>
            <form id="signupForm" action="process_signup.php" method="POST">
                <div class="form-group">
                    <label for="firstname">First Name</label>
                    <input type="text" id="firstname" name="firstname" required>
                    <div class="error-message" id="firstname-error"></div>
                </div>
                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" required>
                    <div class="error-message" id="lastname-error"></div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                    <div class="error-message" id="email-error"></div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                    <div class="error-message" id="username-error"></div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <div class="error-message" id="password-error"></div>
                </div>
                <div class="form-group">
                    <label for="contactno">Contact Number</label>
                    <input type="tel" id="contactno" name="contactno" required>
                    <div class="error-message" id="contactno-error"></div>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required>
                    <div class="error-message" id="address-error"></div>
                </div>
                <div class="form-group">
                <label for="role">Role</label>
                    <select id="role" name="role" required onchange="toggleEmployerField()">
                        <option value="">Select a role</option>
                        <option value="Buyer">Buyer</option>
                        <option value="Seller">Seller</option>
                        <option value="Supplier">Supplier</option>
                        <option value="Laborer">Laborer</option>
                    </select>
                    <div class="error-message" id="role-error"></div>
                </div>

                <!-- Add employer ID field for Laborer -->
                <div class="form-group" id="employerIdGroup" style="display: none;">
                    <label for="employer_id">Employer ID (Seller's ID)</label>
                    <input type="text" id="employer_id" name="employer_id" class="form-control" placeholder="Enter Seller ID (e.g., S00001)">
                    <small class="help-text">Enter the unique Seller ID provided by your employer</small>
                    <div class="error-message" id="employer-id-error"></div>
                </div>

                <button type="submit" class="submit-btn">Sign Up</button>
            </form>
        </div>
    </div>

    <!-- Add this div after your modal div -->
    <div id="notification" class="notification"></div>

    <script>
        function toggleEmployerField() {
            const role = document.getElementById('role').value;
            const employerField = document.getElementById('employerIdGroup');
            const employerInput = document.getElementById('employer_id');
            
            if (role === 'Laborer') {
                employerField.style.display = 'block';
                employerInput.required = true;
            } else {
                employerField.style.display = 'none';
                employerInput.required = false;
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.querySelector('.loading-screen');
            const loadingLogo = document.querySelector('.loading-logo');
            const loadingCircle = document.querySelector('.loading-circle');
            const backgroundLogo = document.querySelector('.background-logo');
            const splitContainer = document.querySelector('.split-container');
            
            // Start the animation sequence after 3 seconds
            setTimeout(() => {
                // Fade out the loading circle and logo
                loadingCircle.style.opacity = '0';
                loadingLogo.classList.add('fade');
                
                // After logo fades, show watermark and content
                setTimeout(() => {
                    loadingScreen.classList.add('fade-out');
                    backgroundLogo.classList.add('show');
                    splitContainer.classList.add('show');
                    
                    // Remove loading screen from DOM after fade
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 800);
                }, 500);
            }, 3000);
        });

        // Login Modal Functionality
        const loginModal = document.getElementById('loginModal');
        const loginBtn = document.querySelector('.login-btn');
        const loginCloseBtn = document.getElementById('loginCloseBtn');
        const loginForm = document.getElementById('loginForm');

        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });

        loginCloseBtn.addEventListener('click', function() {
            loginModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        window.addEventListener('click', function(e) {
            if (e.target == loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Login Form submission
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
            });

            // Hide response message
            const loginResponseMessage = document.getElementById('loginResponseMessage');
            loginResponseMessage.style.display = 'none';

            // Basic validation
            const username = document.getElementById('login-username');
            const password = document.getElementById('login-password');

            if (!username.value.trim()) {
                document.getElementById('login-username-error').textContent = 'Username is required';
                document.getElementById('login-username-error').style.display = 'block';
                isValid = false;
            }

            if (!password.value.trim()) {
                document.getElementById('login-password-error').textContent = 'Password is required';
                document.getElementById('login-password-error').style.display = 'block';
                isValid = false;
            }

            if (isValid) {
                const formData = new FormData(this);
                
                fetch('process_login.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success notification
                        const notification = document.getElementById('notification');
                        notification.textContent = "Login successful! Redirecting...";
                        notification.classList.add('show');
                        
                        // Redirect based on user role
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Show error in the form
                        loginResponseMessage.textContent = data.message;
                        loginResponseMessage.className = 'response-message error';
                        loginResponseMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    loginResponseMessage.textContent = 'An error occurred. Please try again.';
                    loginResponseMessage.className = 'response-message error';
                    loginResponseMessage.style.display = 'block';
                });
            }
        });

        // Signup Modal Functionality
        const modal = document.getElementById('signupModal');
        const signupBtn = document.querySelector('.signup-btn');
        const closeBtn = document.querySelector('.close-btn');
        const form = document.getElementById('signupForm');

        signupBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        });

        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        });

        window.addEventListener('click', function(e) {
            if (e.target == modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            
            // Reset error messages
            document.querySelectorAll('.error-message').forEach(error => {
                error.style.display = 'none';
            });

            // Hide response message
            const responseMessage = document.getElementById('responseMessage');
            responseMessage.style.display = 'none';

            // Validate each field
            const fields = ['firstname', 'lastname', 'email', 'username', 'password', 'contactno', 'address', 'role'];
            fields.forEach(field => {
                const input = document.getElementById(field);
                if (!input.value.trim()) {
                    document.getElementById(`${field}-error`).textContent = 'This field is required';
                    document.getElementById(`${field}-error`).style.display = 'block';
                    isValid = false;
                }
            });

            // Email validation
            const email = document.getElementById('email');
            if (email.value && !email.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                document.getElementById('email-error').textContent = 'Please enter a valid email address';
                document.getElementById('email-error').style.display = 'block';
                isValid = false;
            }

            // Password validation
            const password = document.getElementById('password');
            if (password.value && password.value.length < 8) {
                document.getElementById('password-error').textContent = 'Password must be at least 8 characters long';
                document.getElementById('password-error').style.display = 'block';
                isValid = false;
            }

            // If form is valid, submit via AJAX
            if (isValid) {
                const formData = new FormData(this);
                const notification = document.getElementById('notification');
                
                fetch('process_signup.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success notification
                        notification.textContent = "Signing up successfully!";
                        notification.classList.add('show');
                        
                        // Clear form
                        form.reset();
                        
                        // Close modal
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';

                        // Wait for notification to be visible
                        setTimeout(() => {
                            // Reload page after showing notification
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Show error in the form
                        responseMessage.textContent = data.message;
                        responseMessage.className = 'response-message error';
                        responseMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    responseMessage.textContent = 'An error occurred. Please try again.';
                    responseMessage.className = 'response-message error';
                    responseMessage.style.display = 'block';
                });
            }
        });
    </script>
</body>
</html>
