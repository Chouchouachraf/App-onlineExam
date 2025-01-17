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

    // Create exams table
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
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "Exams table created successfully<br>";

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
    echo "Questions table created successfully<br>";

    // Create question options table
    $conn->exec("CREATE TABLE IF NOT EXISTS question_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        option_text TEXT NOT NULL,
        is_correct BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "Question options table created successfully<br>";

    // Create exam attempts table
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
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "Exam attempts table created successfully<br>";

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
    echo "Student answers table created successfully<br>";

    // Create uploads directory if it doesn't exist
    $upload_dir = "../uploads/exam_images/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Created uploads directory<br>";
    }

    echo "<br>Database setup completed successfully!";
    echo "<br><a href='../teacher/dashboard.php'>Go to Teacher Dashboard</a>";

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
