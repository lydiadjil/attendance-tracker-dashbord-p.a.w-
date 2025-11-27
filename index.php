<?php
session_start();
require 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $pdo = getDBConnection();
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify Password
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['full_name'];

        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] == 'professor') {
            header("Location: prof_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login - Algiers University</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh;">
    
    <div class="card" style="width: 400px; text-align: center;">
        <h1>University Login</h1>
        
        <?php if ($error): ?>
            <p style="color: #e74c3c;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="margin-bottom: 15px; text-align: left;">
                <label>Email:</label>
                <input type="email" name="email" required placeholder="admin@algiers.dz" style="width:100%; padding: 10px;">
            </div>
            
            <div style="margin-bottom: 15px; text-align: left;">
                <label>Password:</label>
                <input type="password" name="password" required placeholder="123456" style="width:100%; padding: 10px;">
            </div>

            <button type="submit" class="btn" style="width:100%;">Login</button>
        </form>
        
        <div style="margin-top:20px; font-size:0.85em; color: #888; text-align: left;">
            <p><strong>Demo Accounts (Pass: 123456):</strong></p>
            <ul>
                <li>Admin: admin@algiers.dz</li>
                <li>Prof: prof@algiers.dz</li>
                <li>Student: ali@student.dz</li>
            </ul>
        </div>
    </div>

</body>
</html>