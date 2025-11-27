<?php
session_start();
require 'db_connect.php';

// 1. SECURITY: Check if user is logged in AND is an Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$pdo = getDBConnection();
$message = "";

// 2. LOGIC: Add User (Student or Professor)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $matricule = ($role == 'student') ? $_POST['matricule'] : NULL;
    // Default password is '123456'
    $password = password_hash('123456', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, matricule) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role, $matricule]);
        $message = "<p style='color:green;'>User added successfully!</p>";
    } catch (PDOException $e) {
        $message = "<p style='color:red;'>Error: Email or Matricule already exists.</p>";
    }
}

// 3. LOGIC: Delete User
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: admin_dashboard.php"); // Refresh to clear URL
    exit;
}

// 4. DATA: Fetch Statistics
$countStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$countProfs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='professor'")->fetchColumn();

// 5. DATA: Fetch All Users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="exo2.css">
    <!-- Chart.js for Statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #2c3e50; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #4a5568; }
        .stat-number { font-size: 2.5em; font-weight: bold; color: #4299e1; }
        .table-container { overflow-x: auto; }
        select { padding: 10px; background: #1a202c; color: white; border: 1px solid #4a5568; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
        <h1>Admin Dashboard</h1>
        <div>
            <span style="margin-right: 15px;">Welcome, <?php echo $_SESSION['name']; ?></span>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>

    <!-- SECTION 1: STATISTICS (Charts) -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Students</h3>
            <div class="stat-number"><?php echo $countStudents; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Professors</h3>
            <div class="stat-number"><?php echo $countProfs; ?></div>
        </div>
        <div class="stat-card">
            <h3>User Distribution</h3>
            <!-- Canvas for Chart.js -->
            <div style="height:150px;">
                <canvas id="userChart"></canvas>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ADD USER FORM -->
    <section class="card">
        <h2>Add New User</h2>
        <?php echo $message; ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_user">
            <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                <select name="role" id="roleSelect" onchange="toggleMatricule()" required>
                    <option value="student">Student</option>
                    <option value="professor">Professor</option>
                    <option value="admin">Admin</option>
                </select>
                <input type="text" name="full_name" placeholder="Full Name" required style="flex:1;">
                <input type="email" name="email" placeholder="Email" required style="flex:1;">
                <input type="text" name="matricule" id="matriculeInput" placeholder="Matricule (Students Only)" style="flex:1;">
                <button type="submit" class="btn">Add User</button>
            </div>
        </form>
    </section>

    <!-- SECTION 3: USER LIST MANAGEMENT -->
    <section class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>User Management</h2>
            <!-- Simple Excel Export -->
            <button onclick="exportTableToExcel('userTable')" class="btn btn-secondary">Export to Excel</button>
        </div>
        
        <div class="table-container">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Matricule</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <span style="color: <?php echo $user['role'] == 'admin' ? '#e74c3c' : ($user['role'] == 'professor' ? '#f1c40f' : '#2ecc71'); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['matricule'] ? htmlspecialchars($user['matricule']) : '-'; ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete_id=<?php echo $user['id']; ?>" onclick="return confirm('Delete this user?')" style="color:#e74c3c; font-weight:bold; text-decoration:none;">Delete</a>
                            <?php else: ?>
                                (You)
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- SCRIPTS -->
<script>
    // 1. Toggle Matricule Input based on Role
    function toggleMatricule() {
        const role = document.getElementById('roleSelect').value;
        const input = document.getElementById('matriculeInput');
        if (role === 'student') {
            input.style.display = 'block';
            input.required = true;
        } else {
            input.style.display = 'none';
            input.required = false;
        }
    }

    // 2. Initialize Chart.js
    const ctx = document.getElementById('userChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Professors'],
            datasets: [{
                data: [<?php echo $countStudents; ?>, <?php echo $countProfs; ?>],
                backgroundColor: ['#4299e1', '#f1c40f'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    // 3. Simple Export to Excel Function
    function exportTableToExcel(tableID, filename = 'users_list.xls'){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
        
        filename = filename?filename+'.xls':'excel_data.xls';
        downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);
        
        if(navigator.msSaveOrOpenBlob){
            var blob = new Blob(['\ufeff', tableHTML], {
                type: dataType
            });
            navigator.msSaveOrOpenBlob( blob, filename);
        }else{
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename;
            downloadLink.click();
        }
    }
</script>

</body>
</html>