<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Etudiant') {
    header("Location: ../../auth/login.php");
    exit();
}

require '../../config/connection.php';

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$studentId = $_SESSION['user_id'];

// Vérifier si l'étudiant a déjà passé cet examen
$stmt = $conn->prepare("
    SELECT id FROM exam_results 
    WHERE exam_id = ? AND student_id = ?
");
$stmt->execute([$examId, $studentId]);
if ($stmt->fetch()) {
    header("Location: exam_list.php?error=already_taken");
    exit();
}

// Récupérer les détails de l'examen
$stmt = $conn->prepare("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.id = ?
");
$stmt->execute([$examId]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    header("Location: exam_list.php?error=not_found");
    exit();
}

// Adjust the concatenation to avoid double time specification
$examDateTimeInput = date('Y-m-d', strtotime($exam['exam_date'])) . ' ' . $exam['start_time'];
$examEndDateTimeInput = date('Y-m-d', strtotime($exam['exam_date'])) . ' ' . $exam['end_time'];

// Debugging (optional)
error_log("Start time input: " . $examDateTimeInput);
error_log("End time input: " . $examEndDateTimeInput);

// Correct instantiation
$examDateTime = new DateTime($examDateTimeInput);
$examEndDateTime = new DateTime($examEndDateTimeInput);


// Vérifier si l'examen est actif
$currentDateTime = new DateTime();


if ($currentDateTime > $examEndDateTime) {
    header("Location: exam_list.php?error=expired");
    exit();
}

if ($currentDateTime < $examDateTime) {
    header("Location: exam_list.php?error=not_started");
    exit();
}

// Récupérer les questions
$stmt = $conn->prepare("
    SELECT q.*, GROUP_CONCAT(qo.id,'::',qo.option_text SEPARATOR '||') as options 
    FROM questions q 
    LEFT JOIN question_options qo ON q.id = qo.question_id 
    WHERE q.exam_id = ? 
    GROUP BY q.id
");
$stmt->execute([$examId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examen en cours - <?php echo htmlspecialchars($exam['title']); ?></title>
</head>
<body>
    <div class="exam-container">
        <div class="exam-header">
            <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
            <div id="timer" class="timer"></div>
        </div>

        <div class="exam-info">
            <p><strong>Matière:</strong> <?php echo htmlspecialchars($exam['subject_name']); ?></p>
            <p><strong>Durée:</strong> <?php echo $exam['duration']; ?> minutes</p>
            <p><strong>Points:</strong> <?php echo $exam['total_points']; ?></p>
        </div>

        <form id="exam-form" action="submit_exam.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
            
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-box">
                    <h3>Question <?php echo $index + 1; ?></h3>
                    <p class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></p>
                    
                    <?php if ($question['question_type'] === 'qcm'): ?>
                        <div class="options-list">
                            <?php 
                            $options = explode('||', $question['options']);
                            foreach ($options as $option):
                                list($optionId, $optionText) = explode('::', $option);
                            ?>
                                <label class="option-item">
                                    <input type="checkbox" 
                                           name="answers[<?php echo $question['id']; ?>][]" 
                                           value="<?php echo $optionId; ?>">
                                    <?php echo htmlspecialchars($optionText); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($question['question_type'] === 'text'): ?>
                        <textarea name="answers[<?php echo $question['id']; ?>]" 
                                  class="answer-text" 
                                  rows="4"></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="exam-actions">
                <button type="submit" class="submit-button">Soumettre l'examen</button>
            </div>
        </form>
    </div>

    <script>
        // Configuration du minuteur
        const endTime = new Date('<?php echo $exam['exam_date'] . ' ' . $exam['end_time']; ?>').getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = endTime - now;

            if (timeLeft <= 0) {
                document.getElementById('timer').innerHTML = "Temps écoulé!";
                document.getElementById('exam-form').submit();
                return;
            }

            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById('timer').innerHTML = 
                `Temps restant: ${hours}h ${minutes}m ${seconds}s`;
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>
</html>