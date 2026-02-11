<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Question.php';
init_session();
require_role('teacher');

$examId = validate_int($_GET['id'] ?? 0, 1);
if (!$examId) { http_response_code(404); die('Exam not found.'); }

$exam = Exam::findById($examId);
if (!$exam || !Exam::isOwner($examId, current_user_id())) {
    http_response_code(403); die('Access denied.');
}

$questions = Exam::getQuestions($examId);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = validate_string($_POST['title'] ?? '', 1, 255);
    $description = validate_string($_POST['description'] ?? '', 0, 5000) ?: '';
    $timeLimit   = validate_int($_POST['time_limit_min'] ?? 30, 1, 600);
    $passingScore = validate_float($_POST['passing_score'] ?? 50, 0, 100);
    $accessCode  = trim($_POST['access_code'] ?? '') ?: null;
    $isPublished = !empty($_POST['is_published']);
    $postQuestions = $_POST['questions'] ?? [];

    if ($title === false) $errors[] = 'Title is required.';
    if ($timeLimit === false) $errors[] = 'Time limit must be 1-600 minutes.';
    if ($passingScore === false) $errors[] = 'Passing score must be 0-100.';

    if (empty($errors)) {
        Exam::update($examId, $title, $description, $timeLimit, $passingScore, $accessCode, $isPublished);

        
        Question::deleteByExam($examId);
        foreach ($postQuestions as $i => $q) {
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

        $_SESSION['flash_success'] = 'Exam updated successfully!';
        header('Location: exam_edit.php?id=' . $examId);
        exit;
    }

    
    $exam = Exam::findById($examId);
    $questions = Exam::getQuestions($examId);
}

$pageTitle = 'Edit Exam: ' . $exam['title'];
ob_start();
?>
<div class="page-title">
    <h1>Edit Exam</h1>
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
        <input type="text" id="title" name="title" required value="<?= e($exam['title']) ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($exam['description'] ?? '') ?></textarea>
    </div>

    <div class="form-inline">
        <div class="form-group">
            <label for="time_limit_min">Time Limit (min)</label>
            <input type="number" id="time_limit_min" name="time_limit_min" min="1" max="600"
                   value="<?= (int)$exam['time_limit_min'] ?>">
        </div>
        <div class="form-group">
            <label for="passing_score">Passing Score (%)</label>
            <input type="number" id="passing_score" name="passing_score" min="0" max="100" step="0.5"
                   value="<?= e($exam['passing_score']) ?>">
        </div>
        <div class="form-group">
            <label for="access_code">Access Code</label>
            <input type="text" id="access_code" name="access_code" maxlength="20"
                   value="<?= e($exam['access_code'] ?? '') ?>">
        </div>
    </div>

    <div class="form-check mt-2">
        <input type="checkbox" id="is_published" name="is_published" value="1"
               <?= $exam['is_published'] ? 'checked' : '' ?>>
        <label for="is_published">Published (visible to students)</label>
    </div>

    <hr style="margin: 1.5rem 0">

    <h2>Questions</h2>
    <div id="questions-container">
        <?php foreach ($questions as $i => $q): ?>
            <?php
            $opts = '';
            if ($q['type'] === 'mcq' && $q['options_json']) {
                $optsArr = json_decode($q['options_json'], true);
                $opts = is_array($optsArr) ? implode("\n", $optsArr) : '';
            }
            ?>
            <div class="question-block card" data-question-id="<?= (int)$q['id'] ?>">
                <div class="card-header">
                    <h3>Question 
                    <button type="button" class="btn btn-sm btn-danger remove-question-btn">Remove</button>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="questions[<?= $i ?>][type]" class="question-type-select">
                        <option value="mcq" <?= $q['type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
                        <option value="tf" <?= $q['type'] === 'tf' ? 'selected' : '' ?>>True/False</option>
                        <option value="short" <?= $q['type'] === 'short' ? 'selected' : '' ?>>Short Answer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="questions[<?= $i ?>][question_text]" required><?= e($q['question_text']) ?></textarea>
                </div>
                <div class="form-group options-field" style="display:<?= $q['type'] === 'mcq' ? 'block' : 'none' ?>">
                    <label>Options (one per line)</label>
                    <textarea name="questions[<?= $i ?>][options]"><?= e($opts) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Correct Answer</label>
                    <input type="text" name="questions[<?= $i ?>][correct_answer]" value="<?= e($q['correct_answer']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Points</label>
                    <input type="number" name="questions[<?= $i ?>][points]" value="<?= e($q['points']) ?>" min="0.5" step="0.5">
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-question-btn" class="btn btn-outline mt-1">+ Add Question</button>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';







