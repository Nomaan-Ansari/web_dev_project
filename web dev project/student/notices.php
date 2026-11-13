<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('student', '../');


$notices = [];
try {
    $stmt = $pdo->query("SELECT * FROM notices ORDER BY posted_date DESC, id DESC");
    $notices = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash('error', "Error loading school notices: " . $e->getMessage());
}

$page_title   = 'School Notices';
$current_page = 'notices';
$path_to_root = '../';
$is_dashboard = true;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>School Notices & Circulars</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Official announcements, holiday schedules, and event notices</p>
    </div>
    <div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<div class="card-table-wrapper">
    <h3>Announcement Board</h3>

    <?php if (!empty($notices)): ?>
        <div class="notice-board" style="border: none; padding: 0; box-shadow: none;">
            <?php foreach ($notices as $notice): ?>
                <div class="notice-item" style="padding: 16px 0; border-bottom: 1px solid #e2e8f0;">
                    <div class="notice-header">
                        <span class="notice-title" style="font-size: 1.15rem;"><?php echo escape($notice['title']); ?></span>
                        <span class="notice-date">Published: <?php echo escape(date('d M Y', strtotime($notice['posted_date']))); ?></span>
                    </div>
                    <div class="notice-content" style="font-size: 0.95rem; margin: 8px 0;">
                        <?php echo nl2br(escape($notice['content'])); ?>
                    </div>
                    <div class="notice-author">
                        Posted by: <strong><?php echo escape($notice['posted_by']); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted); padding: 10px 0;">No notices are currently available.</p>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
