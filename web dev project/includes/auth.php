<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'         => $_SESSION['user_id'] ?? null,
        'username'   => $_SESSION['username'] ?? '',
        'email'      => $_SESSION['email'] ?? '',
        'role'       => $_SESSION['role'] ?? '',
        'full_name'  => $_SESSION['full_name'] ?? 'User',
        'profile_id' => $_SESSION['profile_id'] ?? null,
    ];
}


function require_role($allowed_roles, $path_to_root = '') {
    if (is_string($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // 1. If not logged in at all, redirect to login page
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to access this page.');
        header('Location: ' . $path_to_root . 'login.php');
        exit;
    }

    $current_role = $_SESSION['role'] ?? '';

    // 2. If logged in but does not have the required role, redirect to own dashboard
    if (!in_array($current_role, $allowed_roles)) {
        set_flash('error', 'Access denied. You do not have permission to view that page.');
        
        if ($current_role === 'principal') {
            header('Location: ' . $path_to_root . 'principal/dashboard.php');
        } elseif ($current_role === 'faculty') {
            header('Location: ' . $path_to_root . 'faculty/dashboard.php');
        } elseif ($current_role === 'student') {
            header('Location: ' . $path_to_root . 'student/dashboard.php');
        } else {
            header('Location: ' . $path_to_root . 'login.php');
        }
        exit;
    }
}

function escape($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash() {
    $flash = get_flash();
    if ($flash) {
        $type = escape($flash['type']);
        $msg  = escape($flash['message']);
        $alert_class = ($type === 'success') ? 'alert-success' : 'alert-error';
        echo "<div class='alert {$alert_class}'>{$msg}</div>";
    }
}
