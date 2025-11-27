<?php
// close_session.php
require 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $session_id = $_POST['session_id'];

    if (!empty($session_id)) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                // Update status to 'closed'
                $sql = "UPDATE attendance_sessions SET status = 'closed' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$session_id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "<div style='color:green'><h3>Session #$session_id is now CLOSED.</h3></div>";
                } else {
                    $message = "<div style='color:orange'>Session ID not found or already closed.</div>";
                }
            } catch (PDOException $e) {
                $message = "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Close Session</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>
<div class="container">
    <h1>Close Class Session</h1>
    <?php echo $message; ?>
    
    <section class="card">
        <form method="POST" action="">
            <div style="margin-bottom:15px;">
                <label>Enter Session ID to Close:</label>
                <input type="number" name="session_id" required placeholder="eg 1" style="width:100%; padding:8px;">
            </div>
            <button type="submit" class="btn" style="background-color: #e74c3c;">Close Session</button>
        </form>
    </section>
    
    <!-- Helper: List Recent Sessions -->
    <section class="card">
        <h3>Recent Sessions</h3>
        <ul>
            <?php
            // Display last 5 sessions to make testing easier
            $pdo = getDBConnection();
            if ($pdo) {
                $stmt = $pdo->query("SELECT * FROM attendance_sessions ORDER BY id DESC LIMIT 5");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $statusColor = ($row['status'] == 'open') ? 'green' : 'red';
                    echo "<li>ID: <strong>{$row['id']}</strong> | Course: {$row['course_id']} | Status: <span style='color:$statusColor'>{$row['status']}</span></li>";
                }
            }
            ?>
        </ul>
    </section>
    
    <div style="text-align:center;">
        <a href="create_session.php" class="btn btn-secondary">Back to Create Session</a>
    </div>
</div>
</body>
</html>