<?php



$lockFile = __DIR__ . '/../storage/.installed';
if (file_exists($lockFile)) {
    die('<h1>Already installed!</h1><p>Delete <code>storage/.installed</code> to re-run installation.</p>
         <p><a href="login.php">Go to Login</a></p>');
}

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/db.php';

$messages = [];
$errors = [];

try {
    $pdo = db();

    
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tablesExist = $stmt->rowCount() > 0;

    if (!$tablesExist) {
        
        $schemaFile = PROJECT_ROOT . '/sql/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE\s+`?\w+`?\s*;/i', '', $sql);
            $pdo->exec($sql);
            $messages[] = 'Database schema created successfully.';
        } else {
            $errors[] = 'Schema file not found at: ' . $schemaFile;
        }
    } else {
        $messages[] = 'Tables already exist, skipping schema creation.';
    }

    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
    $userCount = $stmt->fetch()['cnt'];

    if ($userCount == 0) {
        
        $teacherHash = password_hash('teacher123', PASSWORD_DEFAULT);
        $studentHash = password_hash('student123', PASSWORD_DEFAULT);

        
        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password_hash, role, full_name) VALUES
             ('teacher1', 'teacher1@school.local', :th, 'teacher', 'Prof. Ivanov'),
             ('student1', 'student1@school.local', :s1, 'student', 'Maria Petrova'),
             ('student2', 'student2@school.local', :s2, 'student', 'Georgi Dimitrov')"
        );
        $stmt->execute([':th' => $teacherHash, ':s1' => $studentHash, ':s2' => $studentHash]);
        $messages[] = 'Users created: teacher1/teacher123, student1/student123, student2/student123';

        
        $pdo->exec("INSERT INTO exams (teacher_id, title, description, time_limit_min, passing_score, access_code, is_published)
            VALUES (1, 'Web Technologies Basics', 'A quiz covering HTML, CSS, JavaScript and HTTP fundamentals.', 20, 60.00, 'WEB2024', 1)");

        $pdo->exec("INSERT INTO questions (exam_id, type, question_text, options_json, correct_answer, points, sort_order) VALUES
            (1, 'mcq', 'Which HTML tag is used to define an unordered list?', '[\"<ol>\",\"<ul>\",\"<li>\",\"<dl>\"]', '<ul>', 2.00, 1),
            (1, 'mcq', 'What does CSS stand for?', '[\"Computer Style Sheets\",\"Creative Style Sheets\",\"Cascading Style Sheets\",\"Colorful Style Sheets\"]', 'Cascading Style Sheets', 2.00, 2),
            (1, 'tf',  'JavaScript is a compiled language.', NULL, 'false', 2.00, 3),
            (1, 'short', 'What HTTP status code indicates \"Not Found\"?', NULL, '404', 2.00, 4),
            (1, 'mcq', 'Which property is used to change the background color in CSS?', '[\"color\",\"bgcolor\",\"background-color\",\"background\"]', 'background-color', 2.00, 5)");

        
        $pdo->exec("INSERT INTO attempts (student_id, exam_id, started_at, finished_at, score, max_score, is_submitted) VALUES
            (2, 1, '2025-12-01 09:00:00', '2025-12-01 09:15:00', 6.00, 10.00, 1),
            (2, 1, '2025-12-05 10:00:00', '2025-12-05 10:12:00', 8.00, 10.00, 1),
            (3, 1, '2025-12-02 14:00:00', '2025-12-02 14:18:00', 4.00, 10.00, 1)");

        $pdo->exec("INSERT INTO attempt_answers (attempt_id, question_id, student_answer, is_correct, points_awarded) VALUES
            (1, 1, '<ul>',  1, 2.00), (1, 2, 'Cascading Style Sheets', 1, 2.00),
            (1, 3, 'true',  0, 0.00), (1, 4, '404',   1, 2.00), (1, 5, 'color', 0, 0.00),
            (2, 1, '<ul>',  1, 2.00), (2, 2, 'Cascading Style Sheets', 1, 2.00),
            (2, 3, 'false', 1, 2.00), (2, 4, '404',   1, 2.00), (2, 5, 'color', 0, 0.00),
            (3, 1, '<ol>',  0, 0.00), (3, 2, 'Cascading Style Sheets', 1, 2.00),
            (3, 3, 'true',  0, 0.00), (3, 4, '200',   0, 0.00), (3, 5, 'background-color', 1, 2.00)");

        
        $pdo->exec("INSERT INTO games (teacher_id, title, description, access_code, is_published)
            VALUES (1, 'The Lost Server Room', 'Find the server room key and restore the network before time runs out!', 'ESCAPE1', 1)");

        $pdo->exec("INSERT INTO game_nodes (game_id, node_key, title, description, is_end_node, sort_order) VALUES
            (1, 'start',    'Reception Desk',    'You arrive at the IT building reception. The lights are flickering. There is a desk with a computer and a locked drawer.', 0, 1),
            (1, 'computer', 'Check the Computer', 'The screen shows a password hint: \"The year the company was founded + 42\". A sticky note on the monitor says \"Founded: 1980\".', 0, 2),
            (1, 'drawer',   'Open the Drawer',    'Inside the drawer you find a keycard and a network diagram. The diagram shows the server room is on floor B2.', 0, 3),
            (1, 'server',   'Server Room',         'You use the keycard to enter the server room. You restart the main switch and the network comes back online. Congratulations!', 1, 4)");

        $pdo->exec("UPDATE games SET start_node_id = 1 WHERE id = 1");

        $pdo->exec("INSERT INTO game_choices (node_id, choice_text, target_node_id, sort_order) VALUES
            (1, 'Look at the computer screen', 2, 1),
            (1, 'Try to open the drawer', 3, 2),
            (2, 'Enter password 2022 (1980+42)', 3, 1),
            (2, 'Go back to the desk', 1, 2),
            (3, 'Take the keycard and go to B2', 4, 1),
            (3, 'Go back to the desk', 1, 2)");

        $messages[] = 'Demo exam with 5 questions created.';
        $messages[] = 'Demo escape room "The Lost Server Room" with 4 nodes created.';
        $messages[] = 'Demo attempts for students created.';

        
        $apiToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $apiToken);
        $pdo->prepare("INSERT INTO api_tokens (user_id, token_hash, label) VALUES (1, :hash, 'Demo API Token')")
            ->execute([':hash' => $tokenHash]);
        $messages[] = 'API token created for teacher1: <code>' . htmlspecialchars($apiToken) . '</code>';
        $messages[] = '<strong>Save this token!</strong> It will not be shown again.';
    } else {
        $messages[] = 'Seed data already exists, skipping.';
    }

    
    if (!is_dir(SEB_CONFIGS_PATH)) {
        mkdir(SEB_CONFIGS_PATH, 0755, true);
        $messages[] = 'Created storage/seb_configs/ directory.';
    }
    if (!is_dir(GAME_EXPORTS_PATH)) {
        mkdir(GAME_EXPORTS_PATH, 0755, true);
        $messages[] = 'Created storage/game_exports/ directory.';
    }

    
    file_put_contents($lockFile, date('c'));
    $messages[] = 'Installation complete!';

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $errors[] = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Exam System - Installation</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 700px; margin: 2rem auto; padding: 0 1rem; color: 
        h1 { color: 
        .msg { padding: 0.5rem 1rem; margin: 0.5rem 0; border-radius: 6px; }
        .msg-ok { background: 
        .msg-err { background: 
        code { background: 
        a.btn { display: inline-block; padding: 0.75rem 1.5rem; background: 
        a.btn:hover { background: 
    </style>
</head>
<body>
    <h1>SEB Exam System - Installation</h1>

    <?php foreach ($messages as $msg): ?>
        <div class="msg msg-ok"><?= $msg ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="msg msg-err"><?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>

    <?php if (empty($errors)): ?>
        <a href="login.php" class="btn">Go to Login</a>
        <p style="margin-top:1rem;color:#64748b;font-size:0.9rem;">
            <strong>Security:</strong> Delete this file (<code>public/install.php</code>) after installation!
        </p>
    <?php endif; ?>
</body>
</html>







