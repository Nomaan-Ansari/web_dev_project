<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';


require_role('student', '../');

$student_id = $_SESSION['profile_id'] ?? 0;
$class_name = '';
$assignments = [];

try {
    // 1. Get student class
    $s_stmt = $pdo->prepare("SELECT class_name FROM students WHERE id = ?");
    $s_stmt->execute([$student_id]);
    $class_name = $s_stmt->fetchColumn() ?: ($_SESSION['class_name'] ?? '');

    // 2. Fetch assignments for this class joined with faculty name
    $stmt = $pdo->prepare("
        SELECT a.*, f.full_name as faculty_name 
        FROM assignments a 
        JOIN faculty f ON a.faculty_id = f.id 
        WHERE a.class_name = ? 
        ORDER BY a.due_date ASC, a.id DESC
    ");
    $stmt->execute([$class_name]);
    $assignments = $stmt->fetchAll();

} catch (PDOException $e) {
    set_flash('error', "Error loading assignments: " . $e->getMessage());
}

$page_title   = 'My Class Assignments';
$current_page = 'assignments';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Class Homework & Assignments</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Assignments and projects assigned for <strong><?php echo escape($class_name); ?></strong>
        </p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="card-table-wrapper">
    <h3>Active Assignments (<?php echo count($assignments); ?>)</h3>

    <?php if (!empty($assignments)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 120px;">Due Date</th>
                    <th style="width: 130px;">Subject</th>
                    <th style="width: 220px;">Title</th>
                    <th>Instructions</th>
                    <th style="width: 170px;">Teacher</th>
                    <th style="width: 100px;">Timeline</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $a): ?>
                    <?php 
                        $is_overdue = (strtotime($a['due_date']) < strtotime(date('Y-m-d')));
                    ?>
                    <tr>
                        <td><strong><?php echo escape(date('d M Y', strtotime($a['due_date']))); ?></strong></td>
                        <td><?php echo escape($a['subject']); ?></td>
                        <td><strong style="color: var(--primary-blue);"><?php echo escape($a['title']); ?></strong></td>
                        <td><?php echo nl2br(escape($a['description'])); ?></td>
                        <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo escape($a['faculty_name']); ?></span></td>
                        <td>
                            <?php if ($is_overdue): ?>
                                <span class="badge-status badge-absent">Past Due</span>
                            <?php else: ?>
                                <span class="badge-status badge-present">Active</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--text-muted); padding: 15px 0;">No assignments currently posted for <?php echo escape($class_name); ?>.</p>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
