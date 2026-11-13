<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verify Principal Authorization
function check_principal_permission($is_json = false) {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'principal') {
        if ($is_json) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Principal access required.']);
            exit;
        } else {
            header('Location: ../login.html?error=' . urlencode('Access denied. Please log in as Principal.'));
            exit;
        }
    }
}

$action = $_GET['action'] ?? '';
$faculty_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. JSON API: Get Faculty List
if ($action === 'list') {
    check_principal_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->query("
            SELECT f.*, u.email, u.username 
            FROM faculty f 
            JOIN users u ON f.user_id = u.id 
            ORDER BY f.full_name ASC
        ");
        $faculty = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $faculty]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. JSON API: Get Single Faculty for Editing
if ($action === 'get' && $faculty_id > 0) {
    check_principal_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->prepare("
            SELECT f.*, u.username, u.email 
            FROM faculty f 
            JOIN users u ON f.user_id = u.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$faculty_id]);
        $teacher = $stmt->fetch();

        if ($teacher) {
            echo json_encode(['success' => true, 'data' => $teacher]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Faculty member not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. DELETE Faculty Record
if ($action === 'delete' && $faculty_id > 0) {
    check_principal_permission(false);

    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM faculty WHERE id = ?");
        $stmt->execute([$faculty_id]);
        $fac = $stmt->fetch();

        if ($fac) {
            // Deleting the user cascades to faculty and assignments
            $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->execute([$fac['user_id']]);

            $msg = urlencode("Faculty member '{$fac['full_name']}' was successfully deleted.");
            header("Location: ../principal/faculty.html?success={$msg}");
            exit;
        } else {
            $err = urlencode('Faculty record not found.');
            header("Location: ../principal/faculty.html?error={$err}");
            exit;
        }
    } catch (PDOException $e) {
        $err = urlencode('Database error while deleting: ' . $e->getMessage());
        header("Location: ../principal/faculty.html?error={$err}");
        exit;
    }
}

// 4. Form Submission: ADD or EDIT Faculty (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_principal_permission(false);

    $faculty_id             = isset($_POST['faculty_id']) ? (int)$_POST['faculty_id'] : 0;
    $full_name              = trim($_POST['full_name'] ?? '');
    $employee_id            = trim($_POST['employee_id'] ?? '');
    $subject_specialization = trim($_POST['subject_specialization'] ?? '');
    $qualification          = trim($_POST['qualification'] ?? '');
    $phone                  = trim($_POST['phone'] ?? '');
    $email                  = trim($_POST['email'] ?? '');
    $username               = trim($_POST['username'] ?? '');
    $address                = trim($_POST['address'] ?? '');
    $password               = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($full_name) || empty($employee_id) || empty($subject_specialization) || empty($email) || empty($username)) {
        $err = urlencode('Please fill out all required fields (Name, Employee ID, Subject, Email, Username).');
        $redirect_url = ($faculty_id > 0) ? "../principal/faculty.html?action=edit&id={$faculty_id}&error={$err}" : "../principal/faculty.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }

    try {
        if ($faculty_id > 0) {
            //  UPDATE EXISTING FACULTY 
            $stmt = $pdo->prepare("SELECT user_id FROM faculty WHERE id = ?");
            $stmt->execute([$faculty_id]);
            $existing_user_id = $stmt->fetchColumn();

            if (!$existing_user_id) {
                $err = urlencode('Faculty member not found.');
                header("Location: ../principal/faculty.html?error={$err}");
                exit;
            }

            // Check if username or email is taken by another account
            $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check->execute([$username, $email, $existing_user_id]);
            if ($check->fetch()) {
                $err = urlencode('The username or email is already taken by another account.');
                header("Location: ../principal/faculty.html?action=edit&id={$faculty_id}&error={$err}");
                exit;
            }

            // Check if employee ID is taken by another faculty member
            $emp_check = $pdo->prepare("SELECT id FROM faculty WHERE employee_id = ? AND id != ?");
            $emp_check->execute([$employee_id, $faculty_id]);
            if ($emp_check->fetch()) {
                $err = urlencode('Another faculty member has this Employee ID.');
                header("Location: ../principal/faculty.html?action=edit&id={$faculty_id}&error={$err}");
                exit;
            }

            // Update user credentials
            if (!empty($password)) {
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $u_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $u_stmt->execute([$username, $email, $pass_hash, $existing_user_id]);
            } else {
                $u_stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $u_stmt->execute([$username, $email, $existing_user_id]);
            }

            // Update faculty profile
            $f_stmt = $pdo->prepare("
                UPDATE faculty 
                SET employee_id = ?, full_name = ?, subject_specialization = ?, 
                    qualification = ?, phone = ?, address = ? 
                WHERE id = ?
            ");
            $f_stmt->execute([
                $employee_id, $full_name, $subject_specialization, 
                $qualification, $phone, $address, 
                $faculty_id
            ]);

            $msg = urlencode("Faculty member '{$full_name}' profile was updated successfully.");
            header("Location: ../principal/faculty.html?success={$msg}");
            exit;

        } else {
            //  ADD NEW FACULTY 
            // Check for duplicate username or email
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $err = urlencode('A user with this username or email already exists.');
                header("Location: ../principal/faculty.html?error={$err}");
                exit;
            }

            // Check for duplicate employee ID
            $emp_check = $pdo->prepare("SELECT id FROM faculty WHERE employee_id = ?");
            $emp_check->execute([$employee_id]);
            if ($emp_check->fetch()) {
                $err = urlencode('A faculty member with this Employee ID already exists.');
                header("Location: ../principal/faculty.html?error={$err}");
                exit;
            }

            $plain_pass = !empty($password) ? $password : 'faculty123';
            $pass_hash  = password_hash($plain_pass, PASSWORD_DEFAULT);

            // Insert User
            $u_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'faculty')");
            $u_stmt->execute([$username, $email, $pass_hash]);
            $new_user_id = $pdo->lastInsertId();

            // Insert Faculty Profile
            $f_stmt = $pdo->prepare("
                INSERT INTO faculty (user_id, employee_id, full_name, subject_specialization, qualification, phone, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $f_stmt->execute([
                $new_user_id, $employee_id, $full_name, $subject_specialization, 
                $qualification, $phone, $address
            ]);

            $msg = urlencode("New faculty member '{$full_name}' registered successfully. Default password is '{$plain_pass}'.");
            header("Location: ../principal/faculty.html?success={$msg}");
            exit;
        }
    } catch (PDOException $e) {
        $err = urlencode('Database error: ' . $e->getMessage());
        $redirect_url = ($faculty_id > 0) ? "../principal/faculty.html?action=edit&id={$faculty_id}&error={$err}" : "../principal/faculty.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }
}

// Fallback redirect if accessed directly
header('Location: ../principal/faculty.html');
exit;

