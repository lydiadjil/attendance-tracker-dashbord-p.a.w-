<?php
// fix_login.php
require 'db_connect.php';
$pdo = getDBConnection();

// 1. Create a hash for '123456'
$password = password_hash('123456', PASSWORD_DEFAULT);

try {
    // 2. Clear old users to avoid duplicates
    $pdo->exec("DELETE FROM users");

    // 3. Insert fresh users
    $sql = "INSERT INTO users (full_name, email, password, role, matricule) VALUES 
    ('System Admin', 'admin@algiers.dz', '$password', 'admin', NULL),
    ('Dr. Professor', 'prof@algiers.dz', '$password', 'professor', NULL),
    ('Ali Student', 'ali@student.dz', '$password', 'student', '2024001')";

    $pdo->exec($sql);

    echo "<h1 style='color:green'>Users Reset Successfully!</h1>";
    echo "<p>You can now login with <strong>123456</strong>.</p>";
    echo "<a href='index.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
