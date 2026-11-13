<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('faculty', '../');

$user = current_user();
$faculty_id = $_SESSION['profile_id'] ?? 0;


$faculty_info = null;
$my_assignments_count = 0;
$total_students_count = 0;
$total_notices_count  = 0;
$recent_assignments   = [];
$recent_notices       = [];

try {
    // 1. Fetch faculty profile details
    $f_stmt = $pdo->prepare("SELECT * FROM faculty WHERE id = ?");
    $f_stmt->execute([$faculty_id]);
    $faculty_info = $f_stmt->fetch();

    // 2. Count assignments posted by this faculty
    $a_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE faculty_id = ?");
    $a_count_stmt->execute([$faculty_id]);
    $my_assignments_count = $a_count_stmt->fetchColumn();

    // 3. Count total students in school
    $total_students_count = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

    // 4. Count notices
    $total_notices_count = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();

    // 5. Fetch recent assignments posted by this faculty
    $a_stmt = $pdo->prepare("SELECT * FROM assignments WHERE faculty_id = ? ORDER BY id DESC LIMIT 4");
    $a_stmt->execute([$faculty_id]);
    $recent_assignments = $a_stmt->fetchAll();

    // 6. Fetch 3 latest school notices
    $recent_notices = $pdo->query("SELECT * FROM notices ORDER BY posted_date DESC, id DESC LIMIT 3")->fetchAll();

} catch (PDOException $e) {
    set_flash('error', 'Database error: ' . $e->getMessage());
}

$page_title   = 'Faculty Dashboard';
$current_page = 'dashboard';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Faculty Academic Dashboard</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Welcome back, <strong><?php echo escape($faculty_info['full_name'] ?? $user['full_name']); ?></strong> 
            (<?php echo escape($faculty_info['subject_specialization'] ?? 'Teaching Faculty'); ?>)
        </p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="attendance.html" class="btn btn-sm btn-primary">Mark Attendance</a>
        <a href="assignments.html" class="btn btn-sm btn-secondary">+ Post Assignment</a>
        <a href="marks.html" class="btn btn-sm btn-success">Record Marks</a>
    </div>
</div>

<!-- Statistic Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-title">My Assignments</div>
        <div class="stat-value"><?php echo (int)$my_assignments_count; ?></div>
        <div class="stat-desc">Posted for students</div>
    </div>

    <div class="stat-card green">
        <div class="stat-title">Total Students</div>
        <div class="stat-value"><?php echo (int)$total_students_count; ?></div>
        <div class="stat-desc">Across all sections</div>
    </div>

    <div class="stat-card purple">
        <div class="stat-title">School Notices</div>
        <div class="stat-value"><?php echo (int)$total_notices_count; ?></div>
        <div class="stat-desc">Announcements active</div>
    </div>

    <div class="stat-card orange">
        <div class="stat-title">Department</div>
        <div class="stat-value" style="font-size: 1.25rem;"><?php echo escape($faculty_info['subject_specialization'] ?? 'Academics'); ?></div>
        <div class="stat-desc"><?php echo escape($faculty_info['qualification'] ?? 'Faculty'); ?></div>
    </div>
</div>

<!-- Two Column Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    <!-- Recent Assignments by this Teacher -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>My Recent Assignments</h3>
            <a href="assignments.html" style="font-size: 0.85rem;">Manage All &rarr;</a>
        </div>

        <?php if (!empty($recent_assignments)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Title</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_assignments as $assign): ?>
                        <tr>
                            <td><strong><?php echo escape($assign['class_name']); ?></strong></td>
                            <td><?php echo escape($assign['title']); ?></td>
                            <td><?php echo escape(date('d M Y', strtotime($assign['due_date']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">You haven't posted any assignments yet.</p>
        <?php endif; ?>
    </div>

    <!-- Latest School Notices -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>Latest Principal Notices</h3>
            <a href="notices.html" style="font-size: 0.85rem;">View Notices &rarr;</a>
        </div>

        <?php if (!empty($recent_notices)): ?>
            <?php foreach ($recent_notices as $notice): ?>
                <div class="notice-item" style="padding: 10px 0;">
                    <div class="notice-header">
                        <strong style="color: var(--primary-blue); font-size: 0.95rem;"><?php echo escape($notice['title']); ?></strong>
                        <span class="notice-date"><?php echo escape(date('d M Y', strtotime($notice['posted_date']))); ?></span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-dark); margin-top: 4px;">
                        <?php echo escape(substr($notice['content'], 0, 110)) . (strlen($notice['content']) > 110 ? '...' : ''); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No notices posted yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
