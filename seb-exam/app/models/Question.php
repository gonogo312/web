<?php

require_once __DIR__ . '/../config/db.php';

class Question {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM questions WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function create(int $examId, string $type, string $questionText,
                                  ?string $optionsJson, string $correctAnswer,
                                  float $points, int $sortOrder): int {
        $stmt = db()->prepare(
            'INSERT INTO questions (exam_id, type, question_text, options_json, correct_answer, points, sort_order)
             VALUES (:eid, :type, :text, :opts, :answer, :pts, :sort)'
        );
        $stmt->execute([
            ':eid'    => $examId,
            ':type'   => $type,
            ':text'   => $questionText,
            ':opts'   => $optionsJson,
            ':answer' => $correctAnswer,
            ':pts'    => $points,
            ':sort'   => $sortOrder,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function update(int $id, string $type, string $questionText,
                                  ?string $optionsJson, string $correctAnswer,
                                  float $points, int $sortOrder): void {
        $stmt = db()->prepare(
            'UPDATE questions SET type = :type, question_text = :text, options_json = :opts,
             correct_answer = :answer, points = :pts, sort_order = :sort
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'     => $id,
            ':type'   => $type,
            ':text'   => $questionText,
            ':opts'   => $optionsJson,
            ':answer' => $correctAnswer,
            ':pts'    => $points,
            ':sort'   => $sortOrder,
        ]);
    }

    
    public static function delete(int $id): void {
        $stmt = db()->prepare('DELETE FROM questions WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    
    public static function deleteByExam(int $examId): void {
        $stmt = db()->prepare('DELETE FROM questions WHERE exam_id = :eid');
        $stmt->execute([':eid' => $examId]);
    }
}




