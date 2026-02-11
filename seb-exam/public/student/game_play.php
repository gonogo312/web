<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/GameNode.php';
init_session();
require_role('student');

$gameId = validate_int($_GET['game_id'] ?? 0, 1);
if (!$gameId) { http_response_code(404); die('Game not found.'); }

$game = Game::findById($gameId);
if (!$game || !$game['is_published']) {
    http_response_code(404); die('Game not found or not published.');
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
        <a href="<?= e(BASE_URL) ?>/student/" class="btn btn-primary">Back to Dashboard</a>
        <a href="game_play.php?game_id=<?= $gameId ?>" class="btn btn-outline">Play Again</a>
    <?php elseif (!empty($choices)): ?>
        <h3>What do you do?</h3>
        <div style="margin-top:0.75rem">
            <?php foreach ($choices as $choice): ?>
                <?php if ($choice['target_node_id']): ?>
                    <a href="game_play.php?game_id=<?= $gameId ?>&node_id=<?= (int)$choice['target_node_id'] ?>"
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




