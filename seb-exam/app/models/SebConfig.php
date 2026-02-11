<?php

require_once __DIR__ . '/../config/db.php';

class SebConfig {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM seb_configs WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByActivity(string $activityType, int $activityId): ?array {
        $stmt = db()->prepare(
            'SELECT * FROM seb_configs WHERE activity_type = :type AND activity_id = :aid
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([':type' => $activityType, ':aid' => $activityId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByTeacher(int $teacherId): array {
        $stmt = db()->prepare('SELECT * FROM seb_configs WHERE teacher_id = :tid ORDER BY created_at DESC');
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll();
    }

    
    public static function create(int $teacherId, string $activityType, int $activityId,
                                  string $title, array $settings, ?string $xmlPath): int {
        $stmt = db()->prepare(
            'INSERT INTO seb_configs (teacher_id, activity_type, activity_id, title, settings_json, xml_path)
             VALUES (:tid, :type, :aid, :title, :settings, :path)'
        );
        $stmt->execute([
            ':tid'      => $teacherId,
            ':type'     => $activityType,
            ':aid'      => $activityId,
            ':title'    => $title,
            ':settings' => json_encode($settings),
            ':path'     => $xmlPath,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function update(int $id, string $title, array $settings, ?string $xmlPath): void {
        $stmt = db()->prepare(
            'UPDATE seb_configs SET title = :title, settings_json = :settings, xml_path = :path
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'       => $id,
            ':title'    => $title,
            ':settings' => json_encode($settings),
            ':path'     => $xmlPath,
        ]);
    }

    
    public static function delete(int $id): void {
        $config = self::findById($id);
        
        if ($config && $config['xml_path'] && file_exists($config['xml_path'])) {
            unlink($config['xml_path']);
        }
        $stmt = db()->prepare('DELETE FROM seb_configs WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function isOwner(int $configId, int $teacherId): bool {
        $stmt = db()->prepare('SELECT COUNT(*) as cnt FROM seb_configs WHERE id = :cid AND teacher_id = :tid');
        $stmt->execute([':cid' => $configId, ':tid' => $teacherId]);
        return $stmt->fetch()['cnt'] > 0;
    }
}




