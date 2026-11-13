<?php

$page_title   = $page_title ?? 'Greenwood High School';
$current_page = $current_page ?? 'home';
$path_to_root = $path_to_root ?? '';
$is_dashboard = $is_dashboard ?? false;

// Ensure auth functions are available
require_once __DIR__ . '/auth.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($page_title); ?> - Greenwood High School</title>

    <link rel="stylesheet" href="<?php echo $path_to_root; ?>css/style.css">
</head>
<body>

<?php if (!$is_dashboard): ?>
    <!-- PUBLIC WEBSITE HEADER -->
    <header class="site-header">
        <div class="header-container">
            <div class="logo-area">
                <span class="logo-badge">GHS</span>
                <div>
                    <div class="school-name-text">Greenwood High School</div>
                    <div class="school-tagline">Excellence in Education & Character</div>
                </div>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button class="menu-toggle" aria-label="Toggle navigation">&#9776;</button>

            <!-- Navigation Links -->
            <ul class="nav-menu">
                <li><a href="<?php echo $path_to_root; ?>index.html" class="nav-link <?php echo ($current_page === 'home') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $path_to_root; ?>index.html#about" class="nav-link">About Us</a></li>
                <li><a href="<?php echo $path_to_root; ?>index.html#academics" class="nav-link">Academics</a></li>
                <li><a href="<?php echo $path_to_root; ?>index.html#notices" class="nav-link">Notices</a></li>
                <?php if (is_logged_in()): ?>
                    <?php 
                        $dash_link = $path_to_root . $user['role'] . '/dashboard.php';
                    ?>
                    <li><a href="<?php echo $dash_link; ?>" class="nav-link nav-link-login">My Dashboard</a></li>
                    <li><a href="<?php echo $path_to_root; ?>backend/logout.php" class="nav-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $path_to_root; ?>login.html" class="nav-link nav-link-login <?php echo ($current_page === 'login') ? 'active' : ''; ?>">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

<?php else: ?>
    <!-- DASHBOARD HEADER -->
    <header class="dashboard-header">
        <div class="header-container">
            <!-- Top bar: User welcome and logout -->
            <div class="dashboard-user-bar" style="width: 100%;">
                <div class="logo-area">
                    <span class="logo-badge">GHS</span>
                    <div>
                        <div class="school-name-text">Greenwood High School Portal</div>
                        <div class="user-info">
                            Welcome, <strong><?php echo escape($user['full_name']); ?></strong> 
                            <span class="role-badge"><?php echo escape($user['role']); ?></span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="<?php echo $path_to_root; ?>index.html" class="btn btn-sm btn-secondary" target="_blank" title="View Public Website">Website &rarr;</a>
                    <a href="<?php echo $path_to_root; ?>backend/logout.php" class="btn btn-sm btn-danger">Logout</a>
                </div>
            </div>

            <!-- Dashboard Sub-Navigation Tabs based on user role -->
            <nav style="width: 100%;">
                <ul class="dashboard-nav">
                    <?php if ($user['role'] === 'principal'): ?>
                        <li><a href="<?php echo $path_to_root; ?>principal/dashboard.php" class="<?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo $path_to_root; ?>principal/students.html" class="<?php echo ($current_page === 'students') ? 'active' : ''; ?>">Manage Students</a></li>
                        <li><a href="<?php echo $path_to_root; ?>principal/faculty.html" class="<?php echo ($current_page === 'faculty') ? 'active' : ''; ?>">Manage Faculty</a></li>
                        <li><a href="<?php echo $path_to_root; ?>principal/notices.html" class="<?php echo ($current_page === 'notices') ? 'active' : ''; ?>">Notices</a></li>

                    <?php elseif ($user['role'] === 'faculty'): ?>
                        <li><a href="<?php echo $path_to_root; ?>faculty/dashboard.php" class="<?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo $path_to_root; ?>faculty/attendance.html" class="<?php echo ($current_page === 'attendance') ? 'active' : ''; ?>">Student Attendance</a></li>
                        <li><a href="<?php echo $path_to_root; ?>faculty/assignments.html" class="<?php echo ($current_page === 'assignments') ? 'active' : ''; ?>">Assignments</a></li>
                        <li><a href="<?php echo $path_to_root; ?>faculty/marks.html" class="<?php echo ($current_page === 'marks') ? 'active' : ''; ?>">Student Marks</a></li>
                        <li><a href="<?php echo $path_to_root; ?>faculty/notices.html" class="<?php echo ($current_page === 'notices') ? 'active' : ''; ?>">Notices</a></li>

                    <?php elseif ($user['role'] === 'student'): ?>
                        <li><a href="<?php echo $path_to_root; ?>student/dashboard.php" class="<?php echo ($current_page === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                        <li><a href="<?php echo $path_to_root; ?>student/profile.php" class="<?php echo ($current_page === 'profile') ? 'active' : ''; ?>">My Profile</a></li>
                        <li><a href="<?php echo $path_to_root; ?>student/attendance.php" class="<?php echo ($current_page === 'attendance') ? 'active' : ''; ?>">Attendance</a></li>
                        <li><a href="<?php echo $path_to_root; ?>student/results.php" class="<?php echo ($current_page === 'results') ? 'active' : ''; ?>">Results</a></li>
                        <li><a href="<?php echo $path_to_root; ?>student/assignments.php" class="<?php echo ($current_page === 'assignments') ? 'active' : ''; ?>">Assignments</a></li>
                        <li><a href="<?php echo $path_to_root; ?>student/notices.php" class="<?php echo ($current_page === 'notices') ? 'active' : ''; ?>">Notices</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
<?php endif; ?>

<main class="main-content">
    
    <?php render_flash(); ?>
