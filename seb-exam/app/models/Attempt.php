<?php

require_once __DIR__ . '/../config/db.php';

class Attempt {
    
    public static function findById(int $id): ?array {
        $stmt = db()->prepare('SELECT * FROM attempts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function start(int $studentId, int $examId, float $maxScore): int {
        $stmt = db()->prepare(
            'INSERT INTO attempts (student_id, exam_id, started_at, max_score, is_submitted)
             VALUES (:sid, :eid, NOW(), :max, 0)'
        );
        $stmt->execute([
            ':sid' => $studentId,
            ':eid' => $examId,
            ':max' => $maxScore,
        ]);
        return (int) db()->lastInsertId();
    }

    
    public static function submit(int $attemptId, float $score): void {
        $stmt = db()->prepare(
            'UPDATE attempts SET finished_at = NOW(), score = :score, is_submitted = 1
             WHERE id = :id'
        );
        $stmt->execute([':id' => $attemptId, ':score' => $score]);
    }

    
    public static function saveAnswer(int $attemptId, int $questionId, string $answer,
                                      bool $isCorrect, float $pointsAwarded): void {
        $stmt = db()->prepare(
            'INSERT INTO attempt_answers (attempt_id, question_id, student_answer, is_correct, points_awarded)
             VALUES (:aid, :qid, :ans, :correct, :pts)'
        );
        $stmt->execute([
            ':aid'     => $attemptId,
            ':qid'     => $questionId,
            ':ans'     => $answer,
            ':correct' => $isCorrect ? 1 : 0,
            ':pts'     => $pointsAwarded,
        ]);
    }

    
    public static function getAnswers(int $attemptId): array {
        $stmt = db()->prepare(
            'SELECT aa.*, q.question_text, q.correct_answer, q.type, q.options_json, q.points
             FROM attempt_answers aa
             JOIN questions q ON aa.question_id = q.id
             WHERE aa.attempt_id = :aid
             ORDER BY q.sort_order, q.id'
        );
        $stmt->execute([':aid' => $attemptId]);
        return $stmt->fetchAll();
    }

    
    public static function findByStudentExam(int $studentId, int $examId): array {
        $stmt = db()->prepare(
            'SELECT * FROM attempts
             WHERE student_id = :sid AND exam_id = :eid
             ORDER BY started_at DESC'
        );
        $stmt->execute([':sid' => $studentId, ':eid' => $examId]);
        return $stmt->fetchAll();
    }

    
    public static function findByExam(int $examId): array {
        $stmt = db()->prepare(
            'SELECT a.*, u.full_name as student_name, u.username
             FROM attempts a
             JOIN users u ON a.student_id = u.id
             WHERE a.exam_id = :eid AND a.is_submitted = 1
             ORDER BY a.finished_at DESC'
        );
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll();
    }

    
    public static function getActiveAttempt(int $studentId, int $examId): ?array {
        $stmt = db()->prepare(
            'SELECT * FROM attempts
             WHERE student_id = :sid AND exam_id = :eid AND is_submitted = 0
             ORDER BY started_at DESC LIMIT 1'
        );
        $stmt->execute([':sid' => $studentId, ':eid' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    
    public static function findByStudent(int $studentId): array {
        $stmt = db()->prepare(
            'SELECT a.*, e.title as exam_title, e.passing_score
             FROM attempts a
             JOIN exams e ON a.exam_id = e.id
             WHERE a.student_id = :sid AND a.is_submitted = 1
             ORDER BY a.finished_at DESC'
        );
        $stmt->execute([':sid' => $studentId]);
        return $stmt->fetchAll();
    }
}







