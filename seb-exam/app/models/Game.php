<?php

require_once __DIR__ . '/../config/db.php';

class Game {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM games WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByTeacher(int $teacherId): array {
        $stmt = db()->prepare('SELECT * FROM games WHERE teacher_id = :tid ORDER BY created_at DESC');
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll();
    }

    
    public static function findPublished(): array {
        $stmt = db()->prepare('SELECT * FROM games WHERE is_published = 1 ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    
    public static function create(int $teacherId, string $title, string $description,
                                  ?string $accessCode, bool $isPublished): int {
        $stmt = db()->prepare(
            'INSERT INTO games (teacher_id, title, description, access_code, is_published)
             VALUES (:tid, :title, :desc, :code, :pub)'
        );
        $stmt->execute([
            ':tid'   => $teacherId,
            ':title' => $title,
            ':desc'  => $description,
            ':code'  => $accessCode,
            ':pub'   => $isPublished ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function update(int $id, string $title, string $description,
                                  ?string $accessCode, bool $isPublished): void {
        $stmt = db()->prepare(
            'UPDATE games SET title = :title, description = :desc,
             access_code = :code, is_published = :pub WHERE id = :id'
        );
        $stmt->execute([
            ':id'    => $id,
            ':title' => $title,
            ':desc'  => $description,
            ':code'  => $accessCode,
            ':pub'   => $isPublished ? 1 : 0,
        ]);
    }

    
    public static function setStartNode(int $gameId, int $nodeId): void {
        $stmt = db()->prepare('UPDATE games SET start_node_id = :nid WHERE id = :gid');
        $stmt->execute([':nid' => $nodeId, ':gid' => $gameId]);
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM games WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function isOwner(int $gameId, int $teacherId): bool {
        $stmt = db()->prepare('SELECT COUNT(*) as cnt FROM games WHERE id = :gid AND teacher_id = :tid');
        $stmt->execute([':gid' => $gameId, ':tid' => $teacherId]);
        return $stmt->fetch()['cnt'] > 0;
    }

    
    public static function getNodes(int $gameId): array {
        $stmt = db()->prepare('SELECT * FROM game_nodes WHERE game_id = :gid ORDER BY sort_order, id');
        $stmt->execute([':gid' => $gameId]);
        return $stmt->fetchAll();
    }

    
    public static function getChoicesForNode(int $nodeId): array {
        $stmt = db()->prepare('SELECT * FROM game_choices WHERE node_id = :nid ORDER BY sort_order, id');
        $stmt->execute([':nid' => $nodeId]);
        return $stmt->fetchAll();
    }

    
    public static function getFullStructure(int $gameId): array {
        $nodes = self::getNodes($gameId);
        foreach ($nodes as &$node) {
            $node['choices'] = self::getChoicesForNode($node['id']);
        }
        return $nodes;
    }

    
    public static function exportJson(int $gameId): ?array {
        $game = self::findById($gameId);
        if (!$game) return null;

        $nodes = self::getFullStructure($gameId);
        $nodesExport = [];
        foreach ($nodes as $node) {
            $choicesExport = [];
            foreach ($node['choices'] as $choice) {
                
                $targetKey = null;
                if ($choice['target_node_id']) {
                    foreach ($nodes as $n) {
                        if ($n['id'] == $choice['target_node_id']) {
                            $targetKey = $n['node_key'];
                            break;
                        }
                    }
                }
                $choicesExport[] = [
                    'choice_text'     => $choice['choice_text'],
                    'target_node_key' => $targetKey,
                ];
            }
            $nodesExport[] = [
                'node_key'    => $node['node_key'],
                'title'       => $node['title'],
                'description' => $node['description'],
                'is_end_node' => (bool) $node['is_end_node'],
                'choices'     => $choicesExport,
            ];
        }

        
        $startKey = null;
        foreach ($nodes as $n) {
            if ($n['id'] == $game['start_node_id']) {
                $startKey = $n['node_key'];
                break;
            }
        }

        return [
            'title'          => $game['title'],
            'description'    => $game['description'],
            'start_node_key' => $startKey,
            'nodes'          => $nodesExport,
        ];
    }

    
    public static function importFromJson(int $teacherId, array $data): int {
        $title = $data['title'] ?? 'Imported Game';
        $desc  = $data['description'] ?? '';
        $gameId = self::create($teacherId, $title, $desc, null, false);

        $nodeKeyToId = [];
        $sortOrder = 0;

        
        foreach ($data['nodes'] as $nodeData) {
            $sortOrder++;
            $nodeKey = $nodeData['node_key'] ?? 'node_' . $sortOrder;
            $stmt = db()->prepare(
                'INSERT INTO game_nodes (game_id, node_key, title, description, is_end_node, sort_order)
                 VALUES (:gid, :key, :title, :desc, :end, :sort)'
            );
            $stmt->execute([
                ':gid'   => $gameId,
                ':key'   => $nodeKey,
                ':title' => $nodeData['title'] ?? 'Untitled',
                ':desc'  => $nodeData['description'] ?? '',
                ':end'   => !empty($nodeData['is_end_node']) ? 1 : 0,
                ':sort'  => $sortOrder,
            ]);
            $nodeKeyToId[$nodeKey] = (int) db()->lastInsertId();
        }

        
        $startKey = $data['start_node_key'] ?? ($data['nodes'][0]['node_key'] ?? null);
        if ($startKey && isset($nodeKeyToId[$startKey])) {
            self::setStartNode($gameId, $nodeKeyToId[$startKey]);
        } elseif (!empty($nodeKeyToId)) {
            self::setStartNode($gameId, reset($nodeKeyToId));
        }

        
        foreach ($data['nodes'] as $nodeData) {
            $nodeKey = $nodeData['node_key'] ?? '';
            $nodeId = $nodeKeyToId[$nodeKey] ?? null;
            if (!$nodeId || empty($nodeData['choices'])) continue;

            $cSort = 0;
            foreach ($nodeData['choices'] as $choiceData) {
                $cSort++;
                $targetNodeId = null;
                $targetKey = $choiceData['target_node_key'] ?? null;
                if ($targetKey && isset($nodeKeyToId[$targetKey])) {
                    $targetNodeId = $nodeKeyToId[$targetKey];
                }
                $stmt = db()->prepare(
                    'INSERT INTO game_choices (node_id, choice_text, target_node_id, sort_order)
                     VALUES (:nid, :text, :tid, :sort)'
                );
                $stmt->execute([
                    ':nid'  => $nodeId,
                    ':text' => $choiceData['choice_text'] ?? 'Continue',
                    ':tid'  => $targetNodeId,
                    ':sort' => $cSort,
                ]);
            }
        }

        return $gameId;
    }

    
    public static function importFromCsv(int $teacherId, string $csvContent): int {
        $lines = array_filter(explode("\n", $csvContent));
        $header = str_getcsv(array_shift($lines));
        $header = array_map('trim', $header);

        $nodesMap = [];
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < 2) continue;
            $data = array_combine($header, array_pad($row, count($header), ''));

            $nodeKey = trim($data['node_key'] ?? '');
            if (!$nodeKey) continue;

            if (!isset($nodesMap[$nodeKey])) {
                $nodesMap[$nodeKey] = [
                    'node_key'    => $nodeKey,
                    'title'       => $data['title'] ?? $nodeKey,
                    'description' => $data['description'] ?? '',
                    'is_end_node' => !empty($data['is_end_node']),
                    'choices'     => [],
                ];
            }

            if (!empty($data['choice_text'])) {
                $nodesMap[$nodeKey]['choices'][] = [
                    'choice_text'     => $data['choice_text'],
                    'target_node_key' => $data['target_node_key'] ?? null,
                ];
            }
        }

        $jsonData = [
            'title'          => 'CSV Import ' . date('Y-m-d H:i'),
            'description'    => 'Imported from CSV',
            'start_node_key' => array_key_first($nodesMap),
            'nodes'          => array_values($nodesMap),
        ];

        return self::importFromJson($teacherId, $jsonData);
    }
}







