<?php

require_once __DIR__ . '/../app/lib/auth.php';
logout();
header('Location: ' . BASE_URL . '/login.php');
exit;






