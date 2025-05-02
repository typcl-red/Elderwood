<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access - ElderWood</title>
    <style>
        :root {
            --primary-brown: #8B4513;
            --secondary-brown: #A0522D;
            --light-brown: #DEB887;
            --bg-brown: #FFF8DC;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-brown);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .error-container {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: var(--primary-brown);
            margin-bottom: 20px;
        }

        p {
            color: var(--secondary-brown);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-brown);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: var(--secondary-brown);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Unauthorized Access</h1>
        <p>Sorry, you don't have permission to access this page. Please make sure you're logged in with the correct account type.</p>
        <a href="main.php" class="back-btn">Back to Home</a>
    </div>
</body>
</html> 