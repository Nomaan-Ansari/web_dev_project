<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';

$logged_in = is_logged_in();
$user_data = null;

if ($logged_in) {
    $user_data = [
        'id'        => (int)($_SESSION['user_id'] ?? 0),
        'username'  => $_SESSION['username'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
    ];
}

echo json_encode([
    'logged_in' => $logged_in,
    'user'      => $user_data
]);
exit;

