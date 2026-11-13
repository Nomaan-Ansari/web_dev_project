<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// If accessed via GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (is_logged_in()) {
        $role = $_SESSION['role'] ?? '';
        if ($role === 'principal') {
            header('Location: ../principal/dashboard.php');
        } elseif ($role === 'faculty') {
            header('Location: ../faculty/dashboard.php');
        } elseif ($role === 'student') {
            header('Location: ../student/dashboard.php');
        } else {
            header('Location: ../login.html');
        }
        exit;
    }
    header('Location: ../login.html');
    exit;
}

// Process POST Login Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if (empty($login_input) || empty($password)) {
        $error = urlencode('Please enter both username/email and password.');
        header("Location: ../login.html?error={$error}");
        exit;
    }

    try {
        // Find user by either username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        // Verify password hash
        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);

            // Store common session details
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            // Role-specific profile initialization
            if ($user['role'] === 'faculty') {
                $fac_stmt = $pdo->prepare("SELECT id, full_name, employee_id FROM faculty WHERE user_id = ?");
                $fac_stmt->execute([$user['id']]);
                $fac = $fac_stmt->fetch();

                $_SESSION['profile_id'] = $fac['id'] ?? null;
                $_SESSION['full_name']  = $fac['full_name'] ?? 'Faculty Member';
                $_SESSION['emp_id']     = $fac['employee_id'] ?? '';

                header('Location: ../faculty/dashboard.php');
                exit;

            } elseif ($user['role'] === 'student') {
                $stu_stmt = $pdo->prepare("SELECT id, full_name, roll_number, class_name FROM students WHERE user_id = ?");
                $stu_stmt->execute([$user['id']]);
                $stu = $stu_stmt->fetch();

                $_SESSION['profile_id']  = $stu['id'] ?? null;
                $_SESSION['full_name']   = $stu['full_name'] ?? 'Student';
                $_SESSION['roll_number'] = $stu['roll_number'] ?? '';
                $_SESSION['class_name']  = $stu['class_name'] ?? '';

                header('Location: ../student/dashboard.php');
                exit;

            } elseif ($user['role'] === 'principal') {
                $_SESSION['full_name'] = 'Principal Office';
                header('Location: ../principal/dashboard.php');
                exit;
            } else {
                header('Location: ../login.html');
                exit;
            }
        } else {
            $error = urlencode('Invalid username/email or password. Please try again.');
            header("Location: ../login.html?error={$error}");
            exit;
        }
    } catch (PDOException $e) {
        $error = urlencode('Database error: ' . $e->getMessage());
        header("Location: ../login.html?error={$error}");
        exit;
    }
}

