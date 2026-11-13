<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('student', '../');

$student_id = $_SESSION['profile_id'] ?? 0;

$marks = [];
$total_obtained = 0;
$total_max = 0;
$overall_pct = 0;

try {
    $stmt = $pdo->prepare("
        SELECT * FROM marks 
        WHERE student_id = ? 
        ORDER BY exam_name ASC, subject ASC
    ");
    $stmt->execute([$student_id]);
    $marks = $stmt->fetchAll();

    foreach ($marks as $m) {
        $total_obtained += (float)$m['marks_obtained'];
        $total_max      += (float)$m['max_marks'];
    }

    if ($total_max > 0) {
        $overall_pct = round(($total_obtained / $total_max) * 100, 1);
    }
} catch (PDOException $e) {
    set_flash('error', "Error loading academic results: " . $e->getMessage());
}

/**
 * Helper function to determine letter grade
 */
function calculate_grade($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C';
    if ($pct >= 50) return 'D';
    return 'F';
}

$overall_grade = calculate_grade($overall_pct);

$page_title   = 'Academic Results';
$current_page = 'results';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>Academic Examination Results</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Review your subject scores, maximum marks, and overall performance</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<!-- Performance Summary Cards -->
<div class="stats-grid">
    <div class="stat-card <?php echo ($overall_pct >= 50) ? 'green' : 'orange'; ?>">
        <div class="stat-title">Overall Percentage</div>
        <div class="stat-value"><?php echo $overall_pct; ?>%</div>
        <div class="stat-desc">Across <?php echo count($marks); ?> evaluated subjects</div>
    </div>

    <div class="stat-card purple">
        <div class="stat-title">Cumulative Grade</div>
        <div class="stat-value"><?php echo $overall_grade; ?></div>
        <div class="stat-desc">Based on evaluated scores</div>
    </div>

    <div class="stat-card">
        <div class="stat-title">Total Marks Obtained</div>
        <div class="stat-value"><?php echo number_format($total_obtained, 1); ?></div>
        <div class="stat-desc">Out of <?php echo number_format($total_max, 0); ?> maximum marks</div>
    </div>

    <div class="stat-card orange">
        <div class="stat-title">Evaluations Count</div>
        <div class="stat-value"><?php echo count($marks); ?></div>
        <div class="stat-desc">Exam papers recorded</div>
    </div>
</div>

<!-- Detailed Results Table -->
<div class="card-table-wrapper">
    <h3>Subject-Wise Performance Report</h3>

    <?php if (!empty($marks)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Examination</th>
                    <th>Subject Name</th>
                    <th>Marks Obtained</th>
                    <th>Maximum Marks</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th>Date Recorded</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($marks as $m): ?>
                    <?php 
                        $subject_pct = ($m['max_marks'] > 0) ? round(($m['marks_obtained'] / $m['max_marks']) * 100, 1) : 0;
                        $grade = calculate_grade($subject_pct);
                    ?>
                    <tr>
                        <td><strong><?php echo escape($m['exam_name']); ?></strong></td>
                        <td><?php echo escape($m['subject']); ?></td>
                        <td><strong style="color: var(--primary-blue);"><?php echo escape(number_format($m['marks_obtained'], 1)); ?></strong></td>
                        <td><?php echo escape(number_format($m['max_marks'], 0)); ?></td>
                        <td><?php echo $subject_pct; ?>%</td>
                        <td>
                            <span class="badge-status <?php echo ($subject_pct >= 50) ? 'badge-present' : 'badge-absent'; ?>">
                                <?php echo $grade; ?>
                            </span>
                        </td>
                        <td><span style="font-size: 0.85rem; color: var(--text-muted);"><?php echo escape(date('d M Y', strtotime($m['recorded_date']))); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: var(--light-blue); font-weight: 700;">
                    <td colspan="2">Aggregate Total</td>
                    <td style="color: var(--primary-blue);"><?php echo number_format($total_obtained, 1); ?></td>
                    <td><?php echo number_format($total_max, 0); ?></td>
                    <td><?php echo $overall_pct; ?>%</td>
                    <td colspan="2"><?php echo $overall_grade; ?></td>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p style="color: var(--text-muted); padding: 15px 0;">No examination results have been published for you yet.</p>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
