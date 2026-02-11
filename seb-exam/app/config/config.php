<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('APP_ROOT', dirname(__DIR__));
define('PROJECT_ROOT', dirname(APP_ROOT));

define('BASE_URL', '/seb-exam/public');

define('STORAGE_PATH', PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage');
define('SEB_CONFIGS_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'seb_configs');
define('GAME_EXPORTS_PATH', STORAGE_PATH . DIRECTORY_SEPARATOR . 'game_exports');

define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'SEB_EXAM_SESSION');

define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

define('APP_NAME', 'SEB Exam System');


