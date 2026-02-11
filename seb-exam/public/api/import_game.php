<?php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/ApiToken.php';
require_once __DIR__ . '/../../app/models/Game.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed. Use POST.']));
}


$user = ApiToken::requireAuth();


if ($user['role'] !== 'teacher') {
    http_response_code(403);
    die(json_encode(['error' => 'Only teachers can import games.']));
}


$rawBody = file_get_contents('php://input');
if (empty($rawBody)) {
    http_response_code(400);
    die(json_encode(['error' => 'Empty request body. Send JSON game data.']));
}

$data = validate_json($rawBody);
if ($data === false) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]));
}


$validationErrors = validate_game_json($data);
if (!empty($validationErrors)) {
    http_response_code(422);
    die(json_encode(['error' => 'Validation failed', 'details' => $validationErrors]));
}

try {
    $gameId = Game::importFromJson((int) $user['user_id'], $data);
    echo json_encode([
        'success' => true,
        'game_id' => $gameId,
        'message' => 'Game imported successfully.',
        'edit_url' => BASE_URL . '/teacher/game_edit.php?id=' . $gameId,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Import failed: ' . $e->getMessage()]);
}







