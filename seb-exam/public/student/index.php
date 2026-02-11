<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
init_session();
require_role('student');

$pdo = db();
$sid = current_user_id();


$stmt = $pdo->prepare('SELECT * FROM exams WHERE is_published = 1 ORDER BY created_at DESC');
$stmt->execute();
$exams = $stmt->fetchAll();


$stmt = $pdo->prepare('SELECT * FROM games WHERE is_published = 1 ORDER BY created_at DESC');
$stmt->execute();
$games = $stmt->fetchAll();


$stmt = $pdo->prepare(
    'SELECT a.*, e.title as exam_title
     FROM attempts a
     JOIN exams e ON a.exam_id = e.id
     WHERE a.student_id = :sid AND a.is_submitted = 1
     ORDER BY a.finished_at DESC
     LIMIT 10'
);
$stmt->execute([':sid' => $sid]);
$myAttempts = $stmt->fetchAll();

$pageTitle = 'Student Dashboard';
ob_start();
?>
<div class="page-title">
    <h1>Student Dashboard</h1>
    <p class="text-muted">Welcome, <?= e(current_user_name()) ?></p>
</div>

<div class="card">
    <h2>Available Exams</h2>
    <?php if (empty($exams)): ?>
        <p class="text-muted">No exams available at this time.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Time Limit</th>
                        <th>Passing Score</th>
                        <th>Access Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?= e($exam['title']) ?></td>
                        <td><?= (int)$exam['time_limit_min'] ?> min</td>
                        <td><?= e($exam['passing_score']) ?>%</td>
                        <td>
                            <?= $exam['access_code'] ? '<span class="badge badge-info">Required</span>' : 'None' ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="exam_take.php?exam_id=<?= (int)$exam['id'] ?>" class="btn btn-sm btn-primary">Take Exam</a>
                                <button type="button"
                                        class="btn btn-sm btn-outline btn-copy-link"
                                        data-url="<?= e(BASE_URL . '/student/exam_take.php?exam_id=' . (int)$exam['id']) ?>">
                                    Copy Link
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Available Escape Rooms</h2>
    <?php if (empty($games)): ?>
        <p class="text-muted">No escape rooms available at this time.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td><?= e($game['title']) ?></td>
                        <td><?= e(mb_substr($game['description'] ?? '', 0, 100)) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="game_play.php?game_id=<?= (int)$game['id'] ?>" class="btn btn-sm btn-success">Play</a>
                                <button type="button"
                                        class="btn btn-sm btn-outline btn-copy-link"
                                        data-url="<?= e(BASE_URL . '/student/game_play.php?game_id=' . (int)$game['id']) ?>">
                                    Copy Link
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>My Recent Results</h2>
    <?php if (empty($myAttempts)): ?>
        <p class="text-muted">You haven't completed any exams yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($myAttempts as $att): ?>
                    <tr>
                        <td><?= e($att['exam_title']) ?></td>
                        <td>
                            <?= e($att['score']) ?> / <?= e($att['max_score']) ?>
                            <?php
                            $pct = $att['max_score'] > 0 ? round($att['score'] / $att['max_score'] * 100) : 0;
                            $badge = $pct >= 60 ? 'badge-success' : ($pct >= 40 ? 'badge-warning' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badge ?>"><?= $pct ?>%</span>
                        </td>
                        <td><?= e($att['finished_at'] ?? '') ?></td>
                        <td>
                            <a href="exam_result.php?attempt_id=<?= (int)$att['id'] ?>" class="btn btn-sm btn-outline">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';





