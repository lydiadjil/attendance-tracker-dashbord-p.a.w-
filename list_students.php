<?php
require 'db_connect.php';

$pdo = getDBConnection();
$students = [];

if ($pdo) {
    // Get all students from database
    $stmt = $pdo->query("SELECT * FROM students ORDER BY id DESC");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student List</title>
    <link rel="stylesheet" href="exo2.css">
    <style>
        .action-link { margin: 0 5px; text-decoration: none; font-weight: bold; }
        .edit { color: #3498db; }
        .delete { color: #e74c3c; }
    </style>
</head>
<body>
<div class="container">
    <h1>Database Student List</h1>
    <a href="add_student.php" class="btn">Add New Student</a>
    <br><br>
    
    <section class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Matricule</th>
                    <th>Group</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['id']); ?></td>
                        <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                        <td><?php echo htmlspecialchars($student['group_id']); ?></td>
                        <td>
                            <!-- Links to Update and Delete scripts -->
                            <a href="update_student.php?id=<?php echo $student['id']; ?>" class="action-link edit">Edit</a>
                            <a href="delete_student.php?id=<?php echo $student['id']; ?>" class="action-link delete" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No students in database.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>