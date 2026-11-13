<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student', '../');

$user = current_user();
$student_id = $_SESSION['profile_id'] ?? 0;

$student = null;
$total_days = 0;
$present_days = 0;
$attendance_pct = 0;
$assignments_count = 0;
$results_count = 0;
$recent_assignments = [];
$recent_marks = [];

try {
    // 1. Fetch full student profile
    $s_stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $s_stmt->execute([$student_id]);
    $student = $s_stmt->fetch();

    $class_name = $student['class_name'] ?? ($_SESSION['class_name'] ?? '');

    // 2. Attendance percentage calculation
    $att_total_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ?");
    $att_total_stmt->execute([$student_id]);
    $total_days = (int)$att_total_stmt->fetchColumn();

    $att_pres_stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE student_id = ? AND status = 'Present'");
    $att_pres_stmt->execute([$student_id]);
    $present_days = (int)$att_pres_stmt->fetchColumn();

    if ($total_days > 0) {
        $attendance_pct = round(($present_days / $total_days) * 100, 1);
    }

    // 3. Count assignments for this student's class
    $assign_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE class_name = ?");
    $assign_count_stmt->execute([$class_name]);
    $assignments_count = (int)$assign_count_stmt->fetchColumn();

    // 4. Count exam marks records
    $marks_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM marks WHERE student_id = ?");
    $marks_count_stmt->execute([$student_id]);
    $results_count = (int)$marks_count_stmt->fetchColumn();

    // 5. Fetch upcoming assignments for this class
    $recent_assign_stmt = $pdo->prepare("
        SELECT * FROM assignments 
        WHERE class_name = ? 
        ORDER BY due_date ASC, id DESC 
        LIMIT 4
    ");
    $recent_assign_stmt->execute([$class_name]);
    $recent_assignments = $recent_assign_stmt->fetchAll();

    // 6. Fetch recent marks
    $recent_marks_stmt = $pdo->prepare("
        SELECT * FROM marks 
        WHERE student_id = ? 
        ORDER BY recorded_date DESC, id DESC 
        LIMIT 4
    ");
    $recent_marks_stmt->execute([$student_id]);
    $recent_marks = $recent_marks_stmt->fetchAll();

} catch (PDOException $e) {
    set_flash('error', "Database error: " . $e->getMessage());
}

$page_title   = 'Student Dashboard';
$current_page = 'dashboard';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Student Academic Portal</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Welcome, <strong><?php echo escape($student['full_name'] ?? $user['full_name']); ?></strong> 
            &bull; Roll No: <strong><?php echo escape($student['roll_number'] ?? ''); ?></strong> 
            &bull; Class: <strong><?php echo escape($student['class_name'] ?? ''); ?></strong>
        </p>
    </div>
    <div>
        <a href="profile.php" class="btn btn-sm btn-secondary">View My Profile &rarr;</a>
    </div>
</div>

<!-- Academic Summary Metric Cards -->
<div class="stats-grid">
    <div class="stat-card <?php echo ($attendance_pct >= 75) ? 'green' : 'orange'; ?>">
        <div class="stat-title">My Attendance Rate</div>
        <div class="stat-value"><?php echo $attendance_pct; ?>%</div>
        <div class="stat-desc"><?php echo $present_days; ?> of <?php echo $total_days; ?> days attended</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Class Assignments</div>
        <div class="stat-value"><?php echo $assignments_count; ?></div>
        <div class="stat-desc">For <?php echo escape($student['class_name'] ?? 'Class'); ?></div>
    </div>

    <div class="stat-card purple">
        <div class="stat-title">Exam Results</div>
        <div class="stat-value"><?php echo $results_count; ?></div>
        <div class="stat-desc">Subjects evaluated</div>
    </div>

    <div class="stat-card orange">
        <div class="stat-title">Student Roll No</div>
        <div class="stat-value" style="font-size: 1.4rem;"><?php echo escape($student['roll_number'] ?? 'STU'); ?></div>
        <div class="stat-desc">Class: <?php echo escape($student['class_name'] ?? ''); ?></div>
    </div>
</div>

<!-- Two Column Dashboard Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
    <!-- Active Class Assignments -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>Class Assignments</h3>
            <a href="assignments.php" style="font-size: 0.85rem;">View All &rarr;</a>
        </div>

        <?php if (!empty($recent_assignments)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_assignments as $assign): ?>
                        <tr>
                            <td><strong><?php echo escape($assign['subject']); ?></strong></td>
                            <td><?php echo escape($assign['title']); ?></td>
                            <td>
                                <span style="font-size: 0.85rem; color: #b91c1c; font-weight: 600;">
                                    <?php echo escape(date('d M Y', strtotime($assign['due_date']))); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No assignments currently due for your class.</p>
        <?php endif; ?>
    </div>

    <!-- Recent Exam Results -->
    <div class="card-table-wrapper" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
            <h3>Recent Test Scores</h3>
            <a href="results.php" style="font-size: 0.85rem;">Full Report Card &rarr;</a>
        </div>

        <?php if (!empty($recent_marks)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Score</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_marks as $m): ?>
                        <?php 
                            $pct = ($m['max_marks'] > 0) ? round(($m['marks_obtained'] / $m['max_marks']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo escape($m['subject']); ?></strong></td>
                            <td><?php echo escape(number_format($m['marks_obtained'], 1)); ?> / <?php echo escape(number_format($m['max_marks'], 0)); ?></td>
                            <td>
                                <span class="badge-status <?php echo ($pct >= 50) ? 'badge-present' : 'badge-absent'; ?>">
                                    <?php echo $pct; ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No examination results published yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
