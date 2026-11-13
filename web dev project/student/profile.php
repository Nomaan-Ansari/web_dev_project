<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student', '../');

$student_id = $_SESSION['profile_id'] ?? 0;
$student = null;

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.created_at as account_created 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.id = ?
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        set_flash('error', "Student profile record not found.");
    }
} catch (PDOException $e) {
    set_flash('error', "Database error: " . $e->getMessage());
}

$page_title   = 'My Profile';
$current_page = 'profile';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>My Student Profile</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Personal and academic enrollment records</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<?php if ($student): ?>
    <!-- 1. Academic & Personal Information -->
    <div class="form-card">
        <h3>Academic & Personal Information</h3>
        <div class="profile-grid">
            <div class="profile-field">
                <span class="label">Full Name</span>
                <span class="value"><?php echo escape($student['full_name']); ?></span>
            </div>

            <div class="profile-field">
                <span class="label">Roll Number</span>
                <span class="value"><strong><?php echo escape($student['roll_number']); ?></strong></span>
            </div>

            <div class="profile-field">
                <span class="label">Class / Grade</span>
                <span class="value"><?php echo escape($student['class_name']); ?></span>
            </div>

            <div class="profile-field">
                <span class="label">Gender</span>
                <span class="value"><?php echo escape($student['gender']); ?></span>
            </div>

            <div class="profile-field">
                <span class="label">Date of Birth</span>
                <span class="value">
                    <?php echo !empty($student['dob']) ? escape(date('d F Y', strtotime($student['dob']))) : 'Not Specified'; ?>
                </span>
            </div>

            <div class="profile-field">
                <span class="label">Student Contact Phone</span>
                <span class="value"><?php echo escape($student['phone'] ?: 'N/A'); ?></span>
            </div>
        </div>

        <div style="margin-top: 15px;">
            <div class="profile-field">
                <span class="label">Residential Address</span>
                <span class="value"><?php echo escape($student['address'] ?: 'Not Provided'); ?></span>
            </div>
        </div>
    </div>

    <!-- 2. Parent / Guardian Details -->
    <div class="form-card">
        <h3>Parent / Guardian Contact</h3>
        <div class="profile-grid">
            <div class="profile-field">
                <span class="label">Parent / Guardian Name</span>
                <span class="value"><?php echo escape($student['parent_name'] ?: 'N/A'); ?></span>
            </div>

            <div class="profile-field">
                <span class="label">Emergency / Parent Phone</span>
                <span class="value"><?php echo escape($student['parent_phone'] ?: 'N/A'); ?></span>
            </div>
        </div>
    </div>

    <!-- 3. Portal Account Details -->
    <div class="form-card">
        <h3>Student Portal Account</h3>
        <div class="profile-grid">
            <div class="profile-field">
                <span class="label">Login Username</span>
                <span class="value"><code><?php echo escape($student['username']); ?></code></span>
            </div>

            <div class="profile-field">
                <span class="label">Registered Email</span>
                <span class="value"><?php echo escape($student['email']); ?></span>
            </div>

            <div class="profile-field">
                <span class="label">Account Role</span>
                <span class="value"><span class="badge-status badge-present">Student</span></span>
            </div>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 15px;">
            <em>Note: To update your personal details or reset your password, please contact the Principal Office.</em>
        </p>
    </div>

<?php else: ?>
    <div class="alert alert-error">Unable to display profile information.</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
