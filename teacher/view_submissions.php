<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
$classroom_id = isset($_GET['classroom_id']) ? $_GET['classroom_id'] : null;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name';

if (!$exam_id) {
    header("Location: dashboard.php");
    exit();
}

try {
    // Get exam details
    $stmt = $conn->prepare("
        SELECT e.*, 
               COUNT(DISTINCT es.id) as submission_count,
               c.name as classroom_name
        FROM exams e
        LEFT JOIN exam_submissions es ON e.id = es.exam_id
        LEFT JOIN classrooms c ON c.id = ?
        WHERE e.id = ? AND e.created_by = ?
        GROUP BY e.id
    ");
    $stmt->execute([$classroom_id, $exam_id, $_SESSION['user_id']]);
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        header("Location: dashboard.php");
        exit();
    }

    // Build the query for submissions
    $query = "
        SELECT 
            es.*,
            u.nom,
            u.prenom,
            c.name as classroom_name,
            es.total_score,
            COUNT(sa.id) as answered_questions,
            (SELECT COUNT(*) FROM exam_questions WHERE exam_id = ?) as total_questions
        FROM exam_submissions es
        JOIN users u ON es.student_id = u.id
        LEFT JOIN classrooms c ON u.classroom_id = c.id
        LEFT JOIN student_answers sa ON es.id = sa.submission_id
        WHERE es.exam_id = ?
    ";

    $params = [$exam_id, $exam_id];

    if ($classroom_id) {
        $query .= " AND u.classroom_id = ?";
        $params[] = $classroom_id;
    }

    $query .= " GROUP BY es.id";

    // Add sorting
    switch ($sort_by) {
        case 'score_asc':
            $query .= " ORDER BY es.total_score ASC";
            break;
        case 'score_desc':
            $query .= " ORDER BY es.total_score DESC";
            break;
        case 'classroom':
            $query .= " ORDER BY c.name ASC, u.nom ASC";
            break;
        default:
            $query .= " ORDER BY u.nom ASC, u.prenom ASC";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all classrooms for filter
    $stmt = $conn->prepare("
        SELECT DISTINCT c.* 
        FROM classrooms c
        JOIN users u ON c.id = u.classroom_id
        JOIN exam_submissions es ON u.id = es.student_id
        WHERE es.exam_id = ?
    ");
    $stmt->execute([$exam_id]);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <h2><?php echo htmlspecialchars($exam['title']); ?> - Submissions</h2>
                    <p class="text-muted">
                        Total Submissions: <?php echo count($submissions); ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <form class="row g-3" method="GET">
                        <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam_id); ?>">
                        
                        <div class="col-md-4">
                            <label class="form-label">Filter by Classroom</label>
                            <select name="classroom_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Classrooms</option>
                                <?php foreach ($classrooms as $classroom): ?>
                                    <option value="<?php echo $classroom['id']; ?>" 
                                            <?php echo ($classroom_id == $classroom['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classroom['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Sort by</label>
                            <select name="sort_by" class="form-select" onchange="this.form.submit()">
                                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Student Name</option>
                                <option value="classroom" <?php echo $sort_by === 'classroom' ? 'selected' : ''; ?>>Classroom</option>
                                <option value="score_asc" <?php echo $sort_by === 'score_asc' ? 'selected' : ''; ?>>Score (Low to High)</option>
                                <option value="score_desc" <?php echo $sort_by === 'score_desc' ? 'selected' : ''; ?>>Score (High to Low)</option>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5">
                            <i class='bx bx-folder-open' style="font-size: 4rem; color: #ccc;"></i>
                            <p class="mt-3">No submissions found for this exam.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Classroom</th>
                                        <th>Score</th>
                                        <th>Questions Answered</th>
                                        <th>Submission Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($submission['nom'] . ' ' . $submission['prenom']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($submission['classroom_name']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($submission['total_score'] >= 50) ? 'success' : 'danger'; ?>">
                                                    <?php echo $submission['total_score']; ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo $submission['answered_questions']; ?> / <?php echo $submission['total_questions']; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y H:i', strtotime($submission['submission_time'])); ?>
                                            </td>
                                            <td>
                                                <a href="review_submission.php?submission_id=<?php echo $submission['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class='bx bx-show'></i> Review
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
