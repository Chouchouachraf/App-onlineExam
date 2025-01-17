<?php
$host = 'localhost';
$dbname = 'exammaster';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create exams table
    $sql = "CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        created_by INT NOT NULL,
        status ENUM('draft', 'published') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "Exams table created successfully\n";

    // Create questions table
    $sql = "CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('mcq', 'true_false', 'open') NOT NULL,
        points INT NOT NULL DEFAULT 1,
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Questions table created successfully\n";

    // Create options table for MCQ questions
    $sql = "CREATE TABLE IF NOT EXISTS question_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        option_text TEXT NOT NULL,
        is_correct BOOLEAN NOT NULL DEFAULT FALSE,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Question options table created successfully\n";

    // Create exam attempts table
    $sql = "CREATE TABLE IF NOT EXISTS exam_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        student_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        status ENUM('in_progress', 'completed', 'timed_out') NOT NULL,
        score DECIMAL(5,2),
        points_earned INT,
        total_points INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id),
        FOREIGN KEY (student_id) REFERENCES users(id)
    )";
    $conn->exec($sql);
    echo "Exam attempts table created successfully\n";

    // Create student answers table
    $sql = "CREATE TABLE IF NOT EXISTS student_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        is_correct BOOLEAN,
        points_earned INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id)
    )";
    $conn->exec($sql);
    echo "Student answers table created successfully\n";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
