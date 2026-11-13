<?php

 //Database Configuration & Connection

// Database credentials for local XAMPP setup
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'school_management';

try {
    // Create a new PDO instance
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   
        ]
    );
} catch (PDOException $e) {
    
    die("
        <div style='font-family: Arial, sans-serif; max-width: 650px; margin: 40px auto; padding: 25px; border: 1px solid #f87171; background-color: #fef2f2; border-radius: 8px;'>
            <h2 style='color: #b91c1c; margin-top: 0;'>Database Connection Error</h2>
            <p><strong>Could not connect to the MySQL database:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <hr style='border: none; border-top: 1px solid #fca5a5; margin: 15px 0;'>
            <h4 style='color: #7f1d1d; margin-bottom: 8px;'>Please check the following steps in XAMPP:</h4>
            <ol style='color: #374151; line-height: 1.6;'>
                <li>Make sure the <strong>MySQL</strong> service is started in your <strong>XAMPP Control Panel</strong>.</li>
                <li>Make sure you have imported the <code>database/school_management.sql</code> file into phpMyAdmin.</li>
                <li>Ensure the database name is <code>school_management</code>, user is <code>root</code>, and password is blank.</li>
            </ol>
        </div>
    ");
}
