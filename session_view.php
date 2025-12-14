<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: index.php"); exit;
}

$session_id = $_GET['id'] ?? 0;
$pdo = getDBConnection();

// 1. Fetch Session Info
$stmt = $pdo->prepare("SELECT s.*, c.name as course_name FROM sessions s JOIN courses c ON s.course_id = c.id WHERE s.id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) die("Session not found.");

// 2. Logic: Close Session
if (isset($_POST['close_session'])) {
    $pdo->prepare("UPDATE sessions SET status='closed' WHERE id=?")->execute([$session_id]);
    header("Location: session_view.php?id=$session_id"); // Refresh
    exit;
}

// 3. Fetch Students & Their Attendance Status
// This query gets ALL students and joins with attendance table to see if they are marked present
$sql = "SELECT u.id, u.full_name, u.matricule, a.status as att_status 
        FROM users u 
        LEFT JOIN attendance a ON u.id = a.student_id AND a.session_id = ?
        WHERE u.role = 'student' 
        ORDER BY u.full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Class Attendance</title>
    <link rel="stylesheet" href="exo2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
    <a href="prof_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    
    <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
        <h1><?php echo htmlspecialchars($session['course_name']); ?> (<?php echo $session['session_date']; ?>)</h1>
        <?php if($session['status'] == 'open'): ?>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="close_session" value="1">
                <button type="submit" class="btn" style="background-color:#e74c3c;">Close Session</button>
            </form>
        <?php else: ?>
            <span style="padding:10px; background:#e74c3c; color:white; border-radius:5px;">SESSION CLOSED</span>
        <?php endif; ?>
    </div>

    <section class="card">
        <table>
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Student Name</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><?php echo $s['matricule']; ?></td>
                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                    <td>
                        <!-- If session is closed, disable inputs -->
                        <?php $disabled = ($session['status'] == 'closed') ? 'disabled' : ''; ?>
                        
                        <label style="margin-right:15px; color:#2ecc71; cursor:pointer;">
                            <input type="radio" name="att_<?php echo $s['id']; ?>" 
                                   class="att-radio" 
                                   data-student="<?php echo $s['id']; ?>" 
                                   value="present" 
                                   <?php echo ($s['att_status'] == 'present') ? 'checked' : ''; ?>
                                   <?php echo $disabled; ?>> 
                            Present
                        </label>
                        
                        <label style="margin-right:15px; color:#e74c3c; cursor:pointer;">
                            <input type="radio" name="att_<?php echo $s['id']; ?>" 
                                   class="att-radio" 
                                   data-student="<?php echo $s['id']; ?>" 
                                   value="absent" 
                                   <?php echo ($s['att_status'] == 'absent' || !$s['att_status']) ? 'checked' : ''; ?>
                                   <?php echo $disabled; ?>> 
                            Absent
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<!-- SCRIPT TO SAVE INSTANTLY -->
<script>
$(document).ready(function() {
    $('.att-radio').change(function() { //to detect when a radio button changes
        const studentId = $(this).data('student');
        const status = $(this).val();
        const sessionId = <?php echo $session_id; ?>;

        $.ajax({
            url: 'save_attendance_db.php',
            type: 'POST',
            data: {
                session_id: sessionId,
                student_id: studentId,
                status: status
            },
            success: function(res) {
                console.log('Saved:', res);
            },
            error: function() {
                alert('Error saving attendance. Check connection.');
            }
        });
    });
});
</script>
</body>
</html>
