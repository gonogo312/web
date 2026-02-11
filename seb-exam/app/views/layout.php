<?php

if (!isset($pageTitle)) $pageTitle = APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/styles.css">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<main class="container">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?= e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?= $bodyContent ?? '' ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
<script src="<?= e(BASE_URL) ?>/assets/app.js"></script>
</body>
</html>




