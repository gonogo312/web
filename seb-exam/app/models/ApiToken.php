<?php

require_once __DIR__ . '/../config/db.php';

class ApiToken {
    
    public static function create(int $userId, string $label = 'API Token', ?string $expiresAt = null): array {
        $token = bin2hex(random_bytes(32)); 
        $tokenHash = hash('sha256', $token);

        $stmt = db()->prepare(
            'INSERT INTO api_tokens (user_id, token_hash, label, expires_at)
             VALUES (:uid, :hash, :label, :expires)'
        );
        $stmt->execute([
            ':uid'     => $userId,
            ':hash'    => $tokenHash,
            ':label'   => $label,
            ':expires' => $expiresAt,
        ]);

        return [
            'id'    => (int) db()->lastInsertId(),
            'token' => $token, 
        ];
    }

    
    public static function validateToken(string $token): ?array {
        $tokenHash = hash('sha256', $token);

        $stmt = db()->prepare(
            'SELECT t.*, u.id as user_id, u.username, u.role, u.full_name
             FROM api_tokens t
             JOIN users u ON t.user_id = u.id
             WHERE t.token_hash = :hash
               AND (t.expires_at IS NULL OR t.expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByUser(int $userId): array {
        $stmt = db()->prepare(
            'SELECT id, label, expires_at, created_at FROM api_tokens
             WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM api_tokens WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function getBearerToken(): ?string {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        }
        
        if (empty($headers)) {
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }

        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return trim($matches[1]);
        }

        
        return $_GET['api_token'] ?? null;
    }

    
    public static function requireAuth(): array {
        $token = self::getBearerToken();
        if (!$token) {
            http_response_code(401);
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Missing API token. Provide via Authorization: Bearer <token> header or ?api_token= parameter.']));
        }

        $user = self::validateToken($token);
        if (!$user) {
            http_response_code(401);
            header('Content-Type: application/json');
            die(json_encode(['error' => 'Invalid or expired API token.']));
        }

        return $user;
    }
}




