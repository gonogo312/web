<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Attempt.php';
require_once __DIR__ . '/../../app/models/Question.php';
init_session();
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/student/');
    exit;
}

csrf_check();

$attemptId = validate_int($_POST['attempt_id'] ?? 0, 1);
if (!$attemptId) { die('Invalid attempt.'); }

$attempt = Attempt::findById($attemptId);
if (!$attempt || $attempt['student_id'] !== current_user_id() || $attempt['is_submitted']) {
    die('Invalid or already submitted attempt.');
}

$exam = Exam::findById($attempt['exam_id']);
$questions = Exam::getQuestions($attempt['exam_id']);
$answers = $_POST['answers'] ?? [];

$missingAnswers = [];
foreach ($questions as $q) {
    $studentAnswer = trim($answers[$q['id']] ?? '');
    if ($studentAnswer === '') {
        $missingAnswers[] = $q['id'];
    }
}
if (!empty($missingAnswers)) {
    $_SESSION['flash_error'] = 'Please answer all questions before submitting.';
    header('Location: exam_take.php?exam_id=' . $attempt['exam_id']);
    exit;
}

$totalScore = 0;

foreach ($questions as $q) {
    $studentAnswer = trim($answers[$q['id']] ?? '');
    $isCorrect = false;

    
    if ($q['type'] === 'mcq' || $q['type'] === 'tf') {
        $isCorrect = (mb_strtolower($studentAnswer) === mb_strtolower($q['correct_answer']));
    } elseif ($q['type'] === 'short') {
        
        $isCorrect = (mb_strtolower($studentAnswer) === mb_strtolower(trim($q['correct_answer'])));
    }

    $pointsAwarded = $isCorrect ? (float)$q['points'] : 0;
    $totalScore += $pointsAwarded;

    Attempt::saveAnswer($attemptId, $q['id'], $studentAnswer, $isCorrect, $pointsAwarded);
}


Attempt::submit($attemptId, $totalScore);

$_SESSION['flash_success'] = 'Exam submitted successfully!';
header('Location: exam_result.php?attempt_id=' . $attemptId);
exit;





