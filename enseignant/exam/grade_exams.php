<?php
// grade-exams.php
session_start();

// Dans un vrai système, ces données viendraient d'une base de données
$pending_exams = [
    [
        'id' => 1,
        'student_name' => 'Jean Dupont',
        'exam_name' => 'Français',
        'submission_date' => '2024-01-02',
        'status' => 'En attente'
    ],
    [
        'id' => 2,
        'student_name' => 'Marie Martin',
        'exam_name' => 'Anglais technique',
        'submission_date' => '2024-01-03',
        'status' => 'En attente'
    ]
];

$exam_id = isset($_GET['exam_id']) ? $_GET['exam_id'] : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExamMaster - Correction des examens</title>
    <style>
        .exam-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .exam-item {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .exam-item:hover {
            background: #f8f9fa;
        }

        .grade-btn {
            background: #4a90e2;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
        }

        .grade-btn:hover {
            background: #357abd;
        }

        .grading-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            margin: 20px auto;
        }

        .question-block {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .response-text {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .grade-input {
            width: 80px;
            padding: 5px;
            margin-right: 10px;
        }

        .comment-input {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }

        .submit-grades {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-grades:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <?php if (!$exam_id): ?>
    <!-- Liste des examens à corriger -->
    <div class="exam-list">
        <h2>Examens à corriger</h2>
        <div class="exam-item" style="font-weight: bold;">
            <div>Étudiant</div>
            <div>Examen</div>
            <div>Date</div>
            <div>Statut</div>
            <div>Action</div>
        </div>
        <?php foreach ($pending_exams as $exam): ?>
        <div class="exam-item">
            <div><?php echo htmlspecialchars($exam['student_name']); ?></div>
            <div><?php echo htmlspecialchars($exam['exam_name']); ?></div>
            <div><?php echo htmlspecialchars($exam['submission_date']); ?></div>
            <div><?php echo htmlspecialchars($exam['status']); ?></div>
            <div>
                <a href="?exam_id=<?php echo $exam['id']; ?>" class="grade-btn">Corriger</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Interface de correction -->
    <div class="grading-form">
        <h2>Correction de l'examen</h2>
        <form action="submit-grades.php" method="POST">
            <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
            
            <div class="question-block">
                <h3>Question 1</h3>
                <p>Expliquez le concept de la mondialisation.</p>
                <div class="response-text">
                    La réponse de l'étudiant apparaît ici...
                </div>
                <div>
                    <label>
                        Note (/20):
                        <input type="number" name="grades[1]" class="grade-input" min="0" max="20" required>
                    </label>
                </div>
                <div>
                    <label>
                        Commentaire:
                        <textarea name="comments[1]" class="comment-input" rows="3"></textarea>
                    </label>
                </div>
            </div>

            <!-- Répéter pour chaque question -->
            
            <button type="submit" class="submit-grades">Soumettre la correction</button>
        </form>
    </div>
    <?php endif; ?>
</body>
</html>