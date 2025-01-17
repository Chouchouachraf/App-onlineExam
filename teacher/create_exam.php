<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $title = $_POST['title'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $class_id = $_POST['class_id'] ?? null;
        $subject_id = $_POST['subject_id'] ?? null;
        $passing_score = $_POST['passing_score'] ?? 60;

        // Begin transaction
        $conn->beginTransaction();

        // Insert exam
        $stmt = $conn->prepare("
            INSERT INTO exams (
                title, 
                description, 
                duration, 
                start_date, 
                end_date, 
                created_by, 
                class_id,
                subject_id,
                passing_score
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title,
            $description,
            $duration,
            $start_date,
            $end_date,
            $_SESSION['user_id'],
            $class_id,
            $subject_id,
            $passing_score
        ]);

        $exam_id = $conn->lastInsertId();

        // Process questions
        if (isset($_POST['questions'])) {
            foreach ($_POST['questions'] as $q) {
                // Skip empty questions
                if (empty($q['text'])) continue;

                // Initialize image path as null
                $image_path = null;

                // Only process image if one was uploaded
                if (isset($_FILES['questions']['name'][$q['index']]['image']) && 
                    !empty($_FILES['questions']['name'][$q['index']]['image']) && 
                    $_FILES['questions']['error'][$q['index']]['image'] === UPLOAD_ERR_OK) {
                    
                    $target_dir = "../uploads/questions/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $file = $_FILES['questions']['name'][$q['index']]['image'];
                    $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $image_path = $target_dir . uniqid() . '.' . $file_extension;
                    
                    move_uploaded_file(
                        $_FILES['questions']['tmp_name'][$q['index']]['image'],
                        $image_path
                    );
                }

                // Insert question with or without image
                $stmt = $conn->prepare("
                    INSERT INTO questions (exam_id, question_text, question_type, points, image_path)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $exam_id,
                    $q['text'],
                    $q['type'],
                    $q['points'],
                    $image_path // Will be null if no image was uploaded
                ]);

                $question_id = $conn->lastInsertId();

                // Handle options for MCQ and True/False questions
                if ($q['type'] === 'mcq' && isset($q['options'])) {
                    foreach ($q['options'] as $index => $option_text) {
                        if (trim($option_text) !== '') {
                            $stmt = $conn->prepare("
                                INSERT INTO question_options (question_id, option_text, is_correct)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([
                                $question_id,
                                $option_text,
                                $index == $q['correct_option'] ? 1 : 0
                            ]);
                        }
                    }
                } elseif ($q['type'] === 'true_false') {
                    $stmt = $conn->prepare("
                        INSERT INTO question_options (question_id, option_text, is_correct)
                        VALUES (?, 'True', ?), (?, 'False', ?)
                    ");
                    $stmt->execute([
                        $question_id,
                        $q['correct_answer'] === 'true' ? 1 : 0,
                        $question_id,
                        $q['correct_answer'] === 'false' ? 1 : 0
                    ]);
                }
            }
        }

        $conn->commit();
        $success_message = "Exam and questions created successfully!";
        
    } catch(PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sidebar {
            height: 100vh;
            background: #2c3e50;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover {
            background: #34495e;
            color: #ecf0f1;
        }
        .sidebar-link i {
            margin-right: 10px;
        }
        .question-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .question-image {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
        }
        .image-preview-container {
            display: none;
            margin: 10px 0;
        }
        .remove-image {
            display: none;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="text-center mb-4">
            <h4>ExamMaster</h4>
        </div>
        <nav>
            <a href="dashboard.php" class="sidebar-link">
                <i class='bx bxs-dashboard'></i> Dashboard
            </a>
            <a href="create_exam.php" class="sidebar-link active">
                <i class='bx bx-plus-circle'></i> Create Exam
            </a>
            <a href="my_exams.php" class="sidebar-link">
                <i class='bx bx-book'></i> My Exams
            </a>
            <a href="../auth/logout.php" class="sidebar-link">
                <i class='bx bx-log-out'></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2 class="mb-4">Create New Exam</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form id="examForm" method="POST" action="" enctype="multipart/form-data">
                <!-- Exam Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4>Exam Details</h4>
                        <div class="mb-3">
                            <label for="title" class="form-label">Exam Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (minutes)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="publish" name="publish">
                            <label class="form-check-label" for="publish">Publish exam immediately</label>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <h4 class="mb-3">Questions</h4>
                <div id="questionsContainer">
                    <!-- Questions will be added here dynamically -->
                </div>

                <div class="mb-4">
                    <button type="button" class="btn btn-secondary me-2" onclick="addQuestion('mcq')">Add MCQ Question</button>
                    <button type="button" class="btn btn-secondary me-2" onclick="addQuestion('true_false')">Add True/False Question</button>
                    <button type="button" class="btn btn-secondary" onclick="addQuestion('open')">Add Open Question</button>
                </div>

                <div class="d-flex justify-content-between mb-5">
                    <button type="submit" class="btn btn-primary">Create Exam</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let questionCount = 0;

        function addQuestion(type) {
            const container = document.createElement('div');
            container.className = 'question-card';
            
            let html = `
                <div class="mb-3">
                    <label class="form-label">Question ${questionCount + 1}</label>
                    <input type="hidden" name="questions[${questionCount}][type]" value="${type}">
                    <input type="hidden" name="questions[${questionCount}][index]" value="${questionCount}">
                    <textarea class="form-control" name="questions[${questionCount}][text]" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Points</label>
                    <input type="number" class="form-control" name="questions[${questionCount}][points]" value="1" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image (optional)</label>
                    <div class="input-group">
                        <input type="file" class="form-control" name="questions[${questionCount}][image]" accept="image/*" onchange="previewImage(this)">
                        <button type="button" class="btn btn-outline-danger remove-image" onclick="removeImage(this)" style="display: none;">Remove Image</button>
                    </div>
                    <div class="image-preview-container">
                        <img class="question-image" alt="Preview">
                    </div>
                </div>
            `;

            if (type === 'mcq') {
                html += `
                    <div class="options-container">
                        <label class="form-label">Options</label>
                        <div id="options_${questionCount}">
                            <div class="option-container mb-2">
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input type="radio" name="questions[${questionCount}][correct_option]" value="0" required>
                                    </div>
                                    <input type="text" class="form-control" name="questions[${questionCount}][options][]" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.parentElement.remove()">Remove</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOption(${questionCount})">Add Option</button>
                    </div>
                `;
            } else if (type === 'true_false') {
                html += `
                    <div class="mb-3">
                        <label class="form-label">Correct Answer</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="questions[${questionCount}][correct_answer]" value="true" required>
                                <label class="form-check-label">True</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="questions[${questionCount}][correct_answer]" value="false" required>
                                <label class="form-check-label">False</label>
                            </div>
                        </div>
                    </div>
                `;
            }

            html += `
                <div class="text-end">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">Delete Question</button>
                </div>
            `;

            container.innerHTML = html;
            document.getElementById('questionsContainer').appendChild(container);
            questionCount++;
        }

        function addOption(questionId) {
            const optionsContainer = document.getElementById(`options_${questionId}`);
            const optionCount = optionsContainer.children.length;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'option-container mb-2';
            optionDiv.innerHTML = `
                <div class="input-group">
                    <div class="input-group-text">
                        <input type="radio" name="questions[${questionId}][correct_option]" value="${optionCount}" required>
                    </div>
                    <input type="text" class="form-control" name="questions[${questionId}][options][]" required>
                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.parentElement.remove()">Remove</button>
                </div>
            `;
            
            optionsContainer.appendChild(optionDiv);
        }

        function previewImage(input) {
            const container = input.closest('.mb-3');
            const previewContainer = container.querySelector('.image-preview-container');
            const preview = previewContainer.querySelector('img');
            const removeButton = container.querySelector('.remove-image');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    removeButton.style.display = 'inline-block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage(button) {
            const container = button.closest('.mb-3');
            const fileInput = container.querySelector('input[type="file"]');
            const previewContainer = container.querySelector('.image-preview-container');
            const preview = previewContainer.querySelector('img');
            
            fileInput.value = ''; // Clear file input
            preview.src = ''; // Clear preview
            previewContainer.style.display = 'none';
            button.style.display = 'none';
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('examForm').addEventListener('submit', function(e) {
                const startDate = new Date(document.getElementById('start_date').value);
                const endDate = new Date(document.getElementById('end_date').value);
                const now = new Date();

                if (startDate < now) {
                    e.preventDefault();
                    alert('Start date cannot be in the past');
                    return;
                }

                if (endDate <= startDate) {
                    e.preventDefault();
                    alert('End date must be after start date');
                    return;
                }
            });
        });
    </script>
</body>
</html>
