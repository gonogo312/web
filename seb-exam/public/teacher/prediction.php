<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/lib/prediction.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Exam.php';
init_session();
require_role('teacher');

$tid = current_user_id();
$students = User::getStudents();
$exams = Exam::findByTeacher($tid);

$result = null;
$selectedStudent = null;
$selectedExam = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $studentId = validate_int($_POST['student_id'] ?? 0, 1);
    $examId = validate_int($_POST['exam_id'] ?? 0, 1);

    if ($studentId && $examId) {
        $selectedStudent = User::findById($studentId);
        $selectedExam = Exam::findById($examId);

        if ($selectedStudent && $selectedExam && Exam::isOwner($examId, $tid)) {
            $result = predict_pass_probability($studentId, $examId);
        }
    }
}

$pageTitle = 'Student Pass Prediction';
ob_start();
?>
<div class="page-title">
    <h1>Student Pass Prediction</h1>
</div>

<div class="card">
    <h2>Select Student & Exam</h2>
    <form method="POST" action="" class="form-inline">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="student_id">Student</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Select Student --</option>
                <?php foreach ($students as $st): ?>
                    <option value="<?= (int)$st['id'] ?>"
                            <?= ($selectedStudent && $selectedStudent['id'] == $st['id']) ? 'selected' : '' ?>>
                        <?= e($st['full_name']) ?> (<?= e($st['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="exam_id">Exam</label>
            <select id="exam_id" name="exam_id" required>
                <option value="">-- Select Exam --</option>
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= (int)$ex['id'] ?>"
                            <?= ($selectedExam && $selectedExam['id'] == $ex['id']) ? 'selected' : '' ?>>
                        <?= e($ex['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Predict</button>
    </form>
</div>

<?php if ($result !== null && $selectedStudent && $selectedExam): ?>
    <div class="card mt-2 text-center">
        <h2>Prediction for <?= e($selectedStudent['full_name']) ?></h2>
        <h3 class="text-muted"><?= e($selectedExam['title']) ?> (Pass: <?= e($selectedExam['passing_score']) ?>%)</h3>

        <?php
        $prob = $result['probability'];
        $gaugeClass = $prob >= 65 ? 'high' : ($prob >= 35 ? 'medium' : 'low');
        ?>
        <div class="prediction-gauge <?= $gaugeClass ?>">
            <?= $prob ?>%
        </div>
        <p style="font-size:1.1rem;margin:0.5rem 0">
            <?php if ($prob >= 65): ?>
                <strong>High probability</strong> of passing this exam.
            <?php elseif ($prob >= 35): ?>
                <strong>Moderate probability</strong> of passing this exam.
            <?php else: ?>
                <strong>Low probability</strong> of passing. Additional preparation recommended.
            <?php endif; ?>
        </p>
        <p class="text-muted"><?= e($result['explanation']) ?></p>
    </div>

    <div class="card mt-2">
        <h2>Feature Breakdown</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Value</th>
                        <th>Impact</th>
                        <th>Weight</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['features'] as $feat): ?>
                    <?php
                    $impactClass = 'badge-info';
                    if ($feat['contribution'] === 'Positive') $impactClass = 'badge-success';
                    elseif ($feat['contribution'] === 'Negative') $impactClass = 'badge-danger';
                    elseif ($feat['contribution'] === 'Neutral') $impactClass = 'badge-warning';
                    ?>
                    <tr>
                        <td><strong><?= e($feat['name']) ?></strong></td>
                        <td><?= e($feat['value']) ?></td>
                        <td><span class="badge <?= $impactClass ?>"><?= e($feat['contribution']) ?></span></td>
                        <td><?= isset($feat['weight']) ? e($feat['weight']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';






