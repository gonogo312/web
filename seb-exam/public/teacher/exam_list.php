<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/models/Exam.php';
init_session();
require_role('teacher');

$exams = Exam::findByTeacher(current_user_id());

$pageTitle = 'My Exams';
ob_start();
?>
<div class="page-title">
    <h1>My Exams</h1>
    <a href="exam_create.php" class="btn btn-primary">+ Create Exam</a>
</div>

<?php if (empty($exams)): ?>
    <div class="card">
        <p class="text-muted">You haven't created any exams yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Time</th>
                    <th>Pass %</th>
                    <th>Questions</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($exams as $exam): ?>
                <?php $stats = Exam::getQuestionStats($exam['id']); ?>
                <tr>
                    <td><strong><?= e($exam['title']) ?></strong></td>
                    <td><?= (int)$exam['time_limit_min'] ?> min</td>
                    <td><?= e($exam['passing_score']) ?>%</td>
                    <td><?= (int)$stats['count'] ?> (<?= e($stats['total_points']) ?> pts)</td>
                    <td><?= $exam['access_code'] ? e($exam['access_code']) : '—' ?></td>
                    <td>
                        <?php if ($exam['is_published']): ?>
                            <span class="badge badge-success">Published</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="exam_edit.php?id=<?= (int)$exam['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <button type="button"
                                    class="btn btn-sm btn-outline btn-copy-link"
                                    data-url="<?= e(BASE_URL . '/student/exam_take.php?exam_id=' . (int)$exam['id']) ?>">
                                Copy Link
                            </button>
                            <form method="POST" action="exam_delete.php" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$exam['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger btn-delete-confirm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';





