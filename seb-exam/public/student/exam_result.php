<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Attempt.php';
init_session();
require_role('student');

$attemptId = validate_int($_GET['attempt_id'] ?? 0, 1);
if (!$attemptId) { http_response_code(404); die('Attempt not found.'); }

$attempt = Attempt::findById($attemptId);
if (!$attempt || $attempt['student_id'] !== current_user_id() || !$attempt['is_submitted']) {
    http_response_code(403); die('Access denied.');
}

$exam = Exam::findById($attempt['exam_id']);
$answers = Attempt::getAnswers($attemptId);

$pct = $attempt['max_score'] > 0
    ? round($attempt['score'] / $attempt['max_score'] * 100, 1)
    : 0;
$passed = $pct >= (float)$exam['passing_score'];

$pageTitle = 'Exam Result';
ob_start();
?>
<div class="page-title">
    <h1>Exam Result: <?= e($exam['title']) ?></h1>
    <a href="<?= e(BASE_URL) ?>/student/" class="btn btn-outline">Back to Dashboard</a>
</div>

<div class="card text-center">
    <h2>Your Score</h2>
    <div class="prediction-gauge <?= $passed ? 'high' : ($pct >= 40 ? 'medium' : 'low') ?>">
        <?= $pct ?>%
    </div>
    <p>
        <strong><?= e($attempt['score']) ?> / <?= e($attempt['max_score']) ?></strong> points
        &bull;
        <?php if ($passed): ?>
            <span class="badge badge-success">PASSED</span>
        <?php else: ?>
            <span class="badge badge-danger">NOT PASSED</span>
        <?php endif; ?>
        (required: <?= e($exam['passing_score']) ?>%)
    </p>
    <p class="text-muted">
        Started: <?= e($attempt['started_at']) ?> &bull;
        Finished: <?= e($attempt['finished_at']) ?>
    </p>
</div>

<div class="card">
    <h2>Detailed Answers</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>
                    <th>Question</th>
                    <th>Your Answer</th>
                    <th>Correct Answer</th>
                    <th>Points</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($answers as $i => $a): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($a['question_text']) ?></td>
                    <td><?= e($a['student_answer'] ?: '(no answer)') ?></td>
                    <td><?= e($a['correct_answer']) ?></td>
                    <td><?= e($a['points_awarded']) ?> / <?= e($a['points']) ?></td>
                    <td>
                        <?php if ($a['is_correct']): ?>
                            <span class="badge badge-success">Correct</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Incorrect</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';






