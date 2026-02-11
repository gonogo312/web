<?php

require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/csrf.php';
require_once __DIR__ . '/../app/lib/validation.php';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/User.php';
init_session();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$errors = [];
$username = '';
$email = '';
$fullName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (!validate_string($username, 3, 50)) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    } elseif (User::findByUsername($username)) {
        $errors[] = 'Username already exists.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Invalid email address.';
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        }
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    } elseif (!validate_string($fullName, 2, 100)) {
        $errors[] = 'Full name must be between 2 and 100 characters.';
    }

    if (empty($errors)) {
        try {
            User::create($username, $email, $password, 'student', $fullName);
            $_SESSION['flash_success'] = 'Registration successful! Please login.';
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Register';
ob_start();
?>
<div class="login-wrapper">
    <div class="login-box">
        <h1>Register</h1>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus
                       pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50"
                       title="3-50 characters, letters, numbers, and underscores only">
                <small class="text-muted">3-50 characters, letters, numbers, and underscores only</small>
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
            </div>
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" value="<?= e($fullName) ?>" required
                       minlength="2" maxlength="100">
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small class="text-muted">Minimum 6 characters</small>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%">Register</button>
        </form>
        <p class="text-center mt-2" style="font-size:0.9rem;">
            Already have an account? <a href="<?= BASE_URL ?>/login.php">Login here</a>
        </p>
        <p class="text-muted text-center mt-1" style="font-size:0.8rem;">
            Note: Only student accounts can be created. Teacher accounts are managed by administrators.
        </p>
    </div>
</div>
<?php
$bodyContent = ob_get_clean();
include __DIR__ . '/../app/views/layout.php';

