<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add new columns to users table
    $alterQueries = [
        "ALTER TABLE users 
         ADD COLUMN IF NOT EXISTS teacher_id INT UNIQUE,
         ADD COLUMN IF NOT EXISTS student_id INT UNIQUE,
         ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($alterQueries as $query) {
        $conn->exec($query);
    }

    // Update existing users with role-specific IDs
    $stmt = $conn->query("SELECT id, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        if ($user['role'] === 'enseignant') {
            $stmt = $conn->prepare("UPDATE users SET teacher_id = ? WHERE id = ?");
            $stmt->execute([$user['id'], $user['id']]);
        } elseif ($user['role'] === 'etudiant') {
            $stmt = $conn->prepare("UPDATE users SET student_id = ? WHERE id = ?");
            $stmt->execute([$user['id'], $user['id']]);
        }
    }

    // Drop existing exams tables if they exist
    $dropTables = [
        "student_answers",
        "exam_attempts",
        "question_options",
        "questions",
        "exams"
    ];

    foreach ($dropTables as $table) {
        $conn->exec("DROP TABLE IF EXISTS $table");
    }

    // Create exams table with correct foreign key
    $conn->exec("CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        teacher_id INT NOT NULL,
        duration INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        status ENUM('draft', 'published', 'completed') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(teacher_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create questions table
    $conn->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('mcq', 'true_false', 'open') NOT NULL,
        image_path VARCHAR(255),
        points INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create question options table
    $conn->exec("CREATE TABLE IF NOT EXISTS question_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        option_text TEXT NOT NULL,
        is_correct BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create exam attempts table with correct foreign key
    $conn->exec("CREATE TABLE IF NOT EXISTS exam_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        status ENUM('in_progress', 'completed', 'timed_out') DEFAULT 'in_progress',
        score DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(student_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Create student answers table
    $conn->exec("CREATE TABLE IF NOT EXISTS student_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT,
        selected_option_id INT,
        is_correct BOOLEAN,
        points_earned DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
        FOREIGN KEY (selected_option_id) REFERENCES question_options(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    echo "Database structure updated successfully!<br>";
    echo "<a href='../teacher/dashboard.php'>Go to Teacher Dashboard</a>";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
