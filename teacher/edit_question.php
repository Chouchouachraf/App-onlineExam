<?php
session_start();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if question ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$question_id = $_GET['id'];
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();

        try {
            // Update question
            $stmt = $conn->prepare("
                UPDATE questions 
                SET question_text = ?,
                    points = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['question_text'],
                $_POST['points'],
                $question_id
            ]);

            // Handle image upload if provided
            if (isset($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
                $target_dir = "../uploads/questions/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
                $image_path = $target_dir . uniqid() . '.' . $file_extension;
                
                if (move_uploaded_file($_FILES['question_image']['tmp_name'], $image_path)) {
                    $stmt = $conn->prepare("UPDATE questions SET image_path = ? WHERE id = ?");
                    $stmt->execute([$image_path, $question_id]);
                }
            }

            // Handle options for MCQ questions
            if ($_POST['question_type'] === 'mcq') {
                // Delete existing options
                $stmt = $conn->prepare("DELETE FROM question_options WHERE question_id = ?");
                $stmt->execute([$question_id]);

                // Add new options
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
            // Handle true/false options
            else if ($_POST['question_type'] === 'true_false') {
                // Delete existing options
                $stmt = $conn->prepare("DELETE FROM question_options WHERE question_id = ?");
                $stmt->execute([$question_id]);

                // Add new options
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
            $success_message = "Question updated successfully!";
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Error updating question: " . $e->getMessage();
        }
    }

    // Get question details
    $stmt = $conn->prepare("
        SELECT q.*, 
               GROUP_CONCAT(qo.option_text) as options,
               GROUP_CONCAT(qo.is_correct) as correct_answers,
               e.title as exam_title
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        JOIN exams e ON q.exam_id = e.id
        WHERE q.id = ? AND e.created_by = ?
        GROUP BY q.id
    ");
    $stmt->execute([$question_id, $_SESSION['user_id']]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$question) {
        header("Location: dashboard.php");
        exit();
    }

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - ExamMaster</title>
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
            <h2 class="mb-4">Edit Question</h2>
            <p class="text-muted">Exam: <?php echo htmlspecialchars($question['exam_title']); ?></p>

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

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="question_type" value="<?php echo $question['question_type']; ?>">

                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="<?php echo $question['points']; ?>" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="question_image" class="form-label">Question Image</label>
                            <?php if ($question['image_path']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($question['image_path']); ?>" alt="Current Question Image" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="question_image" name="question_image" accept="image/*">
                            <small class="form-text text-muted">Leave empty to keep current image</small>
                        </div>

                        <?php if ($question['question_type'] === 'mcq'): ?>
                            <div id="mcq_options" class="mb-3">
                                <label class="form-label">Options</label>
                                <div id="options_container">
                                    <?php 
                                    $options = explode(',', $question['options']);
                                    $correct_answers = explode(',', $question['correct_answers']);
                                    foreach ($options as $index => $option): 
                                    ?>
                                        <div class="input-group mb-2">
                                            <div class="input-group-text">
                                                <input type="radio" 
                                                       name="correct_option" 
                                                       value="<?php echo $index; ?>" 
                                                       <?php echo $correct_answers[$index] ? 'checked' : ''; ?> 
                                                       required>
                                            </div>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="options[]" 
                                                   value="<?php echo htmlspecialchars($option); ?>" 
                                                   required>
                                            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addOption()">Add Option</button>
                            </div>
                        <?php elseif ($question['question_type'] === 'true_false'): ?>
                            <div class="mb-3">
                                <label class="form-label">Correct Answer</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="correct_answer" 
                                               value="true" 
                                               <?php echo strpos($question['correct_answers'], '1,0') !== false ? 'checked' : ''; ?> 
                                               required>
                                        <label class="form-check-label">True</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="correct_answer" 
                                               value="false" 
                                               <?php echo strpos($question['correct_answers'], '0,1') !== false ? 'checked' : ''; ?> 
                                               required>
                                        <label class="form-check-label">False</label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Update Question</button>
                            <a href="add_questions.php?exam_id=<?php echo $question['exam_id']; ?>" class="btn btn-secondary">Back to Questions</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
