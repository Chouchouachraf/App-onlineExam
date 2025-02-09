<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get teacher's classrooms
try {
    $stmt = $conn->prepare("
        SELECT c.* 
        FROM classrooms c 
        WHERE c.teacher_id = ?
        ORDER BY c.name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Error fetching classrooms: " . $e->getMessage();
    $classrooms = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $subject = $_POST['subject'];
        $total_marks = $_POST['total_marks'];
        $passing_score = $_POST['passing_score'] ?? 60;
        $classroom_ids = $_POST['classroom_ids'] ?? [];

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
                subject,
                total_marks,
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
            $subject,
            $total_marks,
            $passing_score
        ]);

        $exam_id = $conn->lastInsertId();

        // Link exam to selected classrooms
        if (!empty($classroom_ids)) {
            $stmt = $conn->prepare("
                INSERT INTO exam_classrooms (exam_id, classroom_id)
                VALUES (?, ?)
            ");
            foreach ($classroom_ids as $classroom_id) {
                $stmt->execute([$exam_id, $classroom_id]);
            }
        }

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

                // Insert question
                $stmt = $conn->prepare("
                    INSERT INTO questions (exam_id, question_text, question_type, points, image_path)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $exam_id,
                    $q['text'],
                    $q['type'],
                    $q['points'],
                    $image_path
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
        $success_message = "Exam created successfully and assigned to selected classrooms!";
        
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .select2-container {
            width: 100% !important;
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="mb-4">Create New Exam</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="examForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Exam Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Duration (minutes)</label>
                                <input type="number" class="form-control" name="duration" required min="1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Marks</label>
                                <input type="number" class="form-control" name="total_marks" required min="1">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input type="datetime-local" class="form-control" name="start_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input type="datetime-local" class="form-control" name="end_date" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Passing Score (%)</label>
                                <input type="number" class="form-control" name="passing_score" value="60" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Select Classrooms</label>
                                <select class="form-control select2" name="classroom_ids[]" multiple required>
                                    <?php foreach ($classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>">
                                            <?php echo htmlspecialchars($classroom['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h4>Questions</h4>
                        <div class="mb-3">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary" onclick="addQuestion('mcq')">
                                    <i class='bx bx-list-check'></i> Add MCQ
                                </button>
                                <button type="button" class="btn btn-info" onclick="addQuestion('true_false')">
                                    <i class='bx bx-check-circle'></i> Add True/False
                                </button>
                                <button type="button" class="btn btn-success" onclick="addQuestion('short_answer')">
                                    <i class='bx bx-text'></i> Add Short Answer
                                </button>
                            </div>
                        </div>

                        <div id="questions-container">
                            <!-- Questions will be added here dynamically -->
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save'></i> Create Exam
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 for multiple classroom selection
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Select classrooms...',
                allowClear: true
            });
        });

        let questionCount = 0;

        function addQuestion(type) {
            const container = document.createElement('div');
            container.className = 'question-card';
            container.id = `question_${questionCount}`;

            let html = `
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Question Text</label>
                        <textarea class="form-control" name="questions[${questionCount}][text]" required></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Points</label>
                        <input type="number" class="form-control" name="questions[${questionCount}][points]" required min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Image (Optional)</label>
                        <input type="file" class="form-control" name="questions[${questionCount}][image]" accept="image/*" onchange="previewImage(this, ${questionCount})">
                        <div id="image_preview_${questionCount}" class="image-preview-container">
                            <img src="" class="question-image" alt="Preview">
                        </div>
                    </div>
                </div>
            `;

            if (type === 'mcq') {
                html += `
                    <div class="mb-3">
                        <label class="form-label">Options</label>
                        <div id="options_${questionCount}">
                            <div class="input-group mb-2">
                                <div class="input-group-text">
                                    <input type="radio" name="questions[${questionCount}][correct_option]" value="0" required>
                                </div>
                                <input type="text" class="form-control" name="questions[${questionCount}][options][]" required>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="addOption(${questionCount})">
                            Add Option
                        </button>
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
                <input type="hidden" name="questions[${questionCount}][type]" value="${type}">
                <input type="hidden" name="questions[${questionCount}][index]" value="${questionCount}">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(${questionCount})">
                    Remove Question
                </button>
            `;

            container.innerHTML = html;
            document.getElementById('questions-container').appendChild(container);
            questionCount++;
        }

        function addOption(questionId) {
            const optionsContainer = document.getElementById(`options_${questionId}`);
            const optionCount = optionsContainer.children.length;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'input-group mb-2';
            optionDiv.innerHTML = `
                <div class="input-group-text">
                    <input type="radio" name="questions[${questionId}][correct_option]" value="${optionCount}" required>
                </div>
                <input type="text" class="form-control" name="questions[${questionId}][options][]" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                    <i class='bx bx-trash'></i>
                </button>
            `;
            
            optionsContainer.appendChild(optionDiv);
        }

        function removeQuestion(questionId) {
            document.getElementById(`question_${questionId}`).remove();
        }

        function previewImage(input, questionId) {
            const preview = document.getElementById(`image_preview_${questionId}`);
            const image = preview.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    image.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                image.src = '';
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
