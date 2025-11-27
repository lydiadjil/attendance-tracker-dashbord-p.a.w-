<?php
session_start();
require 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: index.php");
    exit;
}

$prof_id = $_SESSION['user_id'];
$pdo = getDBConnection();
$message = "";

// 2. Logic: Create New Session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_session'])) {
    $course_id = $_POST['course_id'];
    $date = date('Y-m-d'); // Today
    
    try {
        $stmt = $pdo->prepare("INSERT INTO sessions (course_id, session_date, status) VALUES (?, ?, 'open')");
        $stmt->execute([$course_id, $date]);
        $message = "<p style='color:green'>Session created successfully!</p>";
    } catch (PDOException $e) {
        $message = "<p style='color:red'>Error creating session.</p>";
    }
}

// 3. Data: Fetch Courses for this Professor
$courses = $pdo->prepare("SELECT * FROM courses WHERE prof_id = ?");
$courses->execute([$prof_id]);
$myCourses = $courses->fetchAll(PDO::FETCH_ASSOC);

// 4. Data: Fetch My Sessions
$sql = "SELECT s.*, c.name as course_name 
        FROM sessions s 
        JOIN courses c ON s.course_id = c.id 
        WHERE c.prof_id = ? 
        ORDER BY s.session_date DESC, s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$prof_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Professor Dashboard</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Professor Dashboard</h1>
        <div>
            <span>Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <!-- CREATE SESSION CARD -->
    <section class="card">
        <h2>Start New Class Session</h2>
        <?php echo $message; ?>
        <form method="POST">
            <input type="hidden" name="create_session" value="1">
            <div style="display:flex; gap:10px;">
                <select name="course_id" required style="padding:10px; flex:1; background:#1a202c; color:white; border:1px solid #4a5568;">
                    <option value="">Select Course...</option>
                    <?php foreach ($myCourses as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Create Session (Today)</button>
            </div>
        </form>
    </section>

    <!-- SESSION LIST -->
    <section class="card">
        <h2>My Sessions</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?php echo $s['id']; ?></td>
                    <td><?php echo $s['session_date']; ?></td>
                    <td><?php echo htmlspecialchars($s['course_name']); ?></td>
                    <td>
                        <span style="color: <?php echo $s['status']=='open' ? '#2ecc71' : '#e74c3c'; ?>">
                            <?php echo strtoupper($s['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="session_view.php?id=<?php echo $s['id']; ?>" class="btn" style="padding:5px 10px; font-size:0.8em;">
                            Take Attendance
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>