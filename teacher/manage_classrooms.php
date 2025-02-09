<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    $_SESSION['login_error'] = "Please login as a teacher to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle classroom creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_classroom') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);

        try {
            $stmt = $conn->prepare("INSERT INTO classrooms (name, description, teacher_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $teacher_id]);
            $success_message = "Classroom created successfully!";
        } catch (PDOException $e) {
            $error_message = "Error creating classroom: " . $e->getMessage();
        }
    }
    // Handle student addition to classroom
    elseif ($_POST['action'] === 'add_student') {
        $classroom_id = $_POST['classroom_id'];
        $student_id = $_POST['student_id'];

        try {
            $stmt = $conn->prepare("INSERT INTO classroom_students (classroom_id, student_id) VALUES (?, ?)");
            $stmt->execute([$classroom_id, $student_id]);
            $success_message = "Student added to classroom successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry error
                $error_message = "This student is already in the classroom.";
            } else {
                $error_message = "Error adding student: " . $e->getMessage();
            }
        }
    }
    // Handle student removal from classroom
    elseif ($_POST['action'] === 'remove_student') {
        $classroom_id = $_POST['classroom_id'];
        $student_id = $_POST['student_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM classroom_students WHERE classroom_id = ? AND student_id = ?");
            $stmt->execute([$classroom_id, $student_id]);
            $success_message = "Student removed from classroom successfully!";
        } catch (PDOException $e) {
            $error_message = "Error removing student: " . $e->getMessage();
        }
    }
}

// Get all classrooms for this teacher
try {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT cs.student_id) as student_count
        FROM classrooms c
        LEFT JOIN classroom_students cs ON c.id = cs.classroom_id
        WHERE c.teacher_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$teacher_id]);
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all available students
    $stmt = $conn->prepare("SELECT id, nom, prenom, email FROM users WHERE role = 'etudiant' ORDER BY nom, prenom");
    $stmt->execute();
    $all_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - ExamMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Manage Classrooms</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createClassroomModal">
                    <i class='bx bx-plus'></i> Create New Classroom
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

            <!-- Classrooms Grid -->
            <div class="row">
                <?php foreach ($classrooms as $classroom): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($classroom['name']); ?></h5>
                                <div class="dropdown">
                                    <button class="btn btn-link text-dark" type="button" data-bs-toggle="dropdown">
                                        <i class='bx bx-dots-vertical-rounded'></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                               data-bs-target="#addStudentModal" 
                                               data-classroom-id="<?php echo $classroom['id']; ?>"
                                               data-classroom-name="<?php echo htmlspecialchars($classroom['name']); ?>">
                                                <i class='bx bx-user-plus'></i> Add Student
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#" data-bs-toggle="modal" 
                                               data-bs-target="#viewStudentsModal" 
                                               data-classroom-id="<?php echo $classroom['id']; ?>"
                                               data-classroom-name="<?php echo htmlspecialchars($classroom['name']); ?>">
                                                <i class='bx bx-group'></i> View Students
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" 
                                               data-bs-target="#deleteClassroomModal"
                                               data-classroom-id="<?php echo $classroom['id']; ?>"
                                               data-classroom-name="<?php echo htmlspecialchars($classroom['name']); ?>">
                                                <i class='bx bx-trash'></i> Delete Classroom
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($classroom['description'])); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-muted">
                                        <i class='bx bx-group'></i> <?php echo $classroom['student_count']; ?> students
                                    </span>
                                    <small class="text-muted">
                                        Created <?php echo date('M d, Y', strtotime($classroom['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Create Classroom Modal -->
    <div class="modal fade" id="createClassroomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Classroom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_classroom">
                        <div class="mb-3">
                            <label class="form-label">Classroom Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Classroom</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Student to <span class="classroom-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_student">
                        <input type="hidden" name="classroom_id" id="add_classroom_id">
                        <div class="mb-3">
                            <label class="form-label">Select Student</label>
                            <select class="form-select" name="student_id" required>
                                <option value="">Choose a student...</option>
                                <?php foreach ($all_students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom'] . ' (' . $student['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Students Modal -->
    <div class="modal fade" id="viewStudentsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Students in <span class="classroom-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Add Student Modal
            const addStudentModal = document.getElementById('addStudentModal');
            addStudentModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const classroomId = button.getAttribute('data-classroom-id');
                const classroomName = button.getAttribute('data-classroom-name');
                
                this.querySelector('#add_classroom_id').value = classroomId;
                this.querySelector('.classroom-name').textContent = classroomName;
            });

            // Handle View Students Modal
            const viewStudentsModal = document.getElementById('viewStudentsModal');
            viewStudentsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const classroomId = button.getAttribute('data-classroom-id');
                const classroomName = button.getAttribute('data-classroom-name');
                
                this.querySelector('.classroom-name').textContent = classroomName;
                
                // Fetch students for this classroom
                fetch(`get_classroom_students.php?classroom_id=${classroomId}`)
                    .then(response => response.json())
                    .then(data => {
                        const tbody = this.querySelector('tbody');
                        tbody.innerHTML = '';
                        
                        data.forEach(student => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${student.nom} ${student.prenom}</td>
                                <td>${student.email}</td>
                                <td>${new Date(student.joined_at).toLocaleDateString()}</td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_student">
                                        <input type="hidden" name="classroom_id" value="${classroomId}">
                                        <input type="hidden" name="student_id" value="${student.id}">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class='bx bx-user-x'></i> Remove
                                        </button>
                                    </form>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    });
            });
        });
    </script>
</body>
</html>
