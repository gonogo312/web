<?php


require_once __DIR__ . '/../config/db.php';


function predict_pass_probability(int $studentId, int $examId): array {
    $pdo = db();

    
    $stmt = $pdo->prepare('SELECT * FROM exams WHERE id = :eid');
    $stmt->execute([':eid' => $examId]);
    $exam = $stmt->fetch();

    if (!$exam) {
        return ['probability' => 50.0, 'features' => [], 'explanation' => 'Exam not found.'];
    }

    $passingScore = (float) $exam['passing_score'];
    $timeLimit = (int) $exam['time_limit_min'];

    
    $stmt = $pdo->prepare(
        'SELECT score, max_score, started_at, finished_at
         FROM attempts
         WHERE student_id = :sid AND exam_id = :eid AND is_submitted = 1
         ORDER BY finished_at DESC
         LIMIT 10'
    );
    $stmt->execute([':sid' => $studentId, ':eid' => $examId]);
    $attempts = $stmt->fetchAll();

    $attemptCount = count($attempts);

    if ($attemptCount === 0) {
        
        $stmt = $pdo->prepare(
            'SELECT AVG(score / NULLIF(max_score, 0)) as avg_ratio, COUNT(*) as cnt
             FROM attempts
             WHERE student_id = :sid AND is_submitted = 1'
        );
        $stmt->execute([':sid' => $studentId]);
        $global = $stmt->fetch();

        if ($global && $global['cnt'] > 0 && $global['avg_ratio'] !== null) {
            $probability = (float)$global['avg_ratio'] * 100;
        } else {
            $probability = 50.0; 
        }

        return [
            'probability' => round(min(100, max(0, $probability)), 1),
            'features' => [
                ['name' => 'Historical Data', 'value' => 'None for this exam', 'contribution' => 'Neutral'],
                ['name' => 'Overall Performance', 'value' =>
                    ($global['cnt'] > 0 ? round($global['avg_ratio'] * 100, 1) . '%' : 'No data'),
                 'contribution' => $global['cnt'] > 0 ? 'Based on other exams' : 'Default estimate'],
            ],
            'explanation' => 'No attempts for this specific exam. ' .
                ($global['cnt'] > 0 ? 'Estimate based on overall performance across all exams.' : 'No historical data available, using default 50%.'),
        ];
    }

    
    $scoreRatios = [];
    $timeRatios = [];

    foreach ($attempts as $att) {
        $maxScore = (float) $att['max_score'];
        if ($maxScore > 0) {
            $scoreRatios[] = (float)$att['score'] / $maxScore;
        }
        if ($timeLimit > 0 && $att['started_at'] && $att['finished_at']) {
            $usedTime = (strtotime($att['finished_at']) - strtotime($att['started_at'])) / 60.0;
            $timeRatios[] = min(2.0, $usedTime / $timeLimit); 
        }
    }

    
    $avgScoreRatio = count($scoreRatios) > 0 ? array_sum($scoreRatios) / count($scoreRatios) : 0.5;

    
    $f_attempts = min(1.0, $attemptCount / 5.0); 

    
    $avgTimeRatio = count($timeRatios) > 0 ? array_sum($timeRatios) / count($timeRatios) : 0.5;
    $f_time = max(0, 1.0 - $avgTimeRatio); 

    
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) as total,
                SUM(CASE WHEN (score / NULLIF(max_score, 0)) * 100 >= :pass THEN 1 ELSE 0 END) as passed
         FROM attempts
         WHERE exam_id = :eid AND is_submitted = 1'
    );
    $stmt->execute([':eid' => $examId, ':pass' => $passingScore]);
    $diffRow = $stmt->fetch();
    $globalPassRate = ($diffRow['total'] > 0) ? $diffRow['passed'] / $diffRow['total'] : 0.5;

    
    $trend = 0;
    if (count($scoreRatios) >= 2) {
        $recentScores = array_reverse($scoreRatios); 
        $n = count($recentScores);
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $recentScores[$i];
            $sumXY += $i * $recentScores[$i];
            $sumX2 += $i * $i;
        }
        $denom = $n * $sumX2 - $sumX * $sumX;
        if ($denom != 0) {
            $trend = ($n * $sumXY - $sumX * $sumY) / $denom;
        }
    }
    $f_trend = max(-1, min(1, $trend * 5)); 

    
    
    $w = [
        'avg_score'  => 3.0,   
        'attempts'   => 0.5,   
        'time'       => 0.8,   
        'difficulty' => 1.0,   
        'trend'      => 1.5,   
    ];
    $bias = -2.0; 

    
    $z = $bias
       + $w['avg_score']  * ($avgScoreRatio * 2 - 1)  
       + $w['attempts']   * ($f_attempts * 2 - 1)
       + $w['time']       * ($f_time * 2 - 1)
       + $w['difficulty'] * ($globalPassRate * 2 - 1)
       + $w['trend']      * $f_trend;

    
    $probability = 1.0 / (1.0 + exp(-$z));
    $probability = round($probability * 100, 1);
    $probability = min(99.0, max(1.0, $probability)); 

    
    $features = [
        [
            'name'         => 'Average Score',
            'value'        => round($avgScoreRatio * 100, 1) . '% (last ' . $attemptCount . ' attempts)',
            'raw'          => $avgScoreRatio,
            'contribution' => $avgScoreRatio >= 0.7 ? 'Positive' : ($avgScoreRatio >= 0.4 ? 'Neutral' : 'Negative'),
            'weight'       => $w['avg_score'],
        ],
        [
            'name'         => 'Practice Level',
            'value'        => $attemptCount . ' attempt(s)',
            'raw'          => $f_attempts,
            'contribution' => $attemptCount >= 3 ? 'Positive' : ($attemptCount >= 1 ? 'Neutral' : 'Negative'),
            'weight'       => $w['attempts'],
        ],
        [
            'name'         => 'Time Efficiency',
            'value'        => round($avgTimeRatio * 100) . '% of time limit used',
            'raw'          => $f_time,
            'contribution' => $avgTimeRatio <= 0.6 ? 'Positive' : ($avgTimeRatio <= 0.9 ? 'Neutral' : 'Negative'),
            'weight'       => $w['time'],
        ],
        [
            'name'         => 'Exam Difficulty',
            'value'        => round($globalPassRate * 100) . '% global pass rate',
            'raw'          => $globalPassRate,
            'contribution' => $globalPassRate >= 0.6 ? 'Positive' : ($globalPassRate >= 0.3 ? 'Neutral' : 'Negative'),
            'weight'       => $w['difficulty'],
        ],
        [
            'name'         => 'Score Trend',
            'value'        => $trend > 0.01 ? 'Improving' : ($trend < -0.01 ? 'Declining' : 'Stable'),
            'raw'          => $f_trend,
            'contribution' => $trend > 0.01 ? 'Positive' : ($trend < -0.01 ? 'Negative' : 'Neutral'),
            'weight'       => $w['trend'],
        ],
    ];

    $explanation = sprintf(
        'Based on %d past attempt(s): average score %.0f%%, %s trend, using %.0f%% of time limit. ' .
        'Global pass rate for this exam is %.0f%%.',
        $attemptCount,
        $avgScoreRatio * 100,
        $trend > 0.01 ? 'improving' : ($trend < -0.01 ? 'declining' : 'stable'),
        $avgTimeRatio * 100,
        $globalPassRate * 100
    );

    return [
        'probability'  => $probability,
        'features'     => $features,
        'explanation'  => $explanation,
    ];
}




