<?php
// create_session.php
require 'db_connect.php';

$message = "";

// 1. Only run this code IF the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // FIX: Use '??' to prevent "Undefined array key" errors
    $course_id = $_POST['course_id'] ?? '';
    $group_id  = $_POST['group_id'] ?? '';
    $prof_id   = $_POST['prof_id'] ?? '';
    
    // Automatically set today's date
    $date = date('Y-m-d'); 

    if (!empty($course_id) && !empty($group_id) && !empty($prof_id)) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                // Insert the new session
                $sql = "INSERT INTO attendance_sessions (course_id, group_id, session_date, opened_by, status) VALUES (?, ?, ?, ?, 'open')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$course_id, $group_id, $date, $prof_id]);
                
                // Get the ID
                $session_id = $pdo->lastInsertId();
                
                $message = "<div style='color:green; padding: 10px; border: 1px solid green; background: #d4edda;'>
                                <h3>Session Created Successfully!</h3>
                                <p><strong>Session ID:</strong> $session_id</p>
                                <p>Status: Open</p>
                            </div>";
            } catch (PDOException $e) {
                $message = "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        $message = "<div style='color:red'>All fields are required.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Session</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>
<div class="container">
    <h1>Open New Class Session</h1>
    
    <!-- Display Message Here -->
    <?php echo $message; ?>
    
    <section class="card">
        <form method="POST" action="">
            <div style="margin-bottom:15px;">
                <label>Course Name/ID:</label>
                <input type="text" name="course_id" required placeholder="e.g. Web Dev 101" style="width:100%; padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Group:</label>
                <input type="text" name="group_id" required placeholder="e.g. A1" style="width:100%; padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Professor ID:</label>
                <input type="text" name="prof_id" required placeholder="e.g. PROF_001" style="width:100%; padding:8px;">
            </div>
            <button type="submit" class="btn">Create Session</button>
        </form>
    </section>
    
    <div style="text-align:center;">
        <a href="close_session.php" class="btn btn-secondary">Go to Close Session Page</a>
    </div>
</div>
</body>
</html>