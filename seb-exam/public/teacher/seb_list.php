<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/csrf.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';
init_session();
require_role('teacher');

$configs = SebConfig::findByTeacher(current_user_id());

$pageTitle = 'SEB Configurations';
ob_start();
?>
<div class="page-title">
    <h1>SEB Configurations</h1>
    <a href="seb_config.php" class="btn btn-primary">+ Create Config</a>
</div>

<?php if (empty($configs)): ?>
    <div class="card">
        <p class="text-muted">No SEB configurations yet. Create one from an exam or game, or use the button above.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Activity</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($configs as $cfg): ?>
                <tr>
                    <td><strong><?= e($cfg['title']) ?></strong></td>
                    <td>
                        <span class="badge badge-info"><?= e(ucfirst($cfg['activity_type'])) ?></span>
                        
                    </td>
                    <td><?= e($cfg['created_at']) ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="seb_config.php?config_id=<?= (int)$cfg['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <a href="seb_download.php?id=<?= (int)$cfg['id'] ?>" class="btn btn-sm btn-success">Download</a>
                            <form method="POST" action="seb_delete.php" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$cfg['id'] ?>">
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







