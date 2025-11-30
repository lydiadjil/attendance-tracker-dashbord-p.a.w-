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

// 2. HANDLE FORM SUBMISSIONS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    
    // --- A. ADD USER ---
    if ($_POST['action'] == 'add_user') {
        $name = $_POST['full_name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $matricule = ($role == 'student') ? $_POST['matricule'] : NULL;
        $password = password_hash('123456', PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, matricule) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $matricule]);
            $message = "<p style='color:green;'>User added successfully!</p>";
            // NEW (Includes Group):
            $group = ($role == 'student') ? $_POST['group_name'] : NULL;
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, matricule, group_name) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $role, $matricule, $group]);
        } catch (PDOException $e) {
            $message = "<p style='color:red;'>Error: Email or Matricule already exists.</p>";
        }
    }

    // --- B. ADD COURSE (Assign Course to Prof) ---
    if ($_POST['action'] == 'add_course') {
        $course_name = $_POST['course_name'];
        $prof_id = $_POST['prof_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO courses (name, prof_id) VALUES (?, ?)");
            $stmt->execute([$course_name, $prof_id]);
            $message = "<p style='color:green;'>Course '$course_name' assigned successfully!</p>";
        } catch (PDOException $e) {
            $message = "<p style='color:red;'>Error adding course.</p>";
        }
    }

    // --- C. IMPORT CSV ---
    if ($_POST['action'] == 'import_csv') {
        if (is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
            $file = fopen($_FILES['csv_file']['tmp_name'], "r");
            fgetcsv($file); // Skip headers
            $count = 0;
            $pass = password_hash('123456', PASSWORD_DEFAULT);
            
            while (($row = fgetcsv($file)) !== FALSE) {
                // CSV Format: Name, Email, Matricule
                if (isset($row[0], $row[1])) {
                    $u_name = $row[0];
                    $u_email = $row[1];
                    $u_mat = $row[2] ?? NULL;
                    
                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, matricule) VALUES (?, ?, ?, 'student', ?)");
                        $stmt->execute([$u_name, $u_email, $pass, $u_mat]);
                        $count++;
                    } catch (Exception $e) { continue; } // Skip duplicates
                }
            }
            fclose($file);
            $message = "<p style='color:green;'>Imported $count students successfully!</p>";
        }
    }
}

// 3. HANDLE DELETE USER
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: admin_dashboard.php");
    exit;
}

// 4. FETCH DATA FOR UI
$countStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$countProfs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='professor'")->fetchColumn();

