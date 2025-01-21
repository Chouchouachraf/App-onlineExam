<?php
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Create connection without database
    $conn = new PDO("mysql:host=$host", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS schemase");
    echo "Database 'schemase' created or already exists<br>";
    
    // Select the database
    $conn->exec("USE schemase");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        prenom VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'enseignant', 'etudiant') NOT NULL DEFAULT 'etudiant',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'users' created successfully<br>";

    // Create exams table
    $sql = "CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration INT NOT NULL,
        start_time DATETIME,
        end_time DATETIME,
        created_by INT,
        status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'exams' created successfully<br>";

    // Create questions table
    $sql = "CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL,
        points INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'questions' created successfully<br>";

    // Create answers table
    $sql = "CREATE TABLE IF NOT EXISTS answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        answer_text TEXT NOT NULL,
        is_correct BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'answers' created successfully<br>";

    // Create exam_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS exam_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        user_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME,
        score DECIMAL(5,2),
        status ENUM('in_progress', 'completed', 'timed_out') DEFAULT 'in_progress',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'exam_submissions' created successfully<br>";

    // Create user_answers table
    $sql = "CREATE TABLE IF NOT EXISTS user_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        submission_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_text TEXT,
        is_correct BOOLEAN DEFAULT FALSE,
        points_earned DECIMAL(5,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (submission_id) REFERENCES exam_submissions(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questions(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Table 'user_answers' created successfully<br>";

    // Create a default admin user
    $adminEmail = "admin@exammaster.com";
    $checkAdmin = $conn->query("SELECT COUNT(*) FROM users WHERE email = '$adminEmail'")->fetchColumn();
    
    if ($checkAdmin == 0) {
        $adminPassword = password_hash("admin123", PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (nom, prenom, email, password, role) 
                VALUES ('Admin', 'System', :email, :password, 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':email' => $adminEmail,
            ':password' => $adminPassword
        ]);
        echo "<br>Default admin user created<br>";
        echo "Email: admin@exammaster.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "<br>Admin user already exists<br>";
    }
    
    echo "<br>Database setup completed successfully!<br>";
    echo "<h3>Database Structure:</h3>";
    echo "<ul>";
    echo "<li>users: Stores user information (admin, teachers, students)</li>";
    echo "<li>exams: Stores exam information</li>";
    echo "<li>questions: Stores exam questions</li>";
    echo "<li>answers: Stores possible answers for questions</li>";
    echo "<li>exam_submissions: Tracks student exam attempts</li>";
    echo "<li>user_answers: Stores student answers for each question</li>";
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<br>Error: " . $e->getMessage();
}
?>
