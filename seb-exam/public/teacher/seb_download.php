<?php

require_once __DIR__ . '/../../app/lib/auth.php';
require_once __DIR__ . '/../../app/lib/validation.php';
require_once __DIR__ . '/../../app/models/SebConfig.php';
init_session();
require_role('teacher');

$configId = validate_int($_GET['id'] ?? 0, 1);
if (!$configId) { http_response_code(404); die('Config not found.'); }

$config = SebConfig::findById($configId);
if (!$config || !SebConfig::isOwner($configId, current_user_id())) {
    http_response_code(403); die('Access denied.');
}

if (!$config['xml_path'] || !file_exists($config['xml_path'])) {
    http_response_code(404); die('XML file not found. Please regenerate the config.');
}

$filename = 'seb_config_' . $configId . '.seb';
$xmlContent = file_get_contents($config['xml_path']);

header('Content-Type: application/xml');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($xmlContent));
header('Cache-Control: no-cache, no-store, must-revalidate');
echo $xmlContent;
exit;






