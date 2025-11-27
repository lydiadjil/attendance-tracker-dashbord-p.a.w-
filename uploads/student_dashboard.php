<?php
session_start();
require 'db_connect.php';

// 1. Security Check: Must be a Student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php"); exit;
}

$student_id = $_SESSION['user_id'];
$pdo = getDBConnection();
$message = "";

// 2. Logic: Handle File Upload (Justification)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['justification_file'])) {
    $session_id = $_POST['session_id'];
    $reason = $_POST['reason_text']; // Capture the text reason
    
    // File Upload Setup
    $target_dir = "uploads/";
    // Rename file to avoid duplicates: time_filename
    $file_name = time() . "_" . basename($_FILES["justification_file"]["name"]);
    $target_file = $target_dir . $file_name;
    
    // Try to move file
    if (move_uploaded_file($_FILES["justification_file"]["tmp_name"], $target_file)) {
        try {
            // Insert into justifications table
            $stmt = $pdo->prepare("INSERT INTO justifications (student_id, session_id, reason, status) VALUES (?, ?, ?, 'pending')");
            // We save the reason text + the file path in the 'reason' column or separate if you modified the table.
            // Based on the SQL I gave you earlier, we put the text in 'reason'. 
            // *Wait*, standard practice: Save the file path! 
            // Let's combine them: "Reason: [Text] | File: [Path]"
            $full_reason = "Text: " . $reason . " | File: " . $target_file;
            
            $stmt->execute([$student_id, $session_id, $full_reason]);
            
            // Optional: Update attendance status to 'justified' immediately? 
            // No, usually Admin/Prof accepts it first. We leave it as 'absent' until approved.
            
            $message = "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:15px;'>Justification sent successfully!</div>";
        } catch (PDOException $e) {
            $message = "<div style='color:red'>Database Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div style='color:red'>Error uploading file. Make sure 'uploads' folder exists!</div>";
    }
}

// 3. Data: Fetch My Attendance Records
// We join tables to get Course Name and Session Date
$sql = "SELECT a.status, s.session_date, c.name as course_name, s.id as session_id
        FROM attendance a
        JOIN sessions s ON a.session_id = s.id
        JOIN courses c ON s.course_id = c.id
        WHERE a.student_id = ? 
        ORDER BY s.session_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student Portal</title>
    <link rel="stylesheet" href="exo2.css">
    <style>
        .badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 0.8em; }
        .bg-present { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .bg-absent { background: rgba(231, 76, 60, 0.2); color: #e74c3c; }
        .bg-justified { background: rgba(52, 152, 219, 0.2); color: #3498db; }
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Student Portal</h1>
        <div>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <!-- NOTIFICATION AREA -->
    <?php echo $message; ?>

    <!-- ATTENDANCE HISTORY -->
    <section class="card">
        <h2>My Attendance History</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Justification</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?php echo $r['session_date']; ?></td>
                    <td><?php echo htmlspecialchars($r['course_name']); ?></td>
                    <td>
                        <span class="badge <?php echo 'bg-' . $r['status']; ?>">
                            <?php echo strtoupper($r['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($r['status'] == 'absent'): ?>
                            <!-- Form to upload justification -->
                            <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
                                <input type="hidden" name="session_id" value="<?php echo $r['session_id']; ?>">
                                <input type="text" name="reason_text" placeholder="Why were you absent?" required 
                                       style="padding:5px; border-radius:4px; border:1px solid #555; background:#222; color:white;">
                                <input type="file" name="justification_file" required style="color:white; font-size:0.8em; width:180px;">
                                <button type="submit" class="btn" style="padding:5px 10px; font-size:0.8em;">Send</button>
                            </form>
                        <?php elseif ($r['status'] == 'justified'): ?>
                            <span style="color:#3498db;">Accepted</span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($records)): ?>
                    <tr><td colspan="4" style="text-align:center;">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>