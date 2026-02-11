<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
init_session();
require_role('teacher');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (!isset($_FILES['game_file']) || $_FILES['game_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please upload a valid file.';
    } else {
        $file = $_FILES['game_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $content = file_get_contents($file['tmp_name']);

        if ($ext === 'json') {
            $data = validate_json($content);
            if ($data === false) {
                $errors[] = 'Invalid JSON file.';
            } else {
                $validationErrors = validate_game_json($data);
                if (!empty($validationErrors)) {
                    $errors = array_merge($errors, $validationErrors);
                } else {
                    $gameId = Game::importFromJson(current_user_id(), $data);
                    $_SESSION['flash_success'] = 'Game imported successfully from JSON!';
                    header('Location: game_edit.php?id=' . $gameId);
                    exit;
                }
            }
        } elseif ($ext === 'csv') {
            if (empty(trim($content))) {
                $errors[] = 'CSV file is empty.';
            } else {
                $gameId = Game::importFromCsv(current_user_id(), $content);
                $_SESSION['flash_success'] = 'Game imported successfully from CSV!';
                header('Location: game_edit.php?id=' . $gameId);
                exit;
            }
        } else {
            $errors[] = 'Only JSON and CSV files are supported.';
        }
    }
}

$pageTitle = 'Import Escape Room';
ob_start();
?>
<div class="page-title">
    <h1>Import Escape Room</h1>
    <a href="game_list.php" class="btn btn-outline">Back to Games</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Upload JSON or CSV File</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="game_file">Select File (.json or .csv)</label>
            <input type="file" id="game_file" name="game_file" accept=".json,.csv" required>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
    </form>
</div>

<div class="card mt-2">
    <h2>JSON Format Example</h2>
    <pre style="background:#f1f5f9;padding:1rem;border-radius:8px;overflow-x:auto;font-size:0.85rem">{
  "title": "My Escape Room",
  "description": "An adventure game",
  "start_node_key": "start",
  "nodes": [
    {
      "node_key": "start",
      "title": "Entry Hall",
      "description": "You enter a dark hallway...",
      "is_end_node": false,
      "choices": [
        { "choice_text": "Go left", "target_node_key": "room_a" },
        { "choice_text": "Go right", "target_node_key": "room_b" }
      ]
    },
    {
      "node_key": "room_a",
      "title": "Library",
      "description": "Shelves of old books...",
      "is_end_node": false,
      "choices": [
        { "choice_text": "Read a book", "target_node_key": "end" }
      ]
    },
    {
      "node_key": "room_b",
      "title": "Kitchen",
      "description": "A dusty kitchen...",
      "is_end_node": false,
      "choices": [
        { "choice_text": "Search drawers", "target_node_key": "end" }
      ]
    },
    {
      "node_key": "end",
      "title": "Escape!",
      "description": "You found the key and escaped!",
      "is_end_node": true,
      "choices": []
    }
  ]
}</pre>
</div>

<div class="card mt-2">
    <h2>CSV Format Example</h2>
    <pre style="background:#f1f5f9;padding:1rem;border-radius:8px;overflow-x:auto;font-size:0.85rem">node_key,title,description,is_end_node,choice_text,target_node_key
start,Entry Hall,You enter a dark hallway...,0,Go left,room_a
start,Entry Hall,You enter a dark hallway...,0,Go right,room_b
room_a,Library,Shelves of old books...,0,Read a book,end
room_b,Kitchen,A dusty kitchen...,0,Search drawers,end
end,Escape!,You found the key and escaped!,1,,</pre>
    <p class="text-muted form-help">Each row represents a node+choice. Repeat the node for multiple choices.</p>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../../app/views/layout.php';




