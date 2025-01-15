<?php
session_start();

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../../auth/login.php");
    exit();
}

// Correct way to include the connection.php file with __DIR__
require_once(__DIR__ . '/../../config/connection.php');

// Ensure the `exam_id` is valid
$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$studentId = $_SESSION['user_id'];

// Check if the student has already taken this exam
$stmt = $conn->prepare("SELECT id FROM exam_results WHERE exam_id = ? AND student_id = ?");
$stmt->execute([$examId, $studentId]);
if ($stmt->fetch()) {
    header("Location: exam_list.php?error=already_taken");
    exit();
}

// Fetch exam details
$stmt = $conn->prepare("
    SELECT e.*, s.name as subject_name, t.full_name as teacher_name, c.name as class_name 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    JOIN teachers t ON e.teacher_id = t.id
    JOIN classes c ON e.class_id = c.id
    WHERE e.id = ?
");
$stmt->execute([$examId]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: exam_list.php?error=not_found");
    exit();
}

// Validate exam date and time
try {
    $examDate = trim($exam['exam_date']);
    $startTime = trim($exam['start_time']);
    $endTime = trim($exam['end_time']);
    
    if (empty($examDate) || empty($startTime) || empty($endTime)) {
        throw new Exception("Date ou heure manquante dans la base de données.");
    }
    
    $examDate = date('Y-m-d', strtotime($examDate));
    $examStartDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $examDate . ' ' . $startTime);
    $examEndDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $examDate . ' ' . $endTime);
    $currentDateTime = new DateTime();
    
    if ($examStartDateTime === false || $examEndDateTime === false) {
        throw new Exception("Format de date ou d'heure invalide.");
    }
} catch (Exception $e) {
    die("Erreur lors de l'analyse de la date ou de l'heure : " . $e->getMessage());
}

// Check if the exam is accessible
if ($currentDateTime > $examEndDateTime) {
    header("Location: exam_list.php?error=expired");
    exit();
}

if ($currentDateTime < $examStartDateTime) {
    header("Location: exam_list.php?error=not_started");
    exit();
}

