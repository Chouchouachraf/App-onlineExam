<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Online Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .welcome-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            max-width: 800px;
            margin: 0 auto;
        }
        .btn-custom {
            padding: 12px 30px;
            font-size: 1.1rem;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .welcome-text {
            color: #2d3748;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-container text-center">
            <h1 class="display-4 mb-4">Welcome to ExamMaster</h1>
            <p class="lead welcome-text">
                Your comprehensive online examination platform. Join us to experience 
                seamless exam management and assessment.
            </p>
            <div class="d-flex justify-content-center flex-wrap">
                <a href="auth/signup.php" class="btn btn-primary btn-custom m-2">
                    Create Account
                </a>
                <a href="auth/login.php" class="btn btn-outline-primary btn-custom m-2">
                    Login
                </a>
            </div>
            <div class="mt-4">
                <p class="text-muted">
                    Experience the future of online examinations with our secure and 
                    user-friendly platform.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
