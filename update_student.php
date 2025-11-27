<?php
require 'db_connect.php';
$message = "";
$student = null;
$pdo = getDBConnection();

// 1. Get the student data to fill the form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 2. Handle the update submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $fullname = $_POST['fullname'];
    $matricule = $_POST['matricule'];
    $group_id = $_POST['group_id'];

    try {
        $sql = "UPDATE students SET fullname=?, matricule=?, group_id=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullname, $matricule, $group_id, $id]);
        
        // Redirect back to list
        header("Location: list_students.php");
        exit;
    } catch (PDOException $e) {
        $message = "<div style='color:red'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>
<div class="container">
    <h1>Edit Student</h1>
    <?php echo $message; ?>

    <?php if ($student): ?>
    <section class="card">
        <form method="POST" action="">
            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
            
            <div style="margin-bottom:15px;">
                <label>Full Name:</label>
                <input type="text" name="fullname" value="<?php echo $student['fullname']; ?>" required style="width:100%; padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Matricule:</label>
                <input type="text" name="matricule" value="<?php echo $student['matricule']; ?>" required style="width:100%; padding:8px;">
            </div>
            <div style="margin-bottom:15px;">
                <label>Group ID:</label>
                <input type="text" name="group_id" value="<?php echo $student['group_id']; ?>" required style="width:100%; padding:8px;">
            </div>
            <button type="submit" class="btn">Update Student</button>
            <a href="list_students.php" class="btn btn-secondary">Cancel</a>
        </form>
    </section>
    <?php else: ?>
        <p>Student not found.</p>
    <?php endif; ?>
</div>
</body>
</html>