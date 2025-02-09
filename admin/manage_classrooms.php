<?php
session_start();
require_once '../config/connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['login_error'] = "Please login as an administrator to access this page.";
    header("Location: ../auth/login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle classroom actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_classroom') {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $teacher_id = $_POST['teacher_id'];

        try {
            $stmt = $conn->prepare("INSERT INTO classrooms (name, description, teacher_id) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $teacher_id]);
            $success_message = "Classroom created successfully!";
        } catch (PDOException $e) {
            $error_message = "Error creating classroom: " . $e->getMessage();
        }
    }
    elseif ($_POST['action'] === 'delete_classroom') {
        $classroom_id = $_POST['classroom_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM classrooms WHERE id = ?");
            $stmt->execute([$classroom_id]);
            $success_message = "Classroom deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting classroom: " . $e->getMessage();
        }
    }
    elseif ($_POST['action'] === 'update_classroom') {
        $classroom_id = $_POST['classroom_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $teacher_id = $_POST['teacher_id'];

        try {
            $stmt = $conn->prepare("UPDATE classrooms SET name = ?, description = ?, teacher_id = ? WHERE id = ?");
            $stmt->execute([$name, $description, $teacher_id, $classroom_id]);
            $success_message = "Classroom updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating classroom: " . $e->getMessage();
        }
    }
}

// Get all classrooms with teacher information
try {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            CONCAT(u.prenom, ' ', u.nom) as teacher_name,
            COUNT(DISTINCT cs.student_id) as student_count
        FROM classrooms c
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN classroom_students cs ON c.id = cs.classroom_id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all teachers for the dropdown
    $stmt = $conn->prepare("SELECT id, nom, prenom, email FROM users WHERE role = 'enseignant' ORDER BY nom, prenom");
    $stmt->execute();
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - Admin Dashboard</title>
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

            <!-- Classrooms Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Teacher</th>
                                    <th>Students</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($classrooms as $classroom): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($classroom['name']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['description']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['teacher_name']); ?></td>
                                        <td><?php echo $classroom['student_count']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($classroom['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                    data-bs-target="#editClassroomModal"
                                                    data-classroom-id="<?php echo $classroom['id']; ?>"
                                                    data-classroom-name="<?php echo htmlspecialchars($classroom['name']); ?>"
                                                    data-classroom-description="<?php echo htmlspecialchars($classroom['description']); ?>"
                                                    data-teacher-id="<?php echo $classroom['teacher_id']; ?>">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal"
                                                    data-bs-target="#viewStudentsModal"
                                                    data-classroom-id="<?php echo $classroom['id']; ?>"
                                                    data-classroom-name="<?php echo htmlspecialchars($classroom['name']); ?>">
                                                <i class='bx bx-group'></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteClassroom(<?php echo $classroom['id']; ?>)">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                        <div class="mb-3">
                            <label class="form-label">Assign Teacher</label>
                            <select class="form-select" name="teacher_id" required>
                                <option value="">Choose a teacher...</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['nom'] . ' ' . $teacher['prenom'] . ' (' . $teacher['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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

    <!-- Edit Classroom Modal -->
    <div class="modal fade" id="editClassroomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Classroom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_classroom">
                        <input type="hidden" name="classroom_id" id="edit_classroom_id">
                        <div class="mb-3">
                            <label class="form-label">Classroom Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assign Teacher</label>
                            <select class="form-select" name="teacher_id" id="edit_teacher_id" required>
                                <option value="">Choose a teacher...</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['nom'] . ' ' . $teacher['prenom'] . ' (' . $teacher['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Classroom</button>
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
        // Handle Edit Classroom Modal
        const editClassroomModal = document.getElementById('editClassroomModal');
        editClassroomModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const classroomId = button.getAttribute('data-classroom-id');
            const classroomName = button.getAttribute('data-classroom-name');
            const classroomDescription = button.getAttribute('data-classroom-description');
            const teacherId = button.getAttribute('data-teacher-id');
            
            this.querySelector('#edit_classroom_id').value = classroomId;
            this.querySelector('#edit_name').value = classroomName;
            this.querySelector('#edit_description').value = classroomDescription;
            this.querySelector('#edit_teacher_id').value = teacherId;
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
                        `;
                        tbody.appendChild(row);
                    });
                });
        });

        // Handle Classroom Deletion
        function deleteClassroom(classroomId) {
            if (confirm('Are you sure you want to delete this classroom? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_classroom">
                    <input type="hidden" name="classroom_id" value="${classroomId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
