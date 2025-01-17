<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-container {
            background: rgba(255, 255, 255, 0.95);
            max-width: 500px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            transition: transform 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <i class='bx bx-check-circle success-icon'></i>
            
            <?php
            session_start();
            if (isset($_SESSION['success_message'])) {
                echo "<h2 class='mb-4'>Registration Successful!</h2>";
                echo "<p class='mb-4'>" . htmlspecialchars($_SESSION['success_message']) . "</p>";
                unset($_SESSION['success_message']);
            } else {
                echo "<h2 class='mb-4'>Welcome to ExamMaster!</h2>";
                echo "<p class='mb-4'>Your account has been created successfully.</p>";
            }
            ?>

            <p class="mb-4">You can now proceed to login once your account is approved.</p>
            
            <div class="d-grid gap-2">
                <a href="login.php" class="btn btn-custom mb-2">Proceed to Login</a>
                <a href="../index.php" class="text-decoration-none">← Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
