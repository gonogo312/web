<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';
init_session();
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: seb_list.php');
    exit;
}

csrf_check();

$configId = validate_int($_POST['id'] ?? 0, 1);
if (!$configId || !SebConfig::isOwner($configId, current_user_id())) {
    $_SESSION['flash_error'] = 'Config not found or access denied.';
    header('Location: seb_list.php');
    exit;
}

SebConfig::delete($configId);
$_SESSION['flash_success'] = 'SEB configuration deleted.';
header('Location: seb_list.php');
exit;




