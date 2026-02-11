<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/GameNode.php';
require_once __DIR__ . '/../../app/models/GameAttempt.php';
init_session();
require_role('student');

$gameId = validate_int($_GET['game_id'] ?? 0, 1);
if (!$gameId) { http_response_code(404); die('Game not found.'); }

$game = Game::findById($gameId);
if (!$game || !$game['is_published']) {
    http_response_code(404); die('Game not found or not published.');
}

$error = '';


if ($game['access_code'] && $_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_SESSION['game_access_' . $gameId])) {
    $pageTitle = 'Enter Access Code';
    ob_start();
    ?>
    <div class="card" style="max-width:500px;margin:2rem auto">
        <h2>Access Code Required</h2>
        <p>This escape room requires an access code to start.</p>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check_code">
            <div class="form-group">
                <label for="access_code">Access Code</label>
                <input type="text" id="access_code" name="access_code" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
    <?php
    $bodyContent = ob_get_clean();
    include __DIR__ . '/../../app/views/layout.php';
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'check_code') {
    csrf_check();
    $code = trim($_POST['access_code'] ?? '');
    if ($code === $game['access_code']) {
        $_SESSION['game_access_' . $gameId] = true;
        header('Location: game_play.php?game_id=' . $gameId);
        exit;
    } else {
        $error = 'Invalid access code.';
        $pageTitle = 'Enter Access Code';
        ob_start();
        ?>
        <div class="card" style="max-width:500px;margin:2rem auto">
            <h2>Access Code Required</h2>
            <div class="alert alert-error"><?= e($error) ?></div>
            <form method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="check_code">
                <div class="form-group">
                    <label for="access_code">Access Code</label>
                    <input type="text" id="access_code" name="access_code" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
        <?php
        $bodyContent = ob_get_clean();
        include __DIR__ . '/../../app/views/layout.php';
        exit;
    }
}


$nodeId = validate_int($_GET['node_id'] ?? 0, 1);
if (!$nodeId) {
    $nodeId = $game['start_node_id'];
}

if (!$nodeId) {
    die('This game has no starting node.');
}

$node = GameNode::findById($nodeId);
if (!$node || $node['game_id'] != $gameId) {
    die('Invalid node.');
}

$studentId = current_user_id();
$attempt = GameAttempt::getActiveAttempt($studentId, $gameId);
if (!$attempt) {
    $attemptId = GameAttempt::start($studentId, $gameId);
    $attempt = GameAttempt::findById($attemptId);
}

$choiceId = validate_int($_GET['choice_id'] ?? 0, 1);
GameAttempt::logChoice($attempt['id'], $nodeId, $choiceId ?: null);

$choices = Game::getChoicesForNode($nodeId);

$pageTitle = $game['title'] . ' - ' . $node['title'];
ob_start();
?>
<div class="page-title">
    <h1><?= e($game['title']) ?></h1>
    <a href="<?= e(BASE_URL) ?>/student/" class="btn btn-outline">Exit Game</a>
</div>

<div class="card">
    <h2><?= e($node['title']) ?></h2>
    <p style="font-size:1.05rem; line-height:1.7; margin:1rem 0;">
        <?= nl2br(e($node['description'] ?? '')) ?>
    </p>

    <?php if ($node['is_end_node']): ?>
        <div class="alert alert-success">
            <strong>Congratulations!</strong> You have reached the end of this escape room!
        </div>
        <?php if (!$attempt['is_completed']): ?>
            <?php GameAttempt::complete($attempt['id']); ?>
        <?php endif; ?>
        <a href="<?= e(BASE_URL) ?>/student/" class="btn btn-primary">Back to Dashboard</a>
        <a href="game_play.php?game_id=<?= $gameId ?>" class="btn btn-outline">Play Again</a>
    <?php elseif (!empty($choices)): ?>
        <h3>What do you do?</h3>
        <div style="margin-top:0.75rem">
            <?php foreach ($choices as $choice): ?>
                <?php if ($choice['target_node_id']): ?>
                    <a href="game_play.php?game_id=<?= $gameId ?>&node_id=<?= (int)$choice['target_node_id'] ?>&choice_id=<?= (int)$choice['id'] ?>"
                       class="btn btn-primary" style="margin-bottom:0.5rem; display:block; text-align:left;">
                        &rarr; <?= e($choice['choice_text']) ?>
                    </a>
                <?php else: ?>
                    <span class="btn btn-outline" style="margin-bottom:0.5rem; display:block; text-align:left; opacity:0.5; cursor:not-allowed;">
                        &rarr; <?= e($choice['choice_text']) ?> (dead end)
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-muted">No choices available from this node.</p>
        <a href="game_play.php?game_id=<?= $gameId ?>" class="btn btn-outline">Restart</a>
    <?php endif; ?>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';





