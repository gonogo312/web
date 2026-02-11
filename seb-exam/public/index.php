<?php

require_once __DIR__ . '/../app/lib/auth.php';
init_session();

if (is_logged_in()) {
    $role = current_user_role();
    if ($role === 'teacher') {
        header('Location: ' . BASE_URL . '/teacher/');
    } elseif ($role === 'student') {
        header('Location: ' . BASE_URL . '/student/');
    }
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;






