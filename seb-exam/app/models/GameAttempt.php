<?php

require_once __DIR__ . '/../config/db.php';

class GameAttempt {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM game_attempts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function getActiveAttempt(int $studentId, int $gameId): ?array {
        $stmt = db()->prepare(
            'SELECT * FROM game_attempts
             WHERE student_id = :sid AND game_id = :gid AND is_completed = 0
             ORDER BY started_at DESC LIMIT 1'
        );
        $stmt->execute([':sid' => $studentId, ':gid' => $gameId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function start(int $studentId, int $gameId): int {
        $stmt = db()->prepare(
            'INSERT INTO game_attempts (student_id, game_id, started_at, is_completed)
             VALUES (:sid, :gid, NOW(), 0)'
        );
        $stmt->execute([':sid' => $studentId, ':gid' => $gameId]);
        return (int) db()->lastInsertId();
    }

    
    public static function complete(int $attemptId): void {
        $stmt = db()->prepare(
            'UPDATE game_attempts SET finished_at = NOW(), is_completed = 1
             WHERE id = :id'
        );
        $stmt->execute([':id' => $attemptId]);
    }

    
    public static function logChoice(int $attemptId, int $nodeId, ?int $choiceId): void {
        $stmt = db()->prepare(
            'INSERT INTO game_choice_logs (game_attempt_id, node_id, choice_id)
             VALUES (:aid, :nid, :cid)'
        );
        $stmt->execute([
            ':aid' => $attemptId,
            ':nid' => $nodeId,
            ':cid' => $choiceId,
        ]);
    }

    
    public static function getLogs(int $attemptId): array {
        $stmt = db()->prepare(
            'SELECT gcl.*, gn.title as node_title, gn.node_key, gc.choice_text
             FROM game_choice_logs gcl
             JOIN game_nodes gn ON gcl.node_id = gn.id
             LEFT JOIN game_choices gc ON gcl.choice_id = gc.id
             WHERE gcl.game_attempt_id = :aid
             ORDER BY gcl.created_at, gcl.id'
        );
        $stmt->execute([':aid' => $attemptId]);
        return $stmt->fetchAll();
    }

    
    public static function findByTeacher(int $teacherId, ?int $studentId = null, ?int $gameId = null): array {
        $sql = 'SELECT ga.*, g.title as game_title, u.full_name as student_name, u.username
                FROM game_attempts ga
                JOIN games g ON ga.game_id = g.id
                JOIN users u ON ga.student_id = u.id
                WHERE g.teacher_id = :tid AND ga.is_completed = 1';
        $params = [':tid' => $teacherId];
        if ($studentId) {
            $sql .= ' AND ga.student_id = :sid';
            $params[':sid'] = $studentId;
        }
        if ($gameId) {
            $sql .= ' AND ga.game_id = :gid';
            $params[':gid'] = $gameId;
        }
        $sql .= ' ORDER BY ga.finished_at DESC';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}



