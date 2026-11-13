<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';


require_role('principal', '../');

$total_students = 0;
$total_faculty  = 0;
$total_notices  = 0;
$total_classes  = 0;

try {
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_faculty  = $pdo->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $total_notices  = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();
    $total_classes  = $pdo->query("SELECT COUNT(DISTINCT class_name) FROM students")->fetchColumn();
    
    // Fetch 5 most recent notices
    $recent_notices = $pdo->query("SELECT * FROM notices ORDER BY posted_date DESC, id DESC LIMIT 4")->fetchAll();

    // Fetch 5 recent students
    $recent_students = $pdo->query("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    set_flash('error', 'Error fetching dashboard data: ' . $e->getMessage());
}

$page_title   = 'Principal Dashboard';
$current_page = 'dashboard';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Principal Administration Dashboard</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Overview of Greenwood High School operations and records</p>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="students.html" class="btn btn-sm btn-primary">+ Add Student</a>
        <a href="faculty.html" class="btn btn-sm btn-secondary">+ Add Faculty</a>
        <a href="notices.html" class="btn btn-sm btn-success">+ Post Notice</a>
    </div>
</div>

<!-- Key Metric Statistic Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-title">Total Students</div>
        <div class="stat-value"><?php echo (int)$total_students; ?></div>
        <div class="stat-desc">Enrolled in school</div>
    </div>

    <div class="stat-card green">
        <div class="stat-title">Faculty Members</div>
        <div class="stat-value"><?php echo (int)$total_faculty; ?></div>
        <div class="stat-desc">Teaching staff</div>
    </div>

    <div class="stat-card purple">
        <div class="stat-title">Published Notices</div>
        <div class="stat-value"><?php echo (int)$total_notices; ?></div>
        <div class="stat-desc">Active announcements</div>
    </div>

    <div class="stat-card orange">
        <div class="stat-title">Active Classes</div>
        <div class="stat-value"><?php echo (int)$total_classes; ?></div>
        <div class="stat-desc">Academic divisions</div>
    </div>
</div>

<!-- Two-column Section: Recent Notices & Recently Added Students -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    <!-- Recent Notices -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>Recent School Notices</h3>
            <a href="notices.html" style="font-size: 0.85rem;">Manage Notices &rarr;</a>
        </div>
        
        <?php if (!empty($recent_notices)): ?>
            <?php foreach ($recent_notices as $notice): ?>
                <div class="notice-item" style="padding: 10px 0;">
                    <div class="notice-header">
                        <strong style="color: var(--primary-blue); font-size: 0.95rem;"><?php echo escape($notice['title']); ?></strong>
                        <span class="notice-date"><?php echo escape(date('d M Y', strtotime($notice['posted_date']))); ?></span>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-dark); margin-top: 4px;">
                        <?php echo escape(substr($notice['content'], 0, 100)) . (strlen($notice['content']) > 100 ? '...' : ''); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No notices available.</p>
        <?php endif; ?>
    </div>

    <!-- Recently Enrolled Students -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>Recently Enrolled Students</h3>
            <a href="students.html" style="font-size: 0.85rem;">View All Students &rarr;</a>
        </div>

        <?php if (!empty($recent_students)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Class</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_students as $st): ?>
                        <tr>
                            <td><strong><?php echo escape($st['roll_number']); ?></strong></td>
                            <td><?php echo escape($st['full_name']); ?></td>
                            <td><?php echo escape($st['class_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No students registered yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
