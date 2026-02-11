<?php


require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';


function get_client_ip(): string {
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}


function record_login_attempt(string $username, bool $success): void {
    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO login_attempts (username, ip_address, attempted_at, success)
         VALUES (:username, :ip, NOW(), :success)'
    );
    $stmt->execute([
        ':username' => $username,
        ':ip'       => get_client_ip(),
        ':success'  => $success ? 1 : 0,
    ]);
}


function is_login_locked(string $username): bool {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as cnt FROM login_attempts
         WHERE ip_address = :ip
           AND success = 0
           AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
    );
    $stmt->execute([
        ':ip'      => get_client_ip(),
        ':minutes' => LOGIN_LOCKOUT_MINUTES,
    ]);
    $row = $stmt->fetch();
    return ($row['cnt'] ?? 0) >= MAX_LOGIN_ATTEMPTS;
}


function get_lockout_remaining(): int {
    $pdo = db();
    $stmt = $pdo->prepare(
        'SELECT MAX(attempted_at) as last_attempt FROM login_attempts
         WHERE ip_address = :ip AND success = 0
           AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)'
    );
    $stmt->execute([
        ':ip'      => get_client_ip(),
        ':minutes' => LOGIN_LOCKOUT_MINUTES,
    ]);
    $row = $stmt->fetch();
    if (!$row || !$row['last_attempt']) {
        return 0;
    }
    $lastAttempt = strtotime($row['last_attempt']);
    $unlockTime  = $lastAttempt + (LOGIN_LOCKOUT_MINUTES * 60);
    $remaining   = $unlockTime - time();
    return max(0, $remaining);
}


function cleanup_login_attempts(): void {
    $pdo = db();
    $pdo->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
}


function generate_access_code(int $length = 8): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; 
    $code  = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}


function generate_api_token(): string {
    return bin2hex(random_bytes(32));
}







