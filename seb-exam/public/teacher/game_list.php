<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/models/Game.php';
init_session();
require_role('teacher');

$games = Game::findByTeacher(current_user_id());

$pageTitle = 'My Escape Rooms';
ob_start();
?>
<div class="page-title">
    <h1>My Escape Rooms</h1>
    <div class="btn-group">
        <a href="game_create.php" class="btn btn-primary">+ Create Game</a>
        <a href="game_import.php" class="btn btn-outline">Import JSON/CSV</a>
    </div>
</div>

<?php if (empty($games)): ?>
    <div class="card">
        <p class="text-muted">You haven't created any escape rooms yet.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Nodes</th>
                    <th>Code</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($games as $game): ?>
                <?php $nodes = Game::getNodes($game['id']); ?>
                <tr>
                    <td><strong><?= e($game['title']) ?></strong></td>
                    <td><?= count($nodes) ?></td>
                    <td><?= $game['access_code'] ? e($game['access_code']) : '—' ?></td>
                    <td>
                        <?php if ($game['is_published']): ?>
                            <span class="badge badge-success">Published</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="game_edit.php?id=<?= (int)$game['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="game_export.php?id=<?= (int)$game['id'] ?>" class="btn btn-sm btn-success">Export JSON</a>
                            <form method="POST" action="game_delete.php" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$game['id'] ?>">
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




