<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? '';
$notice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. JSON API: Public Notices for Homepage 
if ($action === 'public') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $stmt = $pdo->query("SELECT * FROM notices ORDER BY posted_date DESC, id DESC LIMIT 5");
        $notices = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $notices]);
    } catch (PDOException $e) {
        // Fallback default notice if database table is empty or error occurs
        echo json_encode([
            'success' => true,
            'data' => [
                [
                    'title'       => 'Welcome to the New Academic Session 2026-27',
                    'posted_date' => date('Y-m-d'),
                    'content'     => 'We welcome all parents, students, and staff to the new academic year. Please refer to your class schedules.',
                    'posted_by'   => 'Principal Office'
                ]
            ]
        ]);
    }
    exit;
}

// 2. JSON API: Full Notices List (Requires user to be logged in)
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');

    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Please log in to view notices.']);
        exit;
    }

    try {
        $stmt = $pdo->query("SELECT * FROM notices ORDER BY posted_date DESC, id DESC");
        $notices = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $notices]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 3. DELETE Notice (Principal Only)
if ($action === 'delete' && $notice_id > 0) {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'principal') {
        header('Location: ../login.html?error=' . urlencode('Access denied. Principal permission required.'));
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM notices WHERE id = ?");
        $stmt->execute([$notice_id]);

        $msg = urlencode("Notice was deleted successfully.");
        header("Location: ../principal/notices.html?success={$msg}");
        exit;
    } catch (PDOException $e) {
        $err = urlencode('Error deleting notice: ' . $e->getMessage());
        header("Location: ../principal/notices.html?error={$err}");
        exit;
    }
}

// 4. Form Submission: Publish Notice (POST-Principal Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'principal') {
        header('Location: ../login.html?error=' . urlencode('Access denied. Principal permission required.'));
        exit;
    }

    $title       = trim($_POST['title'] ?? '');
    $content     = trim($_POST['content'] ?? '');
    $posted_by   = trim($_POST['posted_by'] ?? 'Principal Office');
    $posted_date = !empty($_POST['posted_date']) ? $_POST['posted_date'] : date('Y-m-d');

    if (empty($title) || empty($content)) {
        $err = urlencode('Please enter both the notice title and the announcement content.');
        header("Location: ../principal/notices.html?error={$err}");
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notices (title, content, posted_by, posted_date) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$title, $content, $posted_by, $posted_date]);

        $msg = urlencode("New notice '{$title}' published successfully.");
        header("Location: ../principal/notices.html?success={$msg}");
        exit;
    } catch (PDOException $e) {
        $err = urlencode('Error publishing notice: ' . $e->getMessage());
        header("Location: ../principal/notices.html?error={$err}");
        exit;
    }
}

// Fallback redirect
header('Location: ../index.html');
exit;

