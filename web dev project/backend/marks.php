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

// Letter grade calculator
function calculate_grade($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C';
    if ($pct >= 50) return 'D';
    return 'F';
}

$action     = $_GET['action'] ?? '';
$mark_id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$faculty_id = (int)($_SESSION['profile_id'] ?? 0);

// 1. JSON API: Get Students List & Faculty Subject for Marks Form
if ($action === 'get_students') {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        // Fetch faculty's subject specialization
        $f_stmt = $pdo->prepare("SELECT subject_specialization FROM faculty WHERE id = ?");
        $f_stmt->execute([$faculty_id]);
        $subject = $f_stmt->fetchColumn() ?: 'General';

        // Fetch students ordered by class and roll
        $students = $pdo->query("SELECT id, roll_number, full_name, class_name FROM students ORDER BY class_name ASC, roll_number ASC")->fetchAll();

        echo json_encode([
            'success'         => true,
            'faculty_subject' => $subject,
            'students'        => $students
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 2. JSON API: Get All Recorded Marks
if ($action === 'list') {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->query("
            SELECT m.*, s.roll_number, s.full_name, s.class_name 
            FROM marks m 
            JOIN students s ON m.student_id = s.id 
            ORDER BY m.id DESC
        ");
        $records = $stmt->fetchAll();

        // Calculate percentage and letter grade for each entry
        foreach ($records as &$rec) {
            $obtained = (float)$rec['marks_obtained'];
            $max      = (float)$rec['max_marks'];
            $pct      = ($max > 0) ? round(($obtained / $max) * 100, 1) : 0;
            $rec['percentage'] = $pct;
            $rec['grade']      = calculate_grade($pct);
        }

        echo json_encode(['success' => true, 'data' => $records]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. JSON API: Get Single Mark Record for Editing
if ($action === 'get' && $mark_id > 0) {
    check_faculty_permission(true);
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->prepare("SELECT * FROM marks WHERE id = ?");
        $stmt->execute([$mark_id]);
        $mark = $stmt->fetch();

        if ($mark) {
            echo json_encode(['success' => true, 'data' => $mark]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Mark record not found.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 4. DELETE Mark Record
if ($action === 'delete' && $mark_id > 0) {
    check_faculty_permission(false);

    try {
        $stmt = $pdo->prepare("DELETE FROM marks WHERE id = ?");
        $stmt->execute([$mark_id]);

        $msg = urlencode('Mark record was deleted successfully.');
        header("Location: ../faculty/marks.html?success={$msg}");
        exit;
    } catch (PDOException $e) {
        $err = urlencode('Error deleting mark record: ' . $e->getMessage());
        header("Location: ../faculty/marks.html?error={$err}");
        exit;
    }
}

// 5. Form Submission: ADD or UPDATE Marks (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_faculty_permission(false);

    $mark_id        = isset($_POST['mark_id']) ? (int)$_POST['mark_id'] : 0;
    $student_id     = (int)($_POST['student_id'] ?? 0);
    $subject        = trim($_POST['subject'] ?? '');
    $exam_name      = trim($_POST['exam_name'] ?? '');
    $marks_obtained = filter_var($_POST['marks_obtained'] ?? '', FILTER_VALIDATE_FLOAT);
    $max_marks      = filter_var($_POST['max_marks'] ?? 100, FILTER_VALIDATE_FLOAT);
    $recorded_date  = !empty($_POST['recorded_date']) ? $_POST['recorded_date'] : date('Y-m-d');

    // Validation
    if ($student_id <= 0 || empty($subject) || empty($exam_name) || $marks_obtained === false || $max_marks === false) {
        $err = urlencode('Please complete all fields with valid numbers for marks.');
        $redirect_url = ($mark_id > 0) ? "../faculty/marks.html?action=edit&id={$mark_id}&error={$err}" : "../faculty/marks.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }

    if ($marks_obtained < 0 || $max_marks <= 0) {
        $err = urlencode('Marks obtained cannot be negative and maximum marks must be greater than zero.');
        $redirect_url = ($mark_id > 0) ? "../faculty/marks.html?action=edit&id={$mark_id}&error={$err}" : "../faculty/marks.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }

    if ($marks_obtained > $max_marks) {
        $err = urlencode('Marks obtained cannot exceed maximum marks.');
        $redirect_url = ($mark_id > 0) ? "../faculty/marks.html?action=edit&id={$mark_id}&error={$err}" : "../faculty/marks.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }

    try {
        if ($mark_id > 0) {
            // Update existing score
            $stmt = $pdo->prepare("
                UPDATE marks 
                SET student_id = ?, subject = ?, exam_name = ?, marks_obtained = ?, max_marks = ?, recorded_date = ? 
                WHERE id = ?
            ");
            $stmt->execute([$student_id, $subject, $exam_name, $marks_obtained, $max_marks, $recorded_date, $mark_id]);

            $msg = urlencode('Mark record was updated successfully.');
            header("Location: ../faculty/marks.html?success={$msg}");
            exit;
        } else {
            // Insert new score
            $stmt = $pdo->prepare("
                INSERT INTO marks (student_id, subject, exam_name, marks_obtained, max_marks, recorded_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student_id, $subject, $exam_name, $marks_obtained, $max_marks, $recorded_date]);

            $msg = urlencode("Marks for '{$exam_name}' recorded successfully.");
            header("Location: ../faculty/marks.html?success={$msg}");
            exit;
        }
    } catch (PDOException $e) {
        $err = urlencode('Error saving marks: ' . $e->getMessage());
        $redirect_url = ($mark_id > 0) ? "../faculty/marks.html?action=edit&id={$mark_id}&error={$err}" : "../faculty/marks.html?error={$err}";
        header("Location: {$redirect_url}");
        exit;
    }
}

// Fallback redirect
header('Location: ../faculty/marks.html');
exit;

