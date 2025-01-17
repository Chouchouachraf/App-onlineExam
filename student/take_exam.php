<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if exam ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$exam_id = $_GET['id'];
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if exam is available
    $stmt = $conn->prepare("
        SELECT e.*, u.nom as teacher_nom, u.prenom as teacher_prenom
        FROM exams e
        JOIN users u ON e.created_by = u.id
        WHERE e.id = ? 
        AND e.start_date <= NOW() 
        AND e.end_date >= NOW()
        AND (
            e.class_id IS NULL 
            OR e.class_id = (SELECT class_id FROM users WHERE id = ?)
        )
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        $_SESSION['error'] = "This exam is not available.";
        header("Location: exam_schedule.php");
        exit();
    }

    // Check if student has already submitted this exam
    $stmt = $conn->prepare("
        SELECT * FROM exam_attempts 
        WHERE exam_id = ? 
        AND student_id = ? 
        AND submitted_at IS NOT NULL
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "You have already submitted this exam.";
        header("Location: exam_schedule.php");
        exit();
    }

    // Get or create exam attempt
    $stmt = $conn->prepare("
        SELECT * FROM exam_attempts 
        WHERE exam_id = ? 
        AND student_id = ? 
        AND submitted_at IS NULL
        ORDER BY started_at DESC LIMIT 1
    ");
    $stmt->execute([$exam_id, $_SESSION['user_id']]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt) {
        // Create new attempt
        $stmt = $conn->prepare("
            INSERT INTO exam_attempts (exam_id, student_id, started_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$exam_id, $_SESSION['user_id']]);
        $attempt_id = $conn->lastInsertId();
    } else {
        $attempt_id = $attempt['id'];
    }

    // Get exam questions
    $stmt = $conn->prepare("
        SELECT q.*, 
               GROUP_CONCAT(qo.id) as option_ids,
               GROUP_CONCAT(qo.option_text) as options
        FROM questions q
        LEFT JOIN question_options qo ON q.id = qo.question_id
        WHERE q.exam_id = ?
        GROUP BY q.id
        ORDER BY q.created_at
    ");
    $stmt->execute([$exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Calculate time remaining
$end_time = strtotime($exam['end_date']);
$current_time = time();
$time_remaining = $end_time - $current_time;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exam['title']); ?> - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .exam-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.2em;
            z-index: 1000;
        }
        .question-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .question-image {
            max-width: 100%;
            max-height: 300px;
            margin: 10px 0;
        }
        .option-label {
            display: block;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .option-label:hover {
            background-color: #f8f9fa;
        }
        .option-input:checked + .option-label {
            background-color: #e9ecef;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="timer" id="timer"></div>

    <div class="exam-container">
        <div class="mb-4">
            <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
            <p class="text-muted">
                By: <?php echo htmlspecialchars("{$exam['teacher_prenom']} {$exam['teacher_nom']}"); ?><br>
                Duration: <?php echo $exam['duration']; ?> minutes
            </p>
            <?php if ($exam['description']): ?>
                <p><?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
            <?php endif; ?>
        </div>

        <form id="examForm" method="POST" action="submit_exam.php">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <h5>Question <?php echo $index + 1; ?></h5>
                    <p><?php echo nl2br(htmlspecialchars($question['question_text'])); ?></p>
                    
                    <?php if ($question['image_path']): ?>
                        <img src="<?php echo htmlspecialchars($question['image_path']); ?>" class="question-image" alt="Question Image">
                    <?php endif; ?>

                    <?php if ($question['question_type'] === 'mcq'): ?>
                        <div class="options">
                            <?php 
                            $options = explode(',', $question['options']);
                            $option_ids = explode(',', $question['option_ids']);
                            foreach ($options as $option_index => $option): 
                            ?>
                                <div class="option">
                                    <input type="radio" 
                                           id="q<?php echo $question['id']; ?>_<?php echo $option_index; ?>" 
                                           name="answers[<?php echo $question['id']; ?>]" 
                                           value="<?php echo $option_ids[$option_index]; ?>"
                                           class="option-input d-none"
                                           required>
                                    <label for="q<?php echo $question['id']; ?>_<?php echo $option_index; ?>" 
                                           class="option-label">
                                        <?php echo htmlspecialchars($option); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($question['question_type'] === 'true_false'): ?>
                        <div class="options">
                            <div class="option">
                                <input type="radio" 
                                       id="q<?php echo $question['id']; ?>_true" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       value="true"
                                       class="option-input d-none"
                                       required>
                                <label for="q<?php echo $question['id']; ?>_true" class="option-label">True</label>
                            </div>
                            <div class="option">
                                <input type="radio" 
                                       id="q<?php echo $question['id']; ?>_false" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       value="false"
                                       class="option-input d-none"
                                       required>
                                <label for="q<?php echo $question['id']; ?>_false" class="option-label">False</label>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <textarea class="form-control" 
                                      name="answers[<?php echo $question['id']; ?>]" 
                                      rows="4" 
                                      required></textarea>
                        </div>
                    <?php endif; ?>

                    <div class="text-muted mt-2">
                        Points: <?php echo $question['points']; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Submit Exam</button>
            </div>
        </form>
    </div>

    <script>
        // Timer functionality
        const endTime = <?php echo $end_time * 1000; ?>;
        
        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = endTime - now;
            
            if (timeLeft <= 0) {
                document.getElementById('timer').innerHTML = "Time's up!";
                document.getElementById('examForm').submit();
                return;
            }
            
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            document.getElementById('timer').innerHTML = 
                (hours > 0 ? hours + "h " : "") + 
                (minutes < 10 ? "0" : "") + minutes + "m " + 
                (seconds < 10 ? "0" : "") + seconds + "s";
        }

        // Update timer every second
        setInterval(updateTimer, 1000);
        updateTimer();

        // Form submission confirmation
        document.getElementById('examForm').onsubmit = function(e) {
            return confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.');
        };
    </script>
</body>
</html>
