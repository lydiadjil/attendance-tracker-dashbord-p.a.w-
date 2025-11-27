<?php
// add_student.php
// REPLACES the old JSON version.
// Now connects to the 'attendance_db' database.

require 'db_connect.php'; // Include your database connection

$message = "";
$status = "";

// 1. Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Get data (Using the names from your database table)
    // Note: If your HTML form still uses 'name', we map it to 'fullname'
    $matricule = $_POST['matricule'] ?? '';
    $fullname = $_POST['fullname'] ?? ''; 
    $group_id = $_POST['group_id'] ?? '';

    // 3. Validate
    if (empty($matricule) || empty($fullname) || empty($group_id)) {
        $message = "Error: All fields (Matricule, Full Name, Group) are required.";
        $status = "error";
    } else {
        $pdo = getDBConnection();

        if ($pdo) {
            try {
                // 4. PREPARE SQL (The Secure Way)
                $sql = "INSERT INTO students (matricule, fullname, group_id) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                // 5. EXECUTE
                $stmt->execute([$matricule, $fullname, $group_id]);

                $message = "Success! Student <strong>$fullname</strong> added to Database.";
                $status = "success";
            } catch (PDOException $e) {
                // Handle duplicate matricule or other DB errors
                if ($e->getCode() == 23000) {
                    $message = "Error: A student with Matricule <strong>$matricule</strong> already exists.";
                } else {
                    $message = "Database Error: " . $e->getMessage();
                }
                $status = "error";
            }
        } else {
            $message = "Error: Could not connect to the database.";
            $status = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student (DB)</title>
    <link rel="stylesheet" href="exo2.css">
    <style>
        .msg-container { text-align: center; margin-top: 20px; }
        .success { color: #2ecc71; }
        .error { color: #e74c3c; }
        form { max-width: 400px; margin: 0 auto; text-align: left; }
        label { font-weight: bold; display: block; margin-top: 10px; }
        input { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; color: white; background: #1a202c; border: 1px solid #4a5568;}
    </style>
</head>
<body>

<div class="container">
    <h1>Add New Student (Database)</h1>

    <!-- Display Status Message -->
    <?php if (!empty($message)): ?>
        <div class="card msg-container">
            <h3 class="<?php echo $status; ?>"><?php echo $message; ?></h3>
        </div>
    <?php endif; ?>

    <!-- The Form -->
    <section class="card">
        <form method="POST" action="add_student.php">
            <label>Matricule (ID):</label>
            <input type="text" name="matricule" required placeholder="Ex: 2023001">

            <label>Full Name:</label>
            <input type="text" name="fullname" required placeholder="Ex: samira belhocine">

            <label>Group ID:</label>
            <input type="text" name="group_id" required placeholder="Ex: A1">

            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" class="btn">Save to DB</button>
            </div>
        </form>
    </section>

    <div style="text-align: center;">
        <a href="list_students.php" class="btn btn-secondary">View All Students</a>
    </div>
</div>

</body>
</html>