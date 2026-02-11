<?php

?>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= e(BASE_URL) ?>/" class="logo"><?= e(APP_NAME) ?></a>
        <nav class="main-nav">
            <?php if (is_logged_in()): ?>
                <?php if (current_user_role() === 'teacher'): ?>
                    <a href="<?= e(BASE_URL) ?>/teacher/">Dashboard</a>
                    <a href="<?= e(BASE_URL) ?>/teacher/exam_list.php">Exams</a>
                    <a href="<?= e(BASE_URL) ?>/teacher/game_list.php">Games</a>
                    <a href="<?= e(BASE_URL) ?>/teacher/seb_list.php">SEB Configs</a>
                    <a href="<?= e(BASE_URL) ?>/teacher/prediction.php">Predictions</a>
                <?php elseif (current_user_role() === 'student'): ?>
                    <a href="<?= e(BASE_URL) ?>/student/">Dashboard</a>
                <?php endif; ?>
                <span class="nav-user">
                    <?= e(current_user_name()) ?> (<?= e(current_user_role()) ?>)
                </span>
                <a href="<?= e(BASE_URL) ?>/logout.php" class="btn btn-sm btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?= e(BASE_URL) ?>/login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>




