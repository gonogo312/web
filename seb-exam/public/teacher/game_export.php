<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/Game.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';
init_session();
require_role('teacher');

$gameId = validate_int($_GET['id'] ?? 0, 1);
if (!$gameId || !Game::isOwner($gameId, current_user_id())) {
    http_response_code(404); die('Game not found.');
}

$json = Game::exportJson($gameId);
if (!$json) {
    http_response_code(404); die('Game not found.');
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


$filename = 'game_' . $gameId . '_' . date('Ymd_His') . '.json';
header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;







