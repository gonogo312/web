<?php

require_once __DIR__ . '/../config/db.php';

class GameChoice {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM game_choices WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function create(int $nodeId, string $choiceText, ?int $targetNodeId, int $sortOrder): int {
        $stmt = db()->prepare(
            'INSERT INTO game_choices (node_id, choice_text, target_node_id, sort_order)
             VALUES (:nid, :text, :tid, :sort)'
        );
        $stmt->execute([
            ':nid'  => $nodeId,
            ':text' => $choiceText,
            ':tid'  => $targetNodeId,
            ':sort' => $sortOrder,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM game_choices WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function deleteByNode(int $nodeId): void {
        $stmt = db()->prepare('DELETE FROM game_choices WHERE node_id = :nid');
        $stmt->execute([':nid' => $nodeId]);
    }
}




