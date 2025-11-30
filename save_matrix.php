<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_id = $_POST['session_id'];
    $student_id = $_POST['student_id'];
    $type = $_POST['type']; // 'presence' or 'participation'
    $value = $_POST['value']; // 'true' or 'false'

    $pdo = getDBConnection();

    // Check if record exists
    $check = $pdo->prepare("SELECT id FROM attendance WHERE session_id = ? AND student_id = ?");
    $check->execute([$session_id, $student_id]);
    $exists = $check->fetch();

    if ($exists) {
        if ($type == 'presence') {
            $status = ($value == 'true') ? 'present' : 'absent';
            $sql = "UPDATE attendance SET status = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$status, $exists['id']]);
        } elseif ($type == 'participation') {
            $val = ($value == 'true') ? 1 : 0;
            $sql = "UPDATE attendance SET participation = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$val, $exists['id']]);
        }
    } else {
        // Create new record
        $status = ($type == 'presence' && $value == 'true') ? 'present' : 'absent';
        $part = ($type == 'participation' && $value == 'true') ? 1 : 0;
        
        $sql = "INSERT INTO attendance (session_id, student_id, status, participation) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$session_id, $student_id, $status, $part]);
    }
    echo "Saved";
}
?>