<?php
session_start();
require_once '../config/connection.php';

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'etudiant') {
    $_SESSION['login_error'] = "Please login as a student to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

try {
    // Get user details with additional info
    $stmt = $conn->prepare("
        SELECT u.*, 
               COALESCE(c.name, 'Not Assigned') as class_name,
               COALESCE(d.name, 'Not Assigned') as department_name
        FROM users u
        LEFT JOIN classes c ON u.class_id = c.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.id = ? AND u.role = 'etudiant'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all exams (upcoming, ongoing, and past)
    $stmt = $conn->prepare("
        SELECT e.*, 
               s.status as submission_status,
               s.submitted_at
        FROM exams e
        LEFT JOIN exam_submissions s ON e.id = s.exam_id AND s.student_id = ?
        WHERE e.class_id = ? OR e.class_id IS NULL
        ORDER BY e.exam_date DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $user_details['class_id']]);
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Initialize grouped exams array
    $grouped_exams = [
        'Upcoming' => [],
        'Available' => [],
        'Submitted' => [],
        'Expired' => []
    ];

    // Current time for comparison
    $current_time = time();

    // Group exams by status
    foreach ($exams as $exam) {
        $exam_time = strtotime($exam['exam_date']);
        
        if ($exam['submission_status'] === 'submitted') {
            $grouped_exams['Submitted'][] = $exam;
        } elseif ($current_time < $exam_time) {
            $grouped_exams['Upcoming'][] = $exam;
        } elseif ($current_time >= $exam_time && $current_time <= strtotime($exam['end_time'])) {
            $grouped_exams['Available'][] = $exam;
        } else {
            $grouped_exams['Expired'][] = $exam;
        }
    }

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching exam schedule.";
    $grouped_exams = [
        'Upcoming' => [],
        'Available' => [],
        'Submitted' => [],
        'Expired' => []
    ];
}

// Set page title
$page_title = "Exam Schedule";
include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Welcome Section -->
    <div class="card welcome-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">Exam Schedule</h2>
                </div>
                <div class="text-end">
                    <p class="mb-0">
                        <span class="badge bg-primary"><?php echo count($grouped_exams['Upcoming']); ?> Upcoming</span>
                        <span class="badge bg-success"><?php echo count($grouped_exams['Available']); ?> Available</span>
                        <span class="badge bg-info"><?php echo count($grouped_exams['Submitted']); ?> Submitted</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Schedule Sections -->
    <?php foreach ($grouped_exams as $status => $status_exams): ?>
        <?php if (!empty($status_exams)): ?>
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <h4 class="mb-0">
                        <?php
                        $icon_class = [
                            'Upcoming' => 'bx-calendar',
                            'Available' => 'bx-play-circle',
                            'Submitted' => 'bx-check-circle',
                            'Expired' => 'bx-x-circle'
                        ][$status];
                        ?>
                        <i class='bx <?php echo $icon_class; ?>'></i>
                        <?php echo $status; ?> Exams
                    </h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Schedule</th>
                                    <th>Duration</th>
                                    <?php if ($status === 'Submitted'): ?>
                                        <th>Score</th>
                                    <?php endif; ?>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($status_exams as $exam): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($exam['teacher_prenom'] . ' ' . $exam['teacher_nom']); ?>
                                        </td>
                                        <td>
                                            <small>
                                                Start: <?php echo date('M d, Y H:i', strtotime($exam['start_date'])); ?><br>
                                                End: <?php echo date('M d, Y H:i', strtotime($exam['end_date'])); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $exam['duration']; ?> mins</td>
                                        <?php if ($status === 'Submitted'): ?>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo $exam['score']; ?>%
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php if ($status === 'Available'): ?>
                                                <a href="take_exam.php?id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class='bx bx-play'></i> Start
                                                </a>
                                            <?php elseif ($status === 'Upcoming'): ?>
                                                <button class="btn btn-warning btn-sm" disabled>
                                                    <i class='bx bx-time'></i> Wait
                                                </button>
                                            <?php elseif ($status === 'Submitted'): ?>
                                                <a href="view_result.php?exam_id=<?php echo $exam['id']; ?>" 
                                                   class="btn btn-info btn-sm">
                                                    <i class='bx bx-show'></i> View
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary btn-sm" disabled>
                                                    <i class='bx bx-x'></i> Expired
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($exams)): ?>
        <div class="card text-center fade-in">
            <div class="card-body py-5">
                <i class='bx bx-calendar-x text-muted' style="font-size: 4rem;"></i>
                <h3 class="mt-3">No Exams Found</h3>
                <p class="text-muted">There are no exams scheduled for you at the moment.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
