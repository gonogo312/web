<?php

require_once __DIR__ . '/../config/db.php';

class Exam {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM exams WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByTeacher(int $teacherId): array {
        $stmt = db()->prepare('SELECT * FROM exams WHERE teacher_id = :tid ORDER BY created_at DESC');
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll();
    }

    
    public static function findPublished(): array {
        $stmt = db()->prepare('SELECT * FROM exams WHERE is_published = 1 ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    
    public static function create(int $teacherId, string $title, string $description,
                                  int $timeLimit, float $passingScore, ?string $accessCode,
                                  bool $isPublished): int {
        $stmt = db()->prepare(
            'INSERT INTO exams (teacher_id, title, description, time_limit_min, passing_score, access_code, is_published)
             VALUES (:tid, :title, :desc, :time, :pass, :code, :pub)'
        );
        $stmt->execute([
            ':tid'   => $teacherId,
            ':title' => $title,
            ':desc'  => $description,
            ':time'  => $timeLimit,
            ':pass'  => $passingScore,
            ':code'  => $accessCode,
            ':pub'   => $isPublished ? 1 : 0,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function update(int $id, string $title, string $description,
                                  int $timeLimit, float $passingScore, ?string $accessCode,
                                  bool $isPublished): void {
        $stmt = db()->prepare(
            'UPDATE exams SET title = :title, description = :desc, time_limit_min = :time,
             passing_score = :pass, access_code = :code, is_published = :pub
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'    => $id,
            ':title' => $title,
            ':desc'  => $description,
            ':time'  => $timeLimit,
            ':pass'  => $passingScore,
            ':code'  => $accessCode,
            ':pub'   => $isPublished ? 1 : 0,
        ]);
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM exams WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function getQuestions(int $examId): array {
        $stmt = db()->prepare('SELECT * FROM questions WHERE exam_id = :eid ORDER BY sort_order, id');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll();
    }

    
    public static function getQuestionStats(int $examId): array {
        $stmt = db()->prepare(
            'SELECT COUNT(*) as count, COALESCE(SUM(points), 0) as total_points
             FROM questions WHERE exam_id = :eid'
        );
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetch();
    }

    
    public static function isOwner(int $examId, int $teacherId): bool {
        $stmt = db()->prepare('SELECT COUNT(*) as cnt FROM exams WHERE id = :eid AND teacher_id = :tid');
        $stmt->execute([':eid' => $examId, ':tid' => $teacherId]);
        return $stmt->fetch()['cnt'] > 0;
    }
}







