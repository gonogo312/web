<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Attempt.php';
init_session();
require_role('student');

$examId = validate_int($_GET['exam_id'] ?? 0, 1);
if (!$examId) { http_response_code(404); die('Exam not found.'); }

$exam = Exam::findById($examId);
if (!$exam || !$exam['is_published']) {
    http_response_code(404); die('Exam not found or not published.');
}

$sid = current_user_id();
$error = '';


if ($exam['access_code'] && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['exam_access_' . $examId])) {
    
    $pageTitle = 'Enter Access Code';
    ob_start();
    ?>
    <div class="card" style="max-width:500px;margin:2rem auto">
        <h2>Access Code Required</h2>
        <p>This exam requires an access code to start.</p>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check_code">
            <div class="form-group">
                <label for="access_code">Access Code</label>
                <input type="text" id="access_code" name="access_code" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <?php
    $bodyContent = ob_get_clean();
    include __DIR__ . '/../../app/views/layout.php';
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_code') {
    csrf_check();
    $code = trim($_POST['access_code'] ?? '');
    if ($code === $exam['access_code']) {
        $_SESSION['exam_access_' . $examId] = true;
        header('Location: exam_take.php?exam_id=' . $examId);
        exit;
    } else {
        $error = 'Invalid access code.';
        $pageTitle = 'Enter Access Code';
        ob_start();
        ?>
        <div class="card" style="max-width:500px;margin:2rem auto">
            <h2>Access Code Required</h2>
            <div class="alert alert-error"><?= e($error) ?></div>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="check_code">
                <div class="form-group">
                    <label for="access_code">Access Code</label>
                    <input type="text" id="access_code" name="access_code" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
        <?php
        $bodyContent = ob_get_clean();
        include __DIR__ . '/../../app/views/layout.php';
        exit;
    }
}


$attempt = Attempt::getActiveAttempt($sid, $examId);
$questions = Exam::getQuestions($examId);
$stats = Exam::getQuestionStats($examId);

if (!$attempt) {
    
    $attemptId = Attempt::start($sid, $examId, (float)$stats['total_points']);
    $attempt = Attempt::findById($attemptId);
}


$startedAt = strtotime($attempt['started_at']);
$timeLimitSec = $exam['time_limit_min'] * 60;
$elapsed = time() - $startedAt;
$remaining = max(0, $timeLimitSec - $elapsed);


if ($remaining <= 0 && !$attempt['is_submitted']) {
    Attempt::submit($attempt['id'], 0);
    $_SESSION['flash_warning'] = 'Time expired! Your exam was auto-submitted.';
    header('Location: exam_result.php?attempt_id=' . $attempt['id']);
    exit;
}

$pageTitle = 'Taking: ' . $exam['title'];
ob_start();
?>
<div class="page-title">
    <h1><?= e($exam['title']) ?></h1>
    <div>
        <strong>Time Remaining: </strong>
        <span id="exam-timer" data-remaining="<?= (int)$remaining ?>" style="font-size:1.2rem; font-weight:700;">
            <?= floor($remaining / 60) ?>:<?= str_pad($remaining % 60, 2, '0', STR_PAD_LEFT) ?>
        </span>
    </div>
</div>

<?php if ($exam['description']): ?>
    <p class="text-muted mb-2"><?= e($exam['description']) ?></p>
<?php endif; ?>

<form method="POST" action="exam_submit.php" id="exam-form" class="card">
    <?= csrf_field() ?>
    <input type="hidden" name="attempt_id" value="<?= (int)$attempt['id'] ?>">

    <?php foreach ($questions as $i => $q): ?>
        <div class="question-block" style="margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid var(--color-border);">
            <h3>Question <?= $i + 1 ?> <small class="text-muted">(<?= e($q['points']) ?> pts)</small></h3>
            <p style="margin:0.5rem 0"><?= e($q['question_text']) ?></p>

            <?php if ($q['type'] === 'mcq'): ?>
                <?php $options = json_decode($q['options_json'], true) ?? []; ?>
                <?php foreach ($options as $j => $opt): ?>
                    <div class="form-check">
                        <input type="radio" name="answers[<?= (int)$q['id'] ?>]"
                               value="<?= e($opt) ?>" id="q<?= $q['id'] ?>_<?= $j ?>">
                        <label for="q<?= $q['id'] ?>_<?= $j ?>"><?= e($opt) ?></label>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($q['type'] === 'tf'): ?>
                <div class="form-check">
                    <input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="true" id="q<?= $q['id'] ?>_t">
                    <label for="q<?= $q['id'] ?>_t">True</label>
                </div>
                <div class="form-check">
                    <input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="false" id="q<?= $q['id'] ?>_f">
                    <label for="q<?= $q['id'] ?>_f">False</label>
                </div>

            <?php elseif ($q['type'] === 'short'): ?>
                <div class="form-group">
                    <input type="text" name="answers[<?= (int)$q['id'] ?>]" placeholder="Your answer">
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary btn-lg">Submit Exam</button>
</form>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';




