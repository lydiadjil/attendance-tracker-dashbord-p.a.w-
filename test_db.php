<?php
// test_db.php
require 'db_connect.php';

// Attempt to connect
$conn = getDBConnection();

if ($conn) {
    echo "<h1 style='color:green'>Connection successful</h1>";
    echo "Connected to database: <strong>$dbname</strong>";
} else {
    echo "<h1 style='color:red'>Connection failed</h1>";
    echo "Check 'db_errors.log' for details or verify your config.php settings.";
}
?>