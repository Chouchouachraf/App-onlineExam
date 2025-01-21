<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_GET['exam_id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['exam_id'];
$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get exam details
    $stmt = $conn->prepare("SELECT * FROM exams WHERE id = ? AND created_by = ?");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header("Location: dashboard.php");
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();

        try {
            $question_text = $_POST['question_text'];
            $question_type = $_POST['question_type'];
            $points = $_POST['points'];
            
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
                $target_dir = "../uploads/questions/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
                $image_path = $target_dir . uniqid() . '.' . $file_extension;
                
                move_uploaded_file($_FILES['question_image']['tmp_name'], $image_path);
            }

            // Insert question
            $stmt = $conn->prepare("
                INSERT INTO questions (exam_id, question_text, question_type, points, image_path)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$exam_id, $question_text, $question_type, $points, $image_path]);
            $question_id = $conn->lastInsertId();

            // Handle options for MCQ questions
            if ($question_type === 'mcq') {
                $options = $_POST['options'];
                $correct_option = $_POST['correct_option'];
                foreach ($options as $index => $option_text) {
                    if (trim($option_text) !== '') {
                        $stmt = $conn->prepare("
                            INSERT INTO question_options (question_id, option_text, is_correct)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([
                            $question_id,
                            $option_text,
                            $index == $correct_option ? 1 : 0
                        ]);
                    }
                }
            }
            // Handle true/false questions
            else if ($question_type === 'true_false') {
                $correct_answer = $_POST['correct_answer'];
                
                $stmt = $conn->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct)
                    VALUES (?, 'True', ?), (?, 'False', ?)
                ");
                $stmt->execute([
                    $question_id,
                    $correct_answer === 'true' ? 1 : 0,
                    $question_id,
                    $correct_answer === 'false' ? 1 : 0
                ]);
            }

            $conn->commit();
            $success_message = "Question added successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }

    // Get existing questions
    $stmt = $conn->prepare("
        SELECT q.*, 
               GROUP_CONCAT(CASE 
                   WHEN q.question_type = 'mcq' THEN qo.option_text
                   ELSE NULL
               END) as options,
               GROUP_CONCAT(qo.is_correct) as correct_answers
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - ExamMaster</title>
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
        .question-preview {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .question-image {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
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
            <h2 class="mb-4">Add Questions to <?php echo htmlspecialchars($exam['title']); ?></h2>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Add Question Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="question_type" class="form-label">Question Type</label>
                            <select class="form-select" id="question_type" name="question_type" required onchange="toggleQuestionOptions()">
                                <option value="mcq">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="open">Open Question</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="1" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="question_image" class="form-label">Question Image (optional)</label>
                            <input type="file" class="form-control" id="question_image" name="question_image" accept="image/*">
                        </div>

                        <!-- MCQ Options -->
                        <div id="mcq_options" class="mb-3">
                            <label class="form-label">Options</label>
                            <div id="options_container">
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="radio" name="correct_option" value="0" required>
                                    </div>
                                    <input type="text" class="form-control" name="options[]" required>
                                    <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addOption()">Add Option</button>
                        </div>

                        <!-- True/False Options -->
                        <div id="true_false_options" class="mb-3" style="display: none;">
                            <label class="form-label">Correct Answer</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="correct_answer" value="true" required>
                                    <label class="form-check-label">True</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="correct_answer" value="false" required>
                                    <label class="form-check-label">False</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Add Question</button>
                    </form>
                </div>
            </div>

            <!-- Existing Questions -->
            <h3 class="mb-3">Existing Questions</h3>
            <?php foreach ($questions as $question): ?>
                <div class="question-preview">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5><?php echo htmlspecialchars($question['question_text']); ?></h5>
                            <p class="text-muted">
                                Type: <?php echo ucfirst($question['question_type']); ?> |
                                Points: <?php echo $question['points']; ?>
                            </p>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteQuestion(<?php echo $question['id']; ?>)">Delete</button>
                        </div>
                    </div>
                    
                    <?php if ($question['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($question['image_path']); ?>" class="question-image" alt="Question Image">
                    <?php endif; ?>
                    
                    <?php if ($question['question_type'] === 'mcq'): ?>
                        <div class="options-list mt-2">
                            <?php 
                            $options = explode(',', $question['options']);
                            $correct_answers = explode(',', $question['correct_answers']);
                            foreach ($options as $index => $option): 
                            ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" disabled <?php echo $correct_answers[$index] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleQuestionOptions() {
            const questionType = document.getElementById('question_type').value;
            const mcqOptions = document.getElementById('mcq_options');
            const trueFalseOptions = document.getElementById('true_false_options');

            mcqOptions.style.display = questionType === 'mcq' ? 'block' : 'none';
            trueFalseOptions.style.display = questionType === 'true_false' ? 'block' : 'none';

            // Update form validation
            const optionInputs = document.querySelectorAll('[name="options[]"]');
            const correctOption = document.querySelectorAll('[name="correct_option"]');
            const trueFalseInputs = document.querySelectorAll('[name="correct_answer"]');

            optionInputs.forEach(input => input.required = questionType === 'mcq');
            correctOption.forEach(input => input.required = questionType === 'mcq');
            trueFalseInputs.forEach(input => input.required = questionType === 'true_false');
        }

        function addOption() {
            const container = document.getElementById('options_container');
            const optionCount = container.children.length;

            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <div class="input-group-text">
                    <input type="radio" name="correct_option" value="${optionCount}" required>
                </div>
                <input type="text" class="form-control" name="options[]" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">Remove</button>
            `;

            container.appendChild(div);
        }

        function deleteQuestion(questionId) {
            if (confirm('Are you sure you want to delete this question?')) {
                window.location.href = `delete_question.php?id=${questionId}&exam_id=<?php echo $exam_id; ?>`;
            }
        }

        // Initialize the form
        toggleQuestionOptions();
    </script>
</body>
</html>
