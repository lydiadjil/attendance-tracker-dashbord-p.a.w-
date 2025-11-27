<?php
// db_connect.php - EMERGENCY HARDCODED VERSION

function getDBConnection() {
    // ----------------------------------------------------
    // EDIT THESE SETTINGS DIRECTLY HERE
    // ----------------------------------------------------
    $host = '127.0.0.1:3307';  // Try 3307. If fail, try 3306 or 3308
    $username = 'root';
    $password = '';            // LEAVE EMPTY
    $dbname = 'attendance_db';
    // ----------------------------------------------------

    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("<h3 style='color:red'>Database Connection Failed!</h3>
             <p>Error: " . $e->getMessage() . "</p>
             <p>Trying to connect to: <strong>$host</strong> with user <strong>$username</strong></p>");
    }
}
?>