<?php

require_once __DIR__ . '/../config/db.php';

class GameNode {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM game_nodes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByKey(int $gameId, string $nodeKey): ?array {
        $stmt = db()->prepare('SELECT * FROM game_nodes WHERE game_id = :gid AND node_key = :key LIMIT 1');
        $stmt->execute([':gid' => $gameId, ':key' => $nodeKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function create(int $gameId, string $nodeKey, string $title,
                                  string $description, bool $isEndNode, int $sortOrder): int {
        $stmt = db()->prepare(
            'INSERT INTO game_nodes (game_id, node_key, title, description, is_end_node, sort_order)
             VALUES (:gid, :key, :title, :desc, :end, :sort)'
        );
        $stmt->execute([
            ':gid'   => $gameId,
            ':key'   => $nodeKey,
            ':title' => $title,
            ':desc'  => $description,
            ':end'   => $isEndNode ? 1 : 0,
            ':sort'  => $sortOrder,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM game_nodes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function deleteByGame(int $gameId): void {
        $stmt = db()->prepare('DELETE FROM game_nodes WHERE game_id = :gid');
        $stmt->execute([':gid' => $gameId]);
    }
}






