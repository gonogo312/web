<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Question.php';
init_session();
require_role('teacher');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = validate_string($_POST['title'] ?? '', 1, 255);
    $description = validate_string($_POST['description'] ?? '', 0, 5000) ?: '';
    $timeLimit   = validate_int($_POST['time_limit_min'] ?? 30, 1, 600);
    $passingScore = validate_float($_POST['passing_score'] ?? 50, 0, 100);
    $accessCode  = trim($_POST['access_code'] ?? '') ?: null;
    $isPublished = !empty($_POST['is_published']);
    $questions   = $_POST['questions'] ?? [];

    if ($title === false) $errors[] = 'Title is required (max 255 chars).';
    if ($timeLimit === false) $errors[] = 'Time limit must be 1-600 minutes.';
    if ($passingScore === false) $errors[] = 'Passing score must be 0-100.';
    if (empty($questions)) $errors[] = 'At least one question is required.';

    if (empty($errors)) {
        $examId = Exam::create(
            current_user_id(), $title, $description,
            $timeLimit, $passingScore, $accessCode, $isPublished
        );

        
        foreach ($questions as $i => $q) {
            $qType = validate_enum($q['type'] ?? 'mcq', ['mcq', 'tf', 'short']) ?: 'mcq';
            $qText = trim($q['question_text'] ?? '');
            $qCorrect = trim($q['correct_answer'] ?? '');
            $qPoints = (float)($q['points'] ?? 1);
            $qOptions = null;

            if ($qType === 'mcq' && !empty($q['options'])) {
                $opts = array_filter(array_map('trim', explode("\n", $q['options'])));
                $qOptions = json_encode(array_values($opts));
            }

            if ($qText && $qCorrect) {
                Question::create($examId, $qType, $qText, $qOptions, $qCorrect, $qPoints, $i);
            }
        }

        $_SESSION['flash_success'] = 'Exam created successfully!';
        header('Location: exam_edit.php?id=' . $examId);
        exit;
    }
}

$pageTitle = 'Create Exam';
ob_start();
?>
<div class="page-title">
    <h1>Create New Exam</h1>
    <a href="exam_list.php" class="btn btn-outline">Back to Exams</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="card">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="title">Exam Title *</label>
        <input type="text" id="title" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-inline">
        <div class="form-group">
            <label for="time_limit_min">Time Limit (min)</label>
            <input type="number" id="time_limit_min" name="time_limit_min" min="1" max="600"
                   value="<?= e($_POST['time_limit_min'] ?? '30') ?>">
        </div>
        <div class="form-group">
            <label for="passing_score">Passing Score (%)</label>
            <input type="number" id="passing_score" name="passing_score" min="0" max="100" step="0.5"
                   value="<?= e($_POST['passing_score'] ?? '50') ?>">
        </div>
        <div class="form-group">
            <label for="access_code">Access Code (optional)</label>
            <input type="text" id="access_code" name="access_code" maxlength="20"
                   value="<?= e($_POST['access_code'] ?? '') ?>" placeholder="Leave empty for no code">
        </div>
    </div>

    <div class="form-check mt-2">
        <input type="checkbox" id="is_published" name="is_published" value="1"
               <?= !empty($_POST['is_published']) ? 'checked' : '' ?>>
        <label for="is_published">Publish immediately (visible to students)</label>
    </div>

    <hr style="margin: 1.5rem 0">

    <h2>Questions</h2>
    <div id="questions-container">
        
    </div>
    <button type="button" id="add-question-btn" class="btn btn-outline mt-1">+ Add Question</button>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Create Exam</button>
    </div>
</form>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';






