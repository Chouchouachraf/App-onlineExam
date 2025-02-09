<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

// Check if submission ID is provided
if (!isset($_GET['submission_id'])) {
    header("Location: dashboard.php");
    exit();
}

$submission_id = $_GET['submission_id'];
$teacher_id = $_SESSION['user_id'];

try {
    // Get submission details with exam and student information
    $stmt = $conn->prepare("
        SELECT 
            es.*,
            e.title as exam_title,
            e.subject as exam_subject,
            e.total_marks as exam_total_marks,
            e.created_by,
            u.nom as student_nom,
            u.prenom as student_prenom,
            u.email as student_email
        FROM exam_submissions es
        JOIN exams e ON es.exam_id = e.id
        JOIN users u ON es.student_id = u.id
        WHERE es.id = ? AND e.created_by = ?
    ");
    $stmt->execute([$submission_id, $teacher_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission) {
        $_SESSION['error'] = "Submission not found or you don't have permission to grade it.";
        header("Location: dashboard.php");
        exit();
    }

    // Get all questions and student answers
    $stmt = $conn->prepare("
        SELECT 
            eq.*,
            sa.id as answer_id,
            sa.student_answer,
            sa.marks_obtained,
            sa.teacher_comment
        FROM exam_questions eq
        LEFT JOIN student_answers sa ON eq.id = sa.question_id AND sa.submission_id = ?
        WHERE eq.exam_id = ?
        ORDER BY eq.question_number
    ");
    $stmt->execute([$submission_id, $submission['exam_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission for grading
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->beginTransaction();
        
        try {
            $total_score = 0;
            $total_possible = 0;
            
            foreach ($questions as $question) {
                $answer_id = $_POST['answer_id'][$question['id']] ?? null;
                $marks = $_POST['marks'][$question['id']] ?? 0;
                $comment = $_POST['comment'][$question['id']] ?? '';
                
                // Update or insert student answer
                if ($answer_id) {
                    $stmt = $conn->prepare("
                        UPDATE student_answers 
                        SET marks_obtained = ?, teacher_comment = ?, graded_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$marks, $comment, $answer_id]);
                }
                
                $total_score += $marks;
                $total_possible += $question['marks'];
            }
            
            // Update submission status and total score
            $stmt = $conn->prepare("
                UPDATE exam_submissions 
                SET status = 'graded', 
                    total_score = ?, 
                    teacher_feedback = ?,
                    graded_at = NOW(),
                    graded_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$total_score, $_POST['overall_feedback'] ?? '', $teacher_id, $submission_id]);
            
            $conn->commit();
            $_SESSION['success'] = "Submission graded successfully! Total score: $total_score/$total_possible";
            header("Location: view_submissions.php?exam_id=" . $submission['exam_id']);
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Error while saving grades: " . $e->getMessage();
        }
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}

// Calculate current total
$current_total = 0;
$max_total = 0;
foreach ($questions as $q) {
    $current_total += ($q['marks_obtained'] ?? 0);
    $max_total += $q['marks'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submission - <?php echo htmlspecialchars($submission['exam_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .answer-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .answer-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
        }
        .grade-controls {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }
        .running-total {
            position: sticky;
            top: 1rem;
            z-index: 1020;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Grade Submission</h2>
                <div>
                    <a href="view_submissions.php?exam_id=<?php echo $submission['exam_id']; ?>" class="btn btn-outline-primary">
                        <i class='bx bx-arrow-back'></i> Back to Submissions
                    </a>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Submission Details</h5>
                                <span class="badge <?php echo $submission['status'] === 'graded' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Exam:</strong> <?php echo htmlspecialchars($submission['exam_title']); ?></p>
                                    <p class="mb-1"><strong>Subject:</strong> <?php echo htmlspecialchars($submission['exam_subject']); ?></p>
                                    <p class="mb-1"><strong>Student:</strong> <?php echo htmlspecialchars($submission['student_nom'] . ' ' . $submission['student_prenom']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($submission['student_email']); ?></p>
                                    <p class="mb-1"><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($submission['submission_time'])); ?></p>
                                    <?php if ($submission['status'] === 'graded'): ?>
                                        <p class="mb-1"><strong>Graded:</strong> <?php echo date('M d, Y H:i', strtotime($submission['graded_at'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="" id="gradeForm">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Question <?php echo $index + 1; ?></h5>
                                    <span class="badge bg-primary"><?php echo $question['marks']; ?> marks</span>
                                </div>
                                <div class="card-body">
                                    <div class="question-text mb-4">
                                        <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                                    </div>
                                    
                                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                                        <div class="mb-4">
                                            <strong>Options:</strong>
                                            <ul class="list-group">
                                                <?php foreach (json_decode($question['options'], true) as $option): ?>
                                                    <li class="list-group-item">
                                                        <?php echo htmlspecialchars($option); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>

                                    <div class="answer-comparison">
                                        <div class="answer-box">
                                            <h6 class="text-success mb-2">Correct Answer</h6>
                                            <?php echo nl2br(htmlspecialchars($question['correct_answer'])); ?>
                                        </div>
                                        <div class="answer-box">
                                            <h6 class="text-primary mb-2">Student's Answer</h6>
                                            <?php echo nl2br(htmlspecialchars($question['student_answer'] ?? 'No answer provided')); ?>
                                        </div>
                                    </div>

                                    <input type="hidden" name="answer_id[<?php echo $question['id']; ?>]" 
                                           value="<?php echo $question['answer_id']; ?>">

                                    <div class="grade-controls">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Marks (max: <?php echo $question['marks']; ?>)</label>
                                                    <input type="number" class="form-control marks-input" 
                                                           name="marks[<?php echo $question['id']; ?>]"
                                                           min="0" max="<?php echo $question['marks']; ?>"
                                                           value="<?php echo $question['marks_obtained'] ?? 0; ?>"
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="mb-3">
                                                    <label class="form-label">Feedback for Student</label>
                                                    <textarea class="form-control" 
                                                              name="comment[<?php echo $question['id']; ?>]"
                                                              rows="2"
                                                              placeholder="Provide constructive feedback..."><?php echo htmlspecialchars($question['teacher_comment'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Overall Feedback</h5>
                                <textarea class="form-control" name="overall_feedback" rows="3"
                                          placeholder="Provide overall feedback about the exam performance..."
                                          ><?php echo htmlspecialchars($submission['teacher_feedback'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mb-4">
                            <a href="view_submissions.php?exam_id=<?php echo $submission['exam_id']; ?>" 
                               class="btn btn-outline-secondary">
                                <i class='bx bx-arrow-back'></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-check-circle'></i> Save Grades
                            </button>
                        </div>
                    </form>
                </div>

                <div class="col-md-4">
                    <div class="card running-total">
                        <div class="card-body">
                            <h5 class="card-title">Running Total</h5>
                            <div class="text-center">
                                <div class="display-4 mb-2">
                                    <span id="currentTotal"><?php echo $current_total; ?></span>
                                    <span class="text-muted">/</span>
                                    <span id="maxTotal"><?php echo $max_total; ?></span>
                                </div>
                                <div class="progress mb-2" style="height: 10px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo ($max_total > 0 ? ($current_total / $max_total * 100) : 0); ?>%" 
                                         aria-valuenow="<?php echo $current_total; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="<?php echo $max_total; ?>">
                                    </div>
                                </div>
                                <p class="text-muted mb-0">
                                    <?php echo number_format(($max_total > 0 ? ($current_total / $max_total * 100) : 0), 1); ?>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('gradeForm');
            const marksInputs = document.querySelectorAll('.marks-input');
            const currentTotalElement = document.getElementById('currentTotal');
            const maxTotal = <?php echo $max_total; ?>;
            
            function updateTotal() {
                let total = 0;
                marksInputs.forEach(input => {
                    total += Number(input.value);
                });
                currentTotalElement.textContent = total;
                
                // Update progress bar
                const percentage = (total / maxTotal * 100);
                const progressBar = document.querySelector('.progress-bar');
                progressBar.style.width = percentage + '%';
                progressBar.setAttribute('aria-valuenow', total);
                
                // Update percentage text
                const percentageText = document.querySelector('.text-muted');
                percentageText.textContent = percentage.toFixed(1) + '%';
            }
            
            marksInputs.forEach(input => {
                input.addEventListener('input', updateTotal);
            });
            
            // Confirm before leaving page with unsaved changes
            let formChanged = false;
            form.addEventListener('input', () => {
                formChanged = true;
            });
            
            window.addEventListener('beforeunload', (e) => {
                if (formChanged) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
            
            // Remove warning when submitting form
            form.addEventListener('submit', () => {
                formChanged = false;
            });
        });
    </script>
</body>
</html>
