<?php
session_start();

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all departments
    $stmt = $conn->query("SELECT id, name FROM departments ORDER BY name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all classes
    $stmt = $conn->query("SELECT id, name, department_id FROM classes ORDER BY name");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error loading departments and classes: " . $e->getMessage());
    $departments = [];
    $classes = [];
}

// Get any errors or form data from session
$errors = $_SESSION['signup_errors'] ?? [];
$form_data = $_SESSION['signup_form_data'] ?? [];

// Clear session data
unset($_SESSION['signup_errors'], $_SESSION['signup_form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .signup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <h2 class="text-center mb-4">Sign Up</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="process_signup.php" method="POST" id="signupForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="prenom" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($form_data['prenom'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="etudiant" <?php echo (($form_data['role'] ?? '') === 'etudiant') ? 'selected' : ''; ?>>Student</option>
                        <option value="enseignant" <?php echo (($form_data['role'] ?? '') === 'enseignant') ? 'selected' : ''; ?>>Teacher</option>
                        <option value="admin" <?php echo (($form_data['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                    </select>
                </div>

                <div class="mb-3 student-field" style="display: none;">
                    <label for="class_id" class="form-label">Class</label>
                    <select class="form-select" id="class_id" name="class_id">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 teacher-field" style="display: none;">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Sign Up</button>
                    <a href="login.php" class="btn btn-link text-center">Already have an account? Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('role').addEventListener('change', function() {
            const studentFields = document.querySelectorAll('.student-field');
            const teacherFields = document.querySelectorAll('.teacher-field');
            
            if (this.value === 'etudiant') {
                studentFields.forEach(field => field.style.display = 'block');
                teacherFields.forEach(field => field.style.display = 'none');
            } else if (this.value === 'enseignant') {
                studentFields.forEach(field => field.style.display = 'none');
                teacherFields.forEach(field => field.style.display = 'block');
            } else {
                studentFields.forEach(field => field.style.display = 'none');
                teacherFields.forEach(field => field.style.display = 'none');
            }
        });

        // Trigger the change event on page load if a role is selected
        if (document.getElementById('role').value) {
            document.getElementById('role').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>
