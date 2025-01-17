<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            max-width: 450px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .form-title {
            color: #2d3748;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 12px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #764ba2;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="form-title">Welcome Back</h2>
            
            <?php
            session_start();
            if (isset($_SESSION['login_error'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_SESSION['login_error']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['login_error']);
            }
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                echo htmlspecialchars($_SESSION['success_message']);
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }
            ?>

            <form action="process_login.php" method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required 
                           value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : ''; ?>">
                    <div class="invalid-feedback">Please enter your email address.</div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class='bx bx-show'></i>
                        </button>
                    </div>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-submit w-100">Login</button>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                    <a href="../index.php" class="text-decoration-none">← Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bx-show');
            this.querySelector('i').classList.toggle('bx-hide');
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
