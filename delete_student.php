<?php
require 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo = getDBConnection();

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            die("Error deleting record: " . $e->getMessage());
        }
    }
}

// Go back to the list
header("Location: list_students.php");
exit;
?>