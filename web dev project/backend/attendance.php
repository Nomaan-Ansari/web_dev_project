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

$action = $_GET['action'] ?? '';

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

// 2. JSON API: Get Attendance Sheet for Specific Date & Class
if ($action === 'get_sheet') {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    $class_name = trim($_GET['class'] ?? '');
    $date       = trim($_GET['date'] ?? date('Y-m-d'));

    if (empty($class_name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Class name is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT s.id, s.roll_number, s.full_name, s.class_name, 
                   a.status, a.remarks 
            FROM students s 
            LEFT JOIN attendance a ON s.id = a.student_id AND a.date = ? 
            WHERE s.class_name = ? 
            ORDER BY s.roll_number ASC
        ");
        $stmt->execute([$date, $class_name]);
        $sheet = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => [
                'date'     => $date,
                'class'    => $class_name,
                'students' => $sheet
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. Form Submission: Save Attendance Records (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_faculty_permission(false);

    $post_date  = trim($_POST['attendance_date'] ?? date('Y-m-d'));
    $post_class = trim($_POST['class_name'] ?? '');
    $records    = $_POST['attendance'] ?? [];
    $remarks    = $_POST['remarks'] ?? [];

    if (!empty($records) && !empty($post_class)) {
        try {
            $pdo->beginTransaction();

            $save_stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, date, status, remarks) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    remarks = VALUES(remarks)
            ");

            foreach ($records as $student_id => $status) {
                $status_val = ($status === 'Absent') ? 'Absent' : 'Present';
                $remark_val = trim($remarks[$student_id] ?? '');

                $save_stmt->execute([
                    (int)$student_id,
                    $post_date,
                    $status_val,
                    !empty($remark_val) ? $remark_val : null
                ]);
            }

            $pdo->commit();
            $msg = urlencode("Attendance for {$post_class} on " . date('d M Y', strtotime($post_date)) . " was saved successfully.");
            header("Location: ../faculty/attendance.html?date=" . urlencode($post_date) . "&class=" . urlencode($post_class) . "&success={$msg}");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $err = urlencode('Error saving attendance: ' . $e->getMessage());
            header("Location: ../faculty/attendance.html?date=" . urlencode($post_date) . "&class=" . urlencode($post_class) . "&error={$err}");
            exit;
        }
    } else {
        $err = urlencode('No student records were submitted to save.');
        header("Location: ../faculty/attendance.html?date=" . urlencode($post_date) . "&class=" . urlencode($post_class) . "&error={$err}");
        exit;
    }
}

// Fallback redirect
header('Location: ../faculty/attendance.html');
exit;

