<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student', '../');

$student_id = $_SESSION['profile_id'] ?? 0;

$attendance_records = [];
$total_days = 0;
$present_days = 0;
$absent_days = 0;
$attendance_pct = 0;

try {
    // 1. Fetch all attendance history for this student
    $stmt = $pdo->prepare("
        SELECT * FROM attendance 
        WHERE student_id = ? 
        ORDER BY date DESC
    ");
    $stmt->execute([$student_id]);
    $attendance_records = $stmt->fetchAll();

    $total_days = count($attendance_records);

    foreach ($attendance_records as $rec) {
        if ($rec['status'] === 'Present') {
            $present_days++;
        } else {
            $absent_days++;
        }
    }

    if ($total_days > 0) {
        $attendance_pct = round(($present_days / $total_days) * 100, 1);
    }
} catch (PDOException $e) {
    set_flash('error', "Error loading attendance history: " . $e->getMessage());
}

$page_title   = 'My Attendance';
$current_page = 'attendance';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>My Attendance Record</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Track your daily classroom presence and overall attendance percentage</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<!-- Summary Statistic Cards -->
<div class="stats-grid">
    <div class="stat-card <?php echo ($attendance_pct >= 75) ? 'green' : 'orange'; ?>">
        <div class="stat-title">Overall Attendance</div>
        <div class="stat-value"><?php echo $attendance_pct; ?>%</div>
        <div class="stat-desc"><?php echo ($attendance_pct >= 75) ? 'Good standing' : 'Below 75% requirement'; ?></div>
    </div>

    <div class="stat-card green">
        <div class="stat-title">Days Present</div>
        <div class="stat-value"><?php echo $present_days; ?></div>
        <div class="stat-desc">Sessions attended</div>
    </div>

    <div class="stat-card orange">
        <div class="stat-title">Days Absent</div>
        <div class="stat-value"><?php echo $absent_days; ?></div>
        <div class="stat-desc">Sessions missed</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Total Working Days</div>
        <div class="stat-value"><?php echo $total_days; ?></div>
        <div class="stat-desc">Recorded sessions</div>
    </div>
</div>

<!-- Attendance Detailed History Table -->
<div class="card-table-wrapper">
    <h3>Attendance Log (<?php echo $total_days; ?> Days Recorded)</h3>

    <?php if (!empty($attendance_records)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 140px;">Date</th>
                    <th style="width: 140px;">Day</th>
                    <th style="width: 120px;">Status</th>
                    <th>Teacher Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_records as $row): ?>
                    <tr>
                        <td><strong><?php echo escape(date('d M Y', strtotime($row['date']))); ?></strong></td>
                        <td><?php echo escape(date('l', strtotime($row['date']))); ?></td>
                        <td>
                            <?php if ($row['status'] === 'Present'): ?>
                                <span class="badge-status badge-present">Present</span>
                            <?php else: ?>
                                <span class="badge-status badge-absent">Absent</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo escape($row['remarks'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--text-muted); padding: 15px 0;">No attendance records have been posted yet.</p>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
