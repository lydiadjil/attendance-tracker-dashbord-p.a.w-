<?php
// add_student.php

$message = "";
$status = "error";

// 1. Takes a form with fields
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $student_id = $_POST['student_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $group = $_POST['group'] ?? '';

    // 2. Validate them
    if (empty($student_id) || empty($name) || empty($group)) {
        $message = "Error: All fields (ID, Name, Group) are required.";
    } else {
        
        $file = 'students.json';
        
        // 3. Loads existing students
        $students = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $students = json_decode($json, true);
            if (!is_array($students)) $students = [];
        }

        // 4. Adds the new student to the array
        $students[] = [
            "student_id" => $student_id,
            "name" => $name,
            "group" => $group
        ];

        // 5. Saves back to students.json
        if (file_put_contents($file, json_encode($students, JSON_PRETTY_PRINT))) {
            $message = "Success! Student <strong>$name</strong> (Group $group) added.";
            $status = "success";
        } else {
            $message = "Error: Could not save to file.";
        }
    }
}
?>

<!-- 6. Displays a confirmation message -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operation Status</title>
    <!-- Re-use your CSS for consistency -->
    <link rel="stylesheet" href="exo2.css"> 
    <style>
        .msg-container { text-align: center; margin-top: 50px; }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="container msg-container card">
        <h2 class="<?php echo $status; ?>">
            <?php echo $message; ?>
        </h2>
        <br>
        <a href="exo1.php" class="btn">Back to Dashboard</a>
    </div>
</body>
</html>