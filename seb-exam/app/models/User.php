<?php

require_once __DIR__ . '/../config/db.php';

class User {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByUsername(string $username): ?array {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByRole(string $role): array {
        $stmt = db()->prepare('SELECT id, username, email, full_name, created_at FROM users WHERE role = :role ORDER BY full_name');
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    
    public static function getStudents(): array {
        return self::findByRole('student');
    }

    
    public static function create(string $username, string $email, string $password, string $role, string $fullName = ''): int {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (username, email, password_hash, role, full_name)
             VALUES (:username, :email, :hash, :role, :name)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':hash'     => $hash,
            ':role'     => $role,
            ':name'     => $fullName,
        ]);
        return (int) db()->lastInsertId();
    }
}







