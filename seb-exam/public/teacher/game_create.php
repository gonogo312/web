<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/GameNode.php';
require_once __DIR__ . '/../../app/models/GameChoice.php';
init_session();
require_role('teacher');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = validate_string($_POST['title'] ?? '', 1, 255);
    $description = validate_string($_POST['description'] ?? '', 0, 5000) ?: '';
    $accessCode  = trim($_POST['access_code'] ?? '') ?: null;
    $isPublished = !empty($_POST['is_published']);
    $nodesData   = $_POST['nodes'] ?? [];

    if ($title === false) $errors[] = 'Title is required.';
    if (empty($nodesData)) $errors[] = 'At least one node is required.';

    if (empty($errors)) {
        $gameId = Game::create(current_user_id(), $title, $description, $accessCode, $isPublished);

        $nodeKeyToId = [];
        
        foreach ($nodesData as $i => $nd) {
            $nodeKey = trim($nd['node_key'] ?? 'node_' . $i);
            $nodeTitle = trim($nd['title'] ?? 'Untitled');
            $nodeDesc = trim($nd['description'] ?? '');
            $isEnd = !empty($nd['is_end_node']);

            $nodeId = GameNode::create($gameId, $nodeKey, $nodeTitle, $nodeDesc, $isEnd, $i);
            $nodeKeyToId[$nodeKey] = $nodeId;
        }

        
        if (!empty($nodeKeyToId)) {
            Game::setStartNode($gameId, reset($nodeKeyToId));
        }

        
        foreach ($nodesData as $i => $nd) {
            $nodeKey = trim($nd['node_key'] ?? 'node_' . $i);
            $nodeId = $nodeKeyToId[$nodeKey] ?? null;
            if (!$nodeId || empty($nd['choices'])) continue;

            foreach ($nd['choices'] as $j => $ch) {
                $choiceText = trim($ch['choice_text'] ?? '');
                $targetKey = trim($ch['target_node_key'] ?? '');
                $targetId = $nodeKeyToId[$targetKey] ?? null;

                if ($choiceText) {
                    GameChoice::create($nodeId, $choiceText, $targetId, $j);
                }
            }
        }

        $_SESSION['flash_success'] = 'Escape room created!';
        header('Location: game_edit.php?id=' . $gameId);
        exit;
    }
}

$pageTitle = 'Create Escape Room';
ob_start();
?>
<div class="page-title">
    <h1>Create Escape Room</h1>
    <a href="game_list.php" class="btn btn-outline">Back to Games</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="" class="card">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="title">Game Title *</label>
        <input type="text" id="title" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($_POST['description'] ?? '') ?></textarea>
    </div>

    <div class="form-inline">
        <div class="form-group">
            <label for="access_code">Access Code (optional)</label>
            <input type="text" id="access_code" name="access_code" maxlength="20"
                   value="<?= e($_POST['access_code'] ?? '') ?>">
        </div>
    </div>

    <div class="form-check mt-2">
        <input type="checkbox" id="is_published" name="is_published" value="1"
               <?= !empty($_POST['is_published']) ? 'checked' : '' ?>>
        <label for="is_published">Publish immediately</label>
    </div>

    <hr style="margin: 1.5rem 0">

    <h2>Nodes</h2>
    <p class="text-muted">Add rooms/scenes for your escape room. The first node is the starting point.</p>
    <div id="nodes-container"></div>
    <button type="button" id="add-node-btn" class="btn btn-outline mt-1">+ Add Node</button>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Create Game</button>
    </div>
</form>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';




