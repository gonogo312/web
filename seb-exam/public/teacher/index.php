<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
init_session();
require_role('teacher');

$pdo = db();
$tid = current_user_id();


$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM exams WHERE teacher_id = :tid');
$stmt->execute([':tid' => $tid]);
$examCount = $stmt->fetch()['cnt'];


$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM games WHERE teacher_id = :tid');
$stmt->execute([':tid' => $tid]);
$gameCount = $stmt->fetch()['cnt'];


$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM seb_configs WHERE teacher_id = :tid');
$stmt->execute([':tid' => $tid]);
$sebCount = $stmt->fetch()['cnt'];


$stmt = $pdo->prepare(
    'SELECT COUNT(*) as cnt FROM attempts a
     JOIN exams e ON a.exam_id = e.id
     WHERE e.teacher_id = :tid AND a.is_submitted = 1'
);
$stmt->execute([':tid' => $tid]);
$attemptCount = $stmt->fetch()['cnt'];


$stmt = $pdo->prepare(
    'SELECT a.*, e.title as exam_title, u.full_name as student_name
     FROM attempts a
     JOIN exams e ON a.exam_id = e.id
     JOIN users u ON a.student_id = u.id
     WHERE e.teacher_id = :tid AND a.is_submitted = 1
     ORDER BY a.finished_at DESC
     LIMIT 10'
);
$stmt->execute([':tid' => $tid]);
$recentAttempts = $stmt->fetchAll();

$pageTitle = 'Teacher Dashboard';
ob_start();
?>
<div class="page-title">
    <h1>Teacher Dashboard</h1>
    <p class="text-muted">Welcome back, <?= e(current_user_name()) ?></p>
</div>

<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-value"><?= (int)$examCount ?></div>
        <div class="stat-label">Exams</div>
        <a href="exam_list.php" class="btn btn-sm btn-outline mt-1">Manage</a>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)$gameCount ?></div>
        <div class="stat-label">Escape Rooms</div>
        <a href="game_list.php" class="btn btn-sm btn-outline mt-1">Manage</a>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)$sebCount ?></div>
        <div class="stat-label">SEB Configs</div>
        <a href="seb_list.php" class="btn btn-sm btn-outline mt-1">Manage</a>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)$attemptCount ?></div>
        <div class="stat-label">Student Attempts</div>
    </div>
</div>

<div class="card mt-3">
    <h2>Recent Student Attempts</h2>
    <?php if (empty($recentAttempts)): ?>
        <p class="text-muted">No attempts recorded yet.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentAttempts as $att): ?>
                    <tr>
                        <td><?= e($att['student_name']) ?></td>
                        <td><?= e($att['exam_title']) ?></td>
                        <td>
                            <?= e($att['score']) ?> / <?= e($att['max_score']) ?>
                            <?php
                            $pct = $att['max_score'] > 0 ? round($att['score'] / $att['max_score'] * 100) : 0;
                            $badge = $pct >= 60 ? 'badge-success' : ($pct >= 40 ? 'badge-warning' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badge ?>"><?= $pct ?>%</span>
                        </td>
                        <td><?= e($att['finished_at'] ?? 'N/A') ?></td>
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




