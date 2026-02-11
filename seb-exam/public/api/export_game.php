<?php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/ApiToken.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';

header('Content-Type: application/json; charset=utf-8');


$user = ApiToken::requireAuth();

$gameId = validate_int($_GET['id'] ?? 0, 1);
if (!$gameId) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing or invalid game id parameter.']));
}

$game = Game::findById($gameId);
if (!$game) {
    http_response_code(404);
    die(json_encode(['error' => 'Game not found.']));
}

$json = Game::exportJson($gameId);
if (!$json) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to export game.']));
}


$sebConfig = SebConfig::findByActivity('game', $gameId);
if ($sebConfig) {
    $json['seb_activity'] = [
        'seb_config_id' => (int) $sebConfig['id'],
        'seb_title'     => $sebConfig['title'],
        'seb_xml_url'   => BASE_URL . '/teacher/seb_download.php?id=' . $sebConfig['id'],
    ];
}

$json['exported_at'] = date('c');
$json['game_id']     = $gameId;

echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);







