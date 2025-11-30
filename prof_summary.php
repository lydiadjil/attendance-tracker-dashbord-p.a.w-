<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: index.php"); exit;
}

$pdo = getDBConnection();
$prof_id = $_SESSION['user_id'];

// 1. Fetch Students linked to this Professor's Courses
// We calculate Total Sessions vs Present Sessions
$sql = "
    SELECT 
        u.full_name, 
        u.matricule,
        c.name as course_name,
        COUNT(a.id) as total_sessions,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'justified' THEN 1 ELSE 0 END) as justified_count
    FROM users u
    JOIN attendance a ON u.id = a.student_id
    JOIN sessions s ON a.session_id = s.id
    JOIN courses c ON s.course_id = c.id
    WHERE c.prof_id = ?
    GROUP BY u.id, c.id
    ORDER BY c.name, u.full_name
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$prof_id]);
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Attendance Summary</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h1>Attendance Analytics</h1>
        <a href="prof_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <section class="card">
        <h2>Student Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Matricule</th>
                    <th>Student Name</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Justified</th>
                    <th>Attendance %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $row): 
                    $total = $row['present_count'] + $row['absent_count'] + $row['justified_count'];
                    $percent = $total > 0 ? round(($row['present_count'] / $total) * 100) : 0;
                    $color = $percent < 50 ? '#e74c3c' : ($percent < 80 ? '#f1c40f' : '#2ecc71');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['matricule']); ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td style="color:#2ecc71; font-weight:bold;"><?php echo $row['present_count']; ?></td>
                    <td style="color:#e74c3c; font-weight:bold;"><?php echo $row['absent_count']; ?></td>
                    <td style="color:#3498db; font-weight:bold;"><?php echo $row['justified_count']; ?></td>
                    <td>
                        <div style="background:#333; width:100%; height:10px; border-radius:5px;">
                            <div style="background:<?php echo $color; ?>; width:<?php echo $percent; ?>%; height:100%; border-radius:5px;"></div>
                        </div>
                        <small><?php echo $percent; ?>%</small>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>