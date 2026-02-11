<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
init_session();
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: game_list.php');
    exit;
}

csrf_check();

$gameId = validate_int($_POST['id'] ?? 0, 1);
if (!$gameId || !Game::isOwner($gameId, current_user_id())) {
    $_SESSION['flash_error'] = 'Game not found or access denied.';
    header('Location: game_list.php');
    exit;
}

Game::delete($gameId);
$_SESSION['flash_success'] = 'Escape room deleted.';
header('Location: game_list.php');
exit;




