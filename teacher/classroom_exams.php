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
$classroom_id = isset($_GET['classroom_id']) ? $_GET['classroom_id'] : null;

try {
    // Get classroom details if ID is provided
    if ($classroom_id) {
        $stmt = $conn->prepare("
            SELECT c.*, COUNT(DISTINCT cs.student_id) as student_count
            FROM classrooms c
            LEFT JOIN classroom_students cs ON c.id = cs.classroom_id
            WHERE c.id = ? AND c.teacher_id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$classroom_id, $_SESSION['user_id']]);
        $classroom = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$classroom) {
            header("Location: manage_classrooms.php");
            exit();
        }

        // Get exams assigned to this classroom
        $stmt = $conn->prepare("
            SELECT 
                e.*,
                ec.created_at as assigned_at,
                COUNT(DISTINCT es.id) as submission_count
            FROM exams e
            JOIN exam_classrooms ec ON e.id = ec.exam_id
            LEFT JOIN exam_submissions es ON e.id = es.exam_id
            WHERE ec.classroom_id = ? AND e.created_by = ?
            GROUP BY e.id
            ORDER BY e.start_date DESC
        ");
        $stmt->execute([$classroom_id, $_SESSION['user_id']]);
        $assigned_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get available exams (not assigned to this classroom)
        $stmt = $conn->prepare("
            SELECT e.*
            FROM exams e
            WHERE e.created_by = ?
            AND NOT EXISTS (
                SELECT 1 FROM exam_classrooms ec 
                WHERE ec.exam_id = e.id 
                AND ec.classroom_id = ?
            )
            ORDER BY e.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $classroom_id]);
        $available_exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle exam assignment/removal
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action']) && isset($_POST['exam_id'])) {
            if ($_POST['action'] === 'assign') {
                $stmt = $conn->prepare("INSERT INTO exam_classrooms (exam_id, classroom_id) VALUES (?, ?)");
                $stmt->execute([$_POST['exam_id'], $classroom_id]);
                $success_message = "Exam assigned to classroom successfully!";
            } elseif ($_POST['action'] === 'remove') {
                $stmt = $conn->prepare("DELETE FROM exam_classrooms WHERE exam_id = ? AND classroom_id = ?");
                $stmt->execute([$_POST['exam_id'], $classroom_id]);
                $success_message = "Exam removed from classroom successfully!";
            }
            
            // Redirect to refresh the page
            header("Location: classroom_exams.php?classroom_id=" . $classroom_id);
            exit();
        }
    }

} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Exams - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <?php if ($classroom): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><?php echo htmlspecialchars($classroom['name']); ?></h2>
                        <p class="text-muted mb-0">
                            <?php echo $classroom['student_count']; ?> students enrolled
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignExamModal">
                        <i class='bx bx-plus'></i> Assign New Exam
                    </button>
                </div>

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

                <!-- Assigned Exams -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Assigned Exams</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Subject</th>
                                        <th>Duration</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Submissions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assigned_exams)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No exams assigned to this classroom yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assigned_exams as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td><?php echo htmlspecialchars($exam['subject']); ?></td>
                                                <td><?php echo $exam['duration']; ?> mins</td>
                                                <td><?php echo date('M d, Y H:i', strtotime($exam['start_date'])); ?></td>
                                                <td><?php echo date('M d, Y H:i', strtotime($exam['end_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo $exam['submission_count']; ?> submissions
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_submissions.php?exam_id=<?php echo $exam['id']; ?>&classroom_id=<?php echo $classroom_id; ?>" 
                                                           class="btn btn-info" title="View Submissions">
                                                            <i class='bx bx-spreadsheet'></i>
                                                        </a>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="remove">
                                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                                            <button type="submit" class="btn btn-danger" 
                                                                    onclick="return confirm('Are you sure you want to remove this exam from the classroom?')"
                                                                    title="Remove from Classroom">
                                                                <i class='bx bx-trash'></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Assign Exam Modal -->
                <div class="modal fade" id="assignExamModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Assign Exam to <?php echo htmlspecialchars($classroom['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (empty($available_exams)): ?>
                                    <p class="text-center">No available exams to assign. Create a new exam first.</p>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="assign">
                                        <div class="mb-3">
                                            <label class="form-label">Select Exam</label>
                                            <select class="form-select" name="exam_id" required>
                                                <option value="">Choose an exam...</option>
                                                <?php foreach ($available_exams as $exam): ?>
                                                    <option value="<?php echo $exam['id']; ?>">
                                                        <?php echo htmlspecialchars($exam['title'] . ' (' . $exam['subject'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Assign Exam</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">
                    Please select a classroom to view its exams.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