// Fetch questions for the exam
$stmt = $conn->prepare("
    SELECT q.*, GROUP_CONCAT(qo.id,'::',qo.option_text,'::',qo.is_correct SEPARATOR '||') as options 
    FROM questions q 
    LEFT JOIN question_options qo ON q.id = qo.question_id 
    WHERE q.exam_id = ? 
    GROUP BY q.id
    ORDER BY q.id ASC
");
$stmt->execute([$examId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate remaining time for the exam
$remainingTime = $examEndDateTime->getTimestamp() - $currentDateTime->getTimestamp();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - ExamMaster</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        /* Your CSS styling */
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2d5a9e;
            --accent-color: #4299e1;
            --success-color: #48bb78;
            --warning-color: #ed8936;
            --danger-color: #e53e3e;
            --background-color: #f7fafc;
            --text-color: #2d3748;
            --border-color: #e2e8f0;
            --header-height: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: var(--background-color);
            color: var(--text-color);
            padding-top: var(--header-height);
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 2rem;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .exam-header {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease-out;
        }

        .exam-title {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .exam-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: #f8fafc;
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .meta-item i {
            font-size: 1.2rem;
            color: var(--accent-color);
            width: 24px;
            text-align: center;
        }

        .meta-label {
            font-weight: 500;
            color: var(--primary-color);
        }

        .timer {
            position: fixed;
            top: calc(var(--header-height) + 1rem);
            right: 1rem;
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: slideInRight 0.5s ease-out;
            z-index: 90;
        }

        .timer i {
            color: var(--warning-color);
        }

        .questions-container {
            margin-top: 2rem;
        }

        .question {
            background-color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .question-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .points-badge {
            background-color: var(--accent-color);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .question-text {
            font-size: 1.1rem;
            color: var(--text-color);
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }

        .options-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .option-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
        }

        .option-item:hover {
            background-color: #f8fafc;
            border-color: var(--accent-color);
        }

        .option-item input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .submit-container {
            position: sticky;
            bottom: 0;
            background-color: white;
            padding: 1rem 2rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
        }

        .submit-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #38a169;
            transform: translateY(-1px);
        }

        .loading-spinner {
            display: none;
            margin-left: 1rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(0);
            }
        }

    </style>
</head>
<body>
    <header class="header">
        <div class="logo">ExamMaster</div>
    </header>

    <div class="progress-bar" id="progressBar"></div>
    <div class="save-indicator" id="saveIndicator">Réponses sauvegardées</div>

    <div class="timer">
        <i class="fas fa-clock"></i>
        <span id="timer"></span>
    </div>

    <div class="container">
        <div class="exam-header">
            <h1 class="exam-title"><?php echo htmlspecialchars($exam['title']); ?></h1>
            
            <div class="exam-meta">
                <div class="meta-item">
                    <i class="fas fa-book"></i>
                    <div>
                        <div class="meta-label">Matière</div>
                        <div><?php echo htmlspecialchars($exam['subject_name']); ?></div>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user-tie"></i>
                    <div>
                        <div class="meta-label">Enseignant</div>
                        <div><?php echo htmlspecialchars($exam['teacher_name']); ?></div>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fas fa-chalkboard"></i>
                    <div>
                        <div class="meta-label">Classe</div>
                        <div><?php echo htmlspecialchars($exam['class_name']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <form id="examForm">
            <div class="questions-container">
                <?php foreach ($questions as $question): ?>
                    <div class="question">
                        <div class="question-header">
                            <div class="question-number">Question <?php echo $question['id']; ?></div>
                            <div class="points-badge"><?php echo $question['points']; ?> pts</div>
                        </div>
                        <div class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></div>

                        <div class="options-container">
                            <?php 
                            $options = explode('||', $question['options']);
                            foreach ($options as $option) {
                                list($optionId, $optionText, $isCorrect) = explode('::', $option);
                            ?>
                                <div class="option-item">
                                    <input type="radio" name="question_<?php echo $question['id']; ?>" value="<?php echo $optionId; ?>" class="answer">
                                    <div class="option-text"><?php echo htmlspecialchars($optionText); ?></div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="submit-container">
                <button class="submit-btn" id="submitExam">
                    Soumettre l'examen
                    <span class="loading-spinner" id="loadingSpinner"><i class="fas fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </form>
    </div>

    <script>
        // Timer functionality
        let remainingTime = <?php echo $remainingTime; ?>;
        let timerElement = document.getElementById('timer');

        function formatTime(seconds) {
            let minutes = Math.floor(seconds / 60);
            let secs = seconds % 60;
            return `${minutes}:${secs < 10 ? '0' + secs : secs}`;
        }

        function updateTimer() {
            if (remainingTime <= 0) {
                document.getElementById('submitExam').disabled = true;
                timerElement.innerText = "Temps écoulé!";
                return;
            }
            timerElement.innerText = formatTime(remainingTime);
            remainingTime--;
        }

        setInterval(updateTimer, 1000);

        // Save answers every 10 seconds
        let saveIndicator = document.getElementById('saveIndicator');
        let answers = [];

        document.querySelectorAll('.answer').forEach((input) => {
            input.addEventListener('change', () => {
                let questionId = input.name.split('_')[1];
                answers[questionId] = input.value;

                clearTimeout(saveIndicatorTimeout);
                saveIndicator.style.display = 'block';
                saveIndicatorTimeout = setTimeout(() => {
                    saveIndicator.style.display = 'none';
                }, 2000);
            });
        });

        // Submit exam
        document.getElementById('examForm').addEventListener('submit', (e) => {
            e.preventDefault();
            document.getElementById('submitExam').disabled = true;
            document.getElementById('loadingSpinner').style.display = 'inline-block';

            // Here you would normally send answers to the server
            setTimeout(() => {
                alert('Examen soumis avec succès!');
                window.location.href = 'exam_list.php';
            }, 2000);
        });
    </script>
</body>
</html>
