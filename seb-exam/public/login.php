<?php

require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/csrf.php';
init_session();


if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (is_login_locked($username)) {
        $remaining = get_lockout_remaining();
        $mins = ceil($remaining / 60);
        $error = "Too many failed attempts. Try again in {$mins} minute(s).";
    } else {
        $user = authenticate($username, $password);
        if ($user) {
            header('Location: ' . BASE_URL . '/');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$pageTitle = 'Login';
ob_start();
?>
<div class="login-wrapper">
    <div class="login-box">
        <h1>Login</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
        </form>
        <p class="text-center mt-2" style="font-size:0.9rem;">
            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register here</a>
        </p>
        <p class="text-muted text-center mt-1" style="font-size:0.8rem;">
            Demo: teacher1 / teacher123 &bull; student1 / student123
        </p>
    </div>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../app/views/layout.php';


