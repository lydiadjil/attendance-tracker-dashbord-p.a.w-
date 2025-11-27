<?php
// Set timezone to ensure the date is correct (optional but good practice)
date_default_timezone_set('Africa/Algiers'); 

$students_file = 'students.json';
$today = date('Y-m-d');
$attendance_file = "attendance_{$today}.json";
$message = "";
$msg_class = "";

// 1. Load Students Logic
$students = [];
if (file_exists($students_file)) {
    $students = json_decode(file_get_contents($students_file), true);
    if (!is_array($students)) $students = [];
}

// 2. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CHECK: Does the file for today already exist?
    if (file_exists($attendance_file)) {
        $message = "Attendance for today has already been taken.";
        $msg_class = "error"; // Red color
    } else {
        // Prepare the array to save
        $attendance_record = [];

        // Loop through the POST data to build the structure
        // We expect $_POST['status'] to be an array keyed by student_id
        if (isset($_POST['status']) && is_array($_POST['status'])) {
            foreach ($_POST['status'] as $student_id => $status_value) {
                $attendance_record[] = [
                    "student_id" => $student_id,
                    "status"     => $status_value
                ];
            }

            // Save the file
            if (file_put_contents($attendance_file, json_encode($attendance_record, JSON_PRETTY_PRINT))) {
                $message = "Success! Attendance saved to $attendance_file.";
                $msg_class = "success"; // Green color
            } else {
                $message = "Error: Could not save the file.";
                $msg_class = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Take Attendance</title>
    <!-- Using your existing CSS for consistency -->
    <link rel="stylesheet" href="exo2.css">
    <style>
        /* Specific styles for this page */
        .radio-group label { margin-right: 15px; cursor: pointer; }
        .radio-group input { margin-right: 5px; transform: scale(1.2); }
        .status-msg { padding: 15px; border-radius: 5px; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .success { background-color: #2ecc71; color: white; }
        .error { background-color: #e74c3c; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>Take Attendance (<?php echo $today; ?>)</h1>

    <!-- Display Message -->
    <?php if (!empty($message)): ?>
        <div class="status-msg <?php echo $msg_class; ?>">
            <?php echo $message; ?>
        </div>
        <!-- Link to go back -->
        <div style="text-align:center; margin-bottom:20px;">
            <a href="exo1.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    <?php endif; ?>

    <!-- 
       Only show the form if the file DOES NOT exist 
       (Or strictly following instructions: just show the message on submit. 
       But usually, you hide the form if it's already done. 
       I will keep the form visible so you can see the logic, but the save block stops duplicate files.)
    -->
    
    <section class="card">
        <form action="take_attendance.php" method="POST">
            <table id="attendance-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Group</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="4">No students found in students.json</td></tr>
                    <?php else: ?>
                        <?php foreach ($students as $student): ?>
                            <?php 
                                $id = htmlspecialchars($student['student_id'] ?? '');
                                $name = htmlspecialchars($student['name'] ?? '');
                                $group = htmlspecialchars($student['group'] ?? '');
                                if ($id == '') continue;
                            ?>
                            <tr class="student-row">
                                <td><?php echo $id; ?></td>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $group; ?></td>
                                <td class="radio-group">
                                    <!-- 
                                       Radio buttons name format: status[student_id]
                                       This allows us to map the ID to the status easily in PHP.
                                    -->
                                    <label>
                                        <input type="radio" name="status[<?php echo $id; ?>]" value="present" checked> 
                                        Present
                                    </label>
                                    <label>
                                        <input type="radio" name="status[<?php echo $id; ?>]" value="absent"> 
                                        Absent
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($students)): ?>
                <div class="center-container">
                    <button type="submit" class="btn">Save Attendance</button>
                </div>
            <?php endif; ?>
        </form>
    </section>

</div>

</body>
</html>