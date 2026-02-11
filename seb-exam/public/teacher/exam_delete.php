<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Exam.php';
init_session();
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exam_list.php');
    exit;
}

csrf_check();

$examId = validate_int($_POST['id'] ?? 0, 1);
if (!$examId || !Exam::isOwner($examId, current_user_id())) {
    $_SESSION['flash_error'] = 'Exam not found or access denied.';
    header('Location: exam_list.php');
    exit;
}

Exam::delete($examId);
$_SESSION['flash_success'] = 'Exam deleted successfully.';
header('Location: exam_list.php');
exit;






