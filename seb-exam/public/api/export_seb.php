<?php

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/ApiToken.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';


$user = ApiToken::requireAuth();

$configId = validate_int($_GET['config_id'] ?? 0, 1);
if (!$configId) {
    header('Content-Type: application/json');
    http_response_code(400);
    die(json_encode(['error' => 'Missing or invalid config_id parameter.']));
}

$config = SebConfig::findById($configId);
if (!$config) {
    header('Content-Type: application/json');
    http_response_code(404);
    die(json_encode(['error' => 'SEB config not found.']));
}

if (!$config['xml_path'] || !file_exists($config['xml_path'])) {
    header('Content-Type: application/json');
    http_response_code(404);
    die(json_encode(['error' => 'XML file not found. Config may need to be regenerated.']));
}

$xmlContent = file_get_contents($config['xml_path']);
header('Content-Type: application/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="seb_config_' . $configId . '.seb"');
echo $xmlContent;






