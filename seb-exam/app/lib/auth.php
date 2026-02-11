<?php


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/security.php';


function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false, 
            'httponly'  => true,
            'samesite'  => 'Lax',
        ]);
        session_start();
    }
}


function authenticate(string $username, string $password): array|false {
    
    if (is_login_locked($username)) {
        return false;
    }

    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        
        record_login_attempt($username, true);

        
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int) $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        return $user;
    }

    
    record_login_attempt($username, false);
    return false;
}


function logout(): void {
    init_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}


function is_logged_in(): bool {
    return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
}


function current_user_id(): int {
    return (int) ($_SESSION['user_id'] ?? 0);
}


function current_user_role(): string {
    return $_SESSION['role'] ?? '';
}


function current_user_name(): string {
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';
}


function require_login(): void {
    init_session();
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}


function require_role(string $role): void {
    require_login();
    if (current_user_role() !== $role) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body>';
        echo '<h1>403 - Access Denied</h1>';
        echo '<p>You do not have permission to access this page.</p>';
        echo '<p><a href="' . e(BASE_URL) . '/">Go to Dashboard</a></p>';
        echo '</body></html>';
        exit;
    }
}


function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}







