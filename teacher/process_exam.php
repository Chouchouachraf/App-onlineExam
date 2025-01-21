<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit();
}

$host = 'localhost';
$dbname = 'schemase';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // Insert exam
    $stmt = $conn->prepare("
        INSERT INTO exams (
            title, 
            description, 
            duration, 
            start_date, 
            end_date, 
            created_by, 
            class_id, 
            subject_id, 
            passing_score
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['duration'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_SESSION['user_id'],
        $_POST['class_id'] ?? null,
        $_POST['subject_id'] ?? null,
        $_POST['passing_score'] ?? 60
    ]);
    
    $exam_id = $conn->lastInsertId();

    // Process questions
    foreach ($_POST['questions'] as $index => $question) {
        // Handle image upload if present
        $image_path = null;
        if (isset($_FILES['questions']['name'][$index]['image']) && $_FILES['questions']['error'][$index]['image'] === UPLOAD_ERR_OK) {
            $upload_dir = "../uploads/exam_images/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['questions']['name'][$index]['image'], PATHINFO_EXTENSION);
            $image_path = $upload_dir . uniqid() . '.' . $file_extension;
            
            move_uploaded_file($_FILES['questions']['tmp_name'][$index]['image'], $image_path);
        }

        // Insert question
        $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, question_type, image_path, points) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $exam_id,
            $question['text'],
            $question['type'],
            $image_path,
            $question['points']
        ]);
        
        $question_id = $conn->lastInsertId();

        // Handle different question types
        switch ($question['type']) {
            case 'mcq':
                foreach ($question['options'] as $option_index => $option_text) {
                    $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) 
                                          VALUES (?, ?, ?)");
                    $stmt->execute([
                        $question_id,
                        $option_text,
                        $option_index == $question['correct']
                    ]);
                }
                break;

            case 'true_false':
                $stmt = $conn->prepare("INSERT INTO question_options (question_id, option_text, is_correct) VALUES (?, ?, ?), (?, ?, ?)");
                $stmt->execute([
                    $question_id, 'True', $question['correct'] === 'true',
                    $question_id, 'False', $question['correct'] === 'false'
                ]);
                break;
        }
    }

    // Commit transaction
    $conn->commit();
    
    $_SESSION['success_message'] = "Exam created successfully!";
    header("Location: dashboard.php");
    exit();

} catch(Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    $_SESSION['error_message'] = "Error creating exam: " . $e->getMessage();
    header("Location: create_exam.php");
    exit();
}
?>
