<?php 
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Enseignant') {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch subjects and classes from the database
require '../../config/connection.php';
$querySubjects = $conn->query("SELECT id, name FROM subjects");
$queryClasses = $conn->query("SELECT id, name FROM classes");

$subjects = $querySubjects->fetchAll(PDO::FETCH_ASSOC);
$classes = $queryClasses->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examTitle = $_POST['exam_title'];
    $subjectId = $_POST['subject'];
    $classId = $_POST['class'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    $teacherId = $_SESSION['user_id'];
    $examDate = $_POST['exam_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $totalPoints = $_POST['total_points'];

    // Insert exam details
    $stmt = $conn->prepare("
        INSERT INTO exams (title, subject_id, class_id, teacher_id, duration, total_points, exam_date, start_time, end_time, instructions, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$examTitle, $subjectId, $classId, $teacherId, $duration, $totalPoints, $examDate, $startTime, $endTime, $instructions, 'published']);
    $examId = $conn->lastInsertId();

    // Insert questions
    if (isset($_POST['questions'])) {
        foreach ($_POST['questions'] as $question) {
            $questionText = $question['text'];
            $questionType = $question['type'];
            $points = $question['points'];

            $stmt = $conn->prepare("
                INSERT INTO questions (exam_id, question_text, question_type, points)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$examId, $questionText, $questionType, $points]);
            $questionId = $conn->lastInsertId();

            if ($questionType === 'qcm' && isset($question['options'])) {
                foreach ($question['options'] as $index => $optionText) {
                    $isCorrect = isset($question['correct_options'][$index]) ? 1 : 0;
                    $stmt = $conn->prepare("
                        INSERT INTO question_options (question_id, option_text, is_correct)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$questionId, $optionText, $isCorrect]);
                }
            }
        }
    }

    header("Location: ../dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Créer un Examen</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f4f8;
        }

        .navbar {
            background-color: #1a365d;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .container {
            display: flex;
            min-height: calc(100vh - 64px);
        }

        .sidebar {
            width: 250px;
            background-color: white;
            padding: 2rem;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-icon {
            width: 80px;
            height: 80px;
            background-color: #4a5568;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .nav-item {
            padding: 0.75rem 1rem;
            color: #1a365d;
            text-decoration: none;
            border-radius: 4px;
        }

        .nav-item:hover {
            background-color: #f0f4f8;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .page-title {
            margin-bottom: 2rem;
            color: #1a365d;
        }

        .exam-form {
            background-color: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: bold;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #1a365d;
            color: white;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #4a5568;
        }

        .question-container {
            margin-top: 2rem;
            border-top: 2px solid #e2e8f0;
            padding-top: 2rem;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .add-question-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }

        .question-item {
            background-color: #f8fafc;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .points-input {
            width: 100px;
        }

        .delete-question {
            color: #dc3545;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">ExamMaster</div>
        <a href="logout.php" style="color: white; text-decoration: none;">Déconnexion</a>
    </nav>

    <div class="container">
        <div class="sidebar">
            <!-- Sidebar with menu -->
        </div>

        <div class="main-content">
            <h1 class="page-title">Créer un Nouvel Examen</h1>

            <form class="exam-form" action="" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="exam-title">Titre de l'examen</label>
                        <input type="text" id="exam-title" name="exam_title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Matière</label>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="">Sélectionner une matière</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject['id']); ?>">
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="class">Classe</label>
                        <select id="class" name="class" class="form-control" required>
                            <option value="">Sélectionner une classe</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo htmlspecialchars($class['id']); ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="duration">Durée (minutes)</label>
                        <input type="number" id="duration" name="duration" class="form-control" min="15" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="exam_date">Date de l'examen</label>
                        <input type="date" id="exam_date" name="exam_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="start-time">Heure de début</label>
                        <input type="time" id="start-time" name="start_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="end-time">Heure de fin</label>
                        <input type="time" id="end-time" name="end_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="total_points">Total des points</label>
                        <input type="number" id="total_points" name="total_points" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="instructions">Instructions</label>
                    <textarea id="instructions" name="instructions" class="form-control" rows="4"></textarea>
                </div>

                <div class="question-container">
                    <div class="question-header">
                        <h2>Questions</h2>
                        <button type="button" class="add-question-btn" onclick="addQuestion()">+ Ajouter une question</button>
                    </div>
                    <div id="questions-list">
                        <!-- Questions will be added here dynamically -->
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='teacher-dashboard.php'">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer l'examen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let questionCount = 0;

        function addQuestion() {
            questionCount++;
            const questionHtml = `
                <div class="question-item" id="question-${questionCount}">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div class="form-group">
                                <label>Question ${questionCount}</label>
                                <textarea name="questions[${questionCount}][text]" class="form-control" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Type de Question</label>
                                <select name="questions[${questionCount}][type]" class="form-control question-type" data-question-id="${questionCount}" required>
                                    <option value="text">Question Texte</option>
                                    <option value="qcm">Question à Choix Multiples (QCM)</option>
                                    <option value="truefalse">Vrai/Faux</option>
                                </select>
                            </div>
                            <div class="question-options-container" id="options-container-${questionCount}" style="margin-top: 1rem;"></div>
                            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                <div class="form-group" style="width: 150px;">
                                    <label>Points</label>
                                    <input type="number" name="questions[${questionCount}][points]" class="form-control points-input" min="1" required>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="delete-question" onclick="deleteQuestion(${questionCount})">×</button>
                    </div>
                </div>
            `;
            document.getElementById('questions-list').insertAdjacentHTML('beforeend', questionHtml);

            // Add event listener to handle question type change
            document.querySelector(`select[data-question-id="${questionCount}"]`).addEventListener('change', handleQuestionTypeChange);
        }

        function handleQuestionTypeChange(event) {
            const questionId = event.target.getAttribute('data-question-id');
            const questionType = event.target.value;
            const optionsContainer = document.getElementById(`options-container-${questionId}`);

            optionsContainer.innerHTML = ''; // Clear previous options

            if (questionType === 'qcm') {
                optionsContainer.innerHTML = `
                    <div class="form-group">
                        <label>Options</label>
                        <button type="button" class="add-option-btn" onclick="addOption(${questionId})">+ Ajouter une Option</button>
                        <div class="options-list" id="options-list-${questionId}"></div>
                    </div>
                `;
            } else if (questionType === 'truefalse') {
                optionsContainer.innerHTML = `
                    <div class="form-group">
                        <label>Vrai/Faux</label>
                        <select name="questions[${questionId}][truefalse]" class="form-control">
                            <option value="true">Vrai</option>
                            <option value="false">Faux</option>
                        </select>
                    </div>
                `;
            }
        }

        function addOption(questionId) {
            const optionId = Date.now(); // Unique ID for the option
            const optionsList = document.getElementById(`options-list-${questionId}`);
            const optionHtml = `
                <div class="option-item" id="option-${optionId}">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                        <input type="text" name="questions[${questionId}][options][]" class="form-control" placeholder="Option texte" required>
                        <label>
                            <input type="checkbox" name="questions[${questionId}][correct_options][]" value="${optionId}">
                            Correct
                        </label>
                        <button type="button" class="delete-option-btn" onclick="deleteOption(${optionId})">×</button>
                    </div>
                </div>
            `;
            optionsList.insertAdjacentHTML('beforeend', optionHtml);
        }

        function deleteOption(optionId) {
            const optionElement = document.getElementById(`option-${optionId}`);
            optionElement.remove();
        }

        function deleteQuestion(questionId) {
            const question = document.getElementById(`question-${questionId}`);
            question.remove();
        }
    </script>
</body>
</html>