<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Exam.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/Attempt.php';
require_once __DIR__ . '/../../app/models/GameAttempt.php';
init_session();
require_role('teacher');

$tid = current_user_id();
$pdo = db();

$students = User::getStudents();
$exams = Exam::findByTeacher($tid);
$games = Game::findByTeacher($tid);

$studentId = validate_int($_GET['student_id'] ?? 0, 0) ?: 0;
$examId = validate_int($_GET['exam_id'] ?? 0, 0) ?: 0;
$gameId = validate_int($_GET['game_id'] ?? 0, 0) ?: 0;
$activityType = validate_enum($_GET['activity_type'] ?? 'all', ['all', 'exam', 'game']) ?: 'all';

$examAttempts = [];
if ($activityType !== 'game') {
    $sql = 'SELECT a.*, e.title as exam_title, u.full_name as student_name, u.username
            FROM attempts a
            JOIN exams e ON a.exam_id = e.id
            JOIN users u ON a.student_id = u.id
            WHERE e.teacher_id = :tid AND a.is_submitted = 1';
    $params = [':tid' => $tid];
    if ($studentId) {
        $sql .= ' AND a.student_id = :sid';
        $params[':sid'] = $studentId;
    }
    if ($examId) {
        $sql .= ' AND a.exam_id = :eid';
        $params[':eid'] = $examId;
    }
    $sql .= ' ORDER BY a.finished_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $examAttempts = $stmt->fetchAll();
}

$gameAttempts = [];
if ($activityType !== 'exam') {
    $gameAttempts = GameAttempt::findByTeacher($tid, $studentId ?: null, $gameId ?: null);
}

$pageTitle = 'Statistics';
ob_start();
?>
<div class="page-title">
    <h1>Statistics</h1>
    <p class="text-muted">Filter by student and activity to review answers and paths.</p>
</div>

<form method="GET" class="card">
    <div class="form-inline">
        <div class="form-group">
            <label for="student_id">Student</label>
            <select id="student_id" name="student_id">
                <option value="0">All Students</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int)$student['id'] ?>" <?= $studentId == $student['id'] ? 'selected' : '' ?>>
                        <?= e($student['full_name'] ?: $student['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="stats-activity-type">Activity Type</label>
            <select id="stats-activity-type" name="activity_type">
                <option value="all" <?= $activityType === 'all' ? 'selected' : '' ?>>All</option>
                <option value="exam" <?= $activityType === 'exam' ? 'selected' : '' ?>>Exams</option>
                <option value="game" <?= $activityType === 'game' ? 'selected' : '' ?>>Escape Rooms</option>
            </select>
        </div>
        <div class="form-group" id="stats-exam-filter">
            <label for="exam_id">Exam</label>
            <select id="exam_id" name="exam_id">
                <option value="0">All Exams</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?= (int)$exam['id'] ?>" <?= $examId == $exam['id'] ? 'selected' : '' ?>>
                        <?= e($exam['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" id="stats-game-filter">
            <label for="game_id">Escape Room</label>
            <select id="game_id" name="game_id">
                <option value="0">All Escape Rooms</option>
                <?php foreach ($games as $game): ?>
                    <option value="<?= (int)$game['id'] ?>" <?= $gameId == $game['id'] ? 'selected' : '' ?>>
                        <?= e($game['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
        </div>
    </div>
</form>

<?php if ($activityType !== 'game'): ?>
    <div class="card">
        <h2>Exam Answers</h2>
        <?php if (empty($examAttempts)): ?>
            <p class="text-muted">No exam attempts match the current filters.</p>
        <?php else: ?>
            <?php foreach ($examAttempts as $attempt): ?>
                <?php $answers = Attempt::getAnswers($attempt['id']); ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div class="d-flex justify-between align-center mb-1">
                        <div>
                            <strong><?= e($attempt['exam_title']) ?></strong>
                            &bull; <?= e($attempt['student_name'] ?: $attempt['username']) ?>
                        </div>
                        <div class="text-muted">
                            <?= e($attempt['finished_at'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Student Answer</th>
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
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($activityType !== 'exam'): ?>
    <div class="card">
        <h2>Escape Room Paths</h2>
        <?php if (empty($gameAttempts)): ?>
            <p class="text-muted">No escape room attempts match the current filters.</p>
        <?php else: ?>
            <?php foreach ($gameAttempts as $attempt): ?>
                <?php $logs = GameAttempt::getLogs($attempt['id']); ?>
                <div class="card" style="margin-bottom:1rem;">
                    <div class="d-flex justify-between align-center mb-1">
                        <div>
                            <strong><?= e($attempt['game_title']) ?></strong>
                            &bull; <?= e($attempt['student_name'] ?: $attempt['username']) ?>
                        </div>
                        <div class="text-muted">
                            <?= e($attempt['finished_at'] ?? 'N/A') ?>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Node</th>
                                    <th>Choice</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($logs as $i => $log): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($log['node_title']) ?></td>
                                    <td><?= e($log['choice_text'] ?: '(start)') ?></td>
                                    <td><?= e($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';



