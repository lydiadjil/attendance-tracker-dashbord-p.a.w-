<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = $_POST['session_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];

    $pdo = getDBConnection();

    // Check if record exists
    $check = $pdo->prepare("SELECT id FROM attendance WHERE session_id = ? AND student_id = ?");
    $check->execute([$session_id, $student_id]);
    $exists = $check->fetch();

    if ($exists) {
        // Update
        $sql = "UPDATE attendance SET status = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $exists['id']]);
    } else {
        // Insert
        $sql = "INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$session_id, $student_id, $status]);
    }
    echo "OK";
}
?>