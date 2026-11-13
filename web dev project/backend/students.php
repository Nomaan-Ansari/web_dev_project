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
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. JSON API: Get Student List
if ($action === 'list') {
    check_principal_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->query("
            SELECT s.*, u.email, u.username 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.class_name ASC, s.roll_number ASC
        ");
        $students = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $students]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. JSON API: Get Single Student for Editing
if ($action === 'get' && $student_id > 0) {
    check_principal_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.username, u.email 
            FROM students s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if ($student) {
            echo json_encode(['success' => true, 'data' => $student]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Student record not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. DELETE Student Record
if ($action === 'delete' && $student_id > 0) {
    check_principal_permission(false);

    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $stu = $stmt->fetch();

        if ($stu) {
            // Deleting from users cascades to students, attendance, and marks
            $del_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->execute([$stu['user_id']]);

            $msg = urlencode("Student '{$stu['full_name']}' was successfully deleted.");
            header("Location: ../principal/students.html?success={$msg}");
            exit;
        } else {
            $err = urlencode('Student record not found.');
            header("Location: ../principal/students.html?error={$err}");
            exit;
        }
    } catch (PDOException $e) {
        $err = urlencode('Database error while deleting: ' . $e->getMessage());
        header("Location: ../principal/students.html?error={$err}");
        exit;
    }
}

// 4. Form Submission: ADD or EDIT Student (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_principal_permission(false);

    $student_id   = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $full_name    = trim($_POST['full_name'] ?? '');
    $roll_number  = trim($_POST['roll_number'] ?? '');
    $class_name   = trim($_POST['class_name'] ?? '');
    $gender       = trim($_POST['gender'] ?? 'Male');
    $dob          = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $phone        = trim($_POST['phone'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $parent_name  = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address      = trim($_POST['address'] ?? '');
    $password     = trim($_POST['password'] ?? '');

    // Basic validation
    if (empty($full_name) || empty($roll_number) || empty($class_name) || empty($email) || empty($username)) {
        $err = urlencode('Please fill out all required fields (Name, Roll No, Class, Email, Username).');
        $redirect_url = ($student_id > 0) ? "../principal/students.html?action=edit&id={$student_id}&error={$err}" : "../principal/students.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }

    try {
        if ($student_id > 0) {
            // UPDATE EXISTING STUDENT 
            $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $existing_user_id = $stmt->fetchColumn();

            if (!$existing_user_id) {
                $err = urlencode('Student record not found.');
                header("Location: ../principal/students.html?error={$err}");
                exit;
            }

            // Check if username or email is already taken by another account
            $check = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $check->execute([$username, $email, $existing_user_id]);
            if ($check->fetch()) {
                $err = urlencode('The username or email is already taken by another account.');
                header("Location: ../principal/students.html?action=edit&id={$student_id}&error={$err}");
                exit;
            }

            // Check if roll number is taken by another student
            $roll_check = $pdo->prepare("SELECT id FROM students WHERE roll_number = ? AND id != ?");
            $roll_check->execute([$roll_number, $student_id]);
            if ($roll_check->fetch()) {
                $err = urlencode('Another student is already registered with this roll number.');
                header("Location: ../principal/students.html?action=edit&id={$student_id}&error={$err}");
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

            // Update student profile
            $s_stmt = $pdo->prepare("
                UPDATE students 
                SET roll_number = ?, full_name = ?, class_name = ?, gender = ?, 
                    dob = ?, phone = ?, address = ?, parent_name = ?, parent_phone = ? 
                WHERE id = ?
            ");
            $s_stmt->execute([
                $roll_number, $full_name, $class_name, $gender, 
                $dob, $phone, $address, $parent_name, $parent_phone, 
                $student_id
            ]);

            $msg = urlencode("Student '{$full_name}' profile was updated successfully.");
            header("Location: ../principal/students.html?success={$msg}");
            exit;

        } else {
            //  ENROLL NEW STUDENT
            // Check for duplicate username or email
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $err = urlencode('A user with this username or email already exists.');
                header("Location: ../principal/students.html?error={$err}");
                exit;
            }

            // Check for duplicate roll number
            $roll_check = $pdo->prepare("SELECT id FROM students WHERE roll_number = ?");
            $roll_check->execute([$roll_number]);
            if ($roll_check->fetch()) {
                $err = urlencode('A student with this roll number already exists.');
                header("Location: ../principal/students.html?error={$err}");
                exit;
            }

            $plain_pass = !empty($password) ? $password : 'student123';
            $pass_hash  = password_hash($plain_pass, PASSWORD_DEFAULT);

            // Insert User
            $u_stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
            $u_stmt->execute([$username, $email, $pass_hash]);
            $new_user_id = $pdo->lastInsertId();

            // Insert Student Profile
            $s_stmt = $pdo->prepare("
                INSERT INTO students (user_id, roll_number, full_name, class_name, gender, dob, phone, address, parent_name, parent_phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $s_stmt->execute([
                $new_user_id, $roll_number, $full_name, $class_name, $gender, 
                $dob, $phone, $address, $parent_name, $parent_phone
            ]);

            $msg = urlencode("New student '{$full_name}' enrolled successfully. Default password is '{$plain_pass}'.");
            header("Location: ../principal/students.html?success={$msg}");
            exit;
        }
    } catch (PDOException $e) {
        $err = urlencode('Database error: ' . $e->getMessage());
        $redirect_url = ($student_id > 0) ? "../principal/students.html?action=edit&id={$student_id}&error={$err}" : "../principal/students.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }
}

// Fallback redirect if accessed directly
header('Location: ../principal/students.html');
exit;