// Lists
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$professors = $pdo->query("SELECT id, full_name FROM users WHERE role='professor'")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT c.name, u.full_name as prof_name FROM courses c LEFT JOIN users u ON c.prof_id = u.id")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="exo2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #2c3e50; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #4a5568; }
        .stat-number { font-size: 2.5em; font-weight: bold; color: #4299e1; }
        .table-container { overflow-x: auto; }
        select, input { padding: 10px; background: #1a202c; color: white; border: 1px solid #4a5568; border-radius: 5px; }
        .grid-half { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid-half { grid-template-columns: 1fr; } }
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

    <!-- 1. STATISTICS -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Students</h3>
            <div class="stat-number"><?php echo $countStudents; ?></div>
        </div>
        <div class="stat-card">
            <h3>Professors</h3>
            <div class="stat-number"><?php echo $countProfs; ?></div>
        </div>
        <div class="stat-card">
            <div style="height:150px;"><canvas id="userChart"></canvas></div>
        </div>
    </div>

    <!-- NOTIFICATION AREA -->
    <?php if($message) echo "<div class='card' style='padding:15px; text-align:center; font-weight:bold;'>$message</div>"; ?>

    <div class="grid-half">
        <!-- 2. ADD USER & IMPORT -->
        <section class="card">
            <h2>Manage Users</h2>
            
            <!-- Add Single User -->
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <select name="role" id="roleSelect" onchange="toggleMatricule()" required>
                        <option value="student">Student</option>
                        <option value="professor">Professor</option>
                        <option value="admin">Admin</option>
                    </select>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="matricule" id="matriculeInput" placeholder="Matricule">
                    <!-- NEW GROUP INPUT -->
                    <input type="text" name="group_name" id="groupInput" placeholder="Group (e.g. G1)" style="display:none; flex:1; padding:10px; background:#1a202c; color:white; border:1px solid #4a5568; border-radius:5px;">
                    <button type="submit" class="btn">Add User</button>
                </div>
            </form>
            
            <!-- CSV Import -->
            <div style="margin-top:20px; border-top:1px solid #444; padding-top:15px;">
                <h4>Import Students (CSV)</h4>
                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items: center;">
                    <input type="hidden" name="action" value="import_csv">
                    <input type="file" name="csv_file" accept=".csv" required style="width: 100%;">
                    <button type="submit" class="btn btn-secondary">Upload</button>
                </form>
            </div>
        </section>

        <!-- 3. ASSIGN COURSES (New Feature) -->
        <section class="card">
            <h2>Assign Course</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_course">
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label>Course Name:</label>
                    <input type="text" name="course_name" placeholder="e.g. Web Development" required>
                    
                    <label>Assign to Professor:</label>
                    <select name="prof_id" required>
                        <option value="">Select Professor...</option>
                        <?php foreach ($professors as $prof): ?>
                            <option value="<?php echo $prof['id']; ?>">
                                Dr. <?php echo htmlspecialchars($prof['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn" style="background-color:#f1c40f; color:black; font-weight:bold;">Assign Course</button>
                </div>
            </form>

            <h4 style="margin-top:20px; border-bottom: 1px solid #444; padding-bottom: 5px;">Existing Courses</h4>
            <ul style="max-height:200px; overflow-y:auto; padding-left:20px;">
                <?php foreach ($courses as $c): ?>
                    <li>
                        <strong style="color:#4299e1;"><?php echo htmlspecialchars($c['name']); ?></strong> 
                        by <?php echo htmlspecialchars($c['prof_name'] ?? 'No Prof'); ?>
                    </li>
                <?php endforeach; ?>
                <?php if(empty($courses)) echo "<li>No courses assigned yet.</li>"; ?>
            </ul>
        </section>
    </div>

    <!-- 4. USER LIST TABLE -->
    <section class="card" style="margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2>User Management</h2>
            <button onclick="exportTableToExcel('userTable')" class="btn btn-secondary">Export to Excel</button>
        </div>
        <div class="table-container">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Matricule</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
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
                                <a href="?delete_id=<?php echo $user['id']; ?>" onclick="return confirm('Delete this user?')" style="color:#e74c3c; font-weight:bold;">Delete</a>
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
   function toggleMatricule() {
    const role = document.getElementById('roleSelect').value;
    const matInput = document.getElementById('matriculeInput');
    const grpInput = document.getElementById('groupInput');
    
    if (role === 'student') {
        matInput.style.display = 'block';
        matInput.required = true;
        grpInput.style.display = 'block'; // Show Group
    } else {
        matInput.style.display = 'none';
        matInput.required = false;
        grpInput.style.display = 'none'; // Hide Group
    }
}

    const ctx = document.getElementById('userChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Students', 'Professors'],
            datasets: [{ data: [<?php echo $countStudents; ?>, <?php echo $countProfs; ?>], backgroundColor: ['#4299e1', '#f1c40f'], borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });

    function exportTableToExcel(tableID, filename = 'users_list.xls'){
        var downloadLink;
        var dataType = 'application/vnd.ms-excel';
        var tableSelect = document.getElementById(tableID);
        var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
        
        filename = filename?filename+'.xls':'excel_data.xls';
        downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);
        
        if(navigator.msSaveOrOpenBlob){
            var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
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