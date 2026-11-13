<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verify Faculty Authorization
function check_faculty_permission($is_json = false) {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'faculty') {
        if ($is_json) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized. Faculty access required.']);
            exit;
        } else {
            header('Location: ../login.html?error=' . urlencode('Access denied. Please log in as Faculty.'));
            exit;
        }
    }
}

$action     = $_GET['action'] ?? '';
$assign_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faculty_id = (int)($_SESSION['profile_id'] ?? 0);

// 1. JSON API: Get Distinct Classes
if ($action === 'get_classes') {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $classes = $pdo->query("SELECT DISTINCT class_name FROM students ORDER BY class_name ASC")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($classes)) {
            $classes = ['Class 10-A', 'Class 10-B', 'Class 9-A', 'Class 9-B'];
        }
        echo json_encode(['success' => true, 'data' => $classes]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. JSON API: Get Faculty's Assignments List
if ($action === 'list') {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE faculty_id = ? ORDER BY due_date DESC, id DESC");
        $stmt->execute([$faculty_id]);
        $assignments = $stmt->fetchAll();

        echo json_encode(['success' => true, 'data' => $assignments]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. DELETE Assignment
if ($action === 'delete' && $assign_id > 0) {
    check_faculty_permission(false);

    try {
        // Enforce ownership: faculty can only delete their own assignments
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND faculty_id = ?");
        $stmt->execute([$assign_id, $faculty_id]);

        if ($stmt->rowCount() > 0) {
            $msg = urlencode('Assignment was deleted successfully.');
            header("Location: ../faculty/assignments.html?success={$msg}");
        } else {
            $err = urlencode('Assignment not found or permission denied.');
            header("Location: ../faculty/assignments.html?error={$err}");
        }
        exit;
    } catch (PDOException $e) {
        $err = urlencode('Error deleting assignment: ' . $e->getMessage());
        header("Location: ../faculty/assignments.html?error={$err}");
        exit;
    }
}

// 4. Form Submission: Create Assignment (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_faculty_permission(false);

    $title       = trim($_POST['title'] ?? '');
    $subject     = trim($_POST['subject'] ?? '');
    $class_name  = trim($_POST['class_name'] ?? '');
    $due_date    = trim($_POST['due_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($subject) || empty($class_name) || empty($due_date) || empty($description)) {
        $err = urlencode('Please fill out all assignment fields (Title, Subject, Class, Due Date, and Description).');
        header("Location: ../faculty/assignments.html?error={$err}");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO assignments (faculty_id, title, subject, class_name, description, due_date) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$faculty_id, $title, $subject, $class_name, $description, $due_date]);

        $msg = urlencode("Assignment '{$title}' posted successfully for {$class_name}.");
        header("Location: ../faculty/assignments.html?success={$msg}");
        exit;
    } catch (PDOException $e) {
        $err = urlencode('Error creating assignment: ' . $e->getMessage());
        header("Location: ../faculty/assignments.html?error={$err}");
        exit;
    }
}

// Fallback redirect
header('Location: ../faculty/assignments.html');
exit;

