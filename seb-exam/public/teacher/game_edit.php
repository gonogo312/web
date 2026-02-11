<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/GameNode.php';
require_once __DIR__ . '/../../app/models/GameChoice.php';
init_session();
require_role('teacher');

$gameId = validate_int($_GET['id'] ?? 0, 1);
if (!$gameId) { http_response_code(404); die('Game not found.'); }

$game = Game::findById($gameId);
if (!$game || !Game::isOwner($gameId, current_user_id())) {
    http_response_code(403); die('Access denied.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title       = validate_string($_POST['title'] ?? '', 1, 255);
    $description = validate_string($_POST['description'] ?? '', 0, 5000) ?: '';
    $accessCode  = trim($_POST['access_code'] ?? '') ?: null;
    $isPublished = !empty($_POST['is_published']);
    $nodesData   = $_POST['nodes'] ?? [];

    if ($title === false) $errors[] = 'Title is required.';

    if (empty($errors)) {
        Game::update($gameId, $title, $description, $accessCode, $isPublished);

        
        GameNode::deleteByGame($gameId);

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

        $_SESSION['flash_success'] = 'Game updated successfully!';
        header('Location: game_edit.php?id=' . $gameId);
        exit;
    }

    $game = Game::findById($gameId);
}

$nodes = Game::getFullStructure($gameId);

$pageTitle = 'Edit Game: ' . $game['title'];
ob_start();
?>
<div class="page-title">
    <h1>Edit Escape Room</h1>
    <div class="btn-group">
        <a href="game_list.php" class="btn btn-outline">Back to Games</a>
        <a href="game_export.php?id=<?= $gameId ?>" class="btn btn-success">Export JSON</a>
        <a href="seb_config.php?activity_type=game&activity_id=<?= $gameId ?>" class="btn btn-primary">Generate SEB Config</a>
    </div>
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
        <input type="text" id="title" name="title" required value="<?= e($game['title']) ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($game['description'] ?? '') ?></textarea>
    </div>

    <div class="form-inline">
        <div class="form-group">
            <label for="access_code">Access Code</label>
            <input type="text" id="access_code" name="access_code" maxlength="20"
                   value="<?= e($game['access_code'] ?? '') ?>">
        </div>
    </div>

    <div class="form-check mt-2">
        <input type="checkbox" id="is_published" name="is_published" value="1"
               <?= $game['is_published'] ? 'checked' : '' ?>>
        <label for="is_published">Published</label>
    </div>

    <hr style="margin: 1.5rem 0">

    <h2>Nodes</h2>
    <div id="nodes-container">
        <?php foreach ($nodes as $i => $node): ?>
            <div class="node-block card" data-node-index="<?= $i ?>">
                <div class="card-header">
                    <h3>Node 
                    <button type="button" class="btn btn-sm btn-danger remove-node-btn">Remove Node</button>
                </div>
                <div class="form-group">
                    <label>Node Key</label>
                    <input type="text" name="nodes[<?= $i ?>][node_key]" value="<?= e($node['node_key']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="nodes[<?= $i ?>][title]" value="<?= e($node['title']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="nodes[<?= $i ?>][description]"><?= e($node['description'] ?? '') ?></textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="nodes[<?= $i ?>][is_end_node]" value="1"
                           <?= $node['is_end_node'] ? 'checked' : '' ?>>
                    <label>End Node</label>
                </div>
                <h4>Choices</h4>
                <div class="choices-list">
                    <?php foreach ($node['choices'] as $j => $choice): ?>
                        <?php
                        
                        $targetKey = '';
                        if ($choice['target_node_id']) {
                            foreach ($nodes as $n) {
                                if ($n['id'] == $choice['target_node_id']) {
                                    $targetKey = $n['node_key'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="choice-entry d-flex gap-1 align-center mb-1">
                            <input type="text" name="nodes[<?= $i ?>][choices][<?= $j ?>][choice_text]"
                                   value="<?= e($choice['choice_text']) ?>" placeholder="Choice text" style="flex:2">
                            <input type="text" name="nodes[<?= $i ?>][choices][<?= $j ?>][target_node_key]"
                                   value="<?= e($targetKey) ?>" placeholder="Target node key" style="flex:1">
                            <button type="button" class="btn btn-sm btn-danger remove-choice-btn">X</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline add-choice-btn mt-1">+ Add Choice</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-node-btn" class="btn btn-outline mt-1">+ Add Node</button>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';




