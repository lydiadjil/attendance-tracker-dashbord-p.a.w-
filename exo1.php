<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Tracker Dashboard</title>
    <link rel="stylesheet" href="exo2.css">
</head>
<body>

    <div class="container">

        <h1>Student Dashboard</h1>

        <!-- SECTION 1: THE FORM (Updated to match instructions) -->
        <section id="add-student-section" class="card">
            <h2>Add New Student</h2>
            <form id="add-student-form" action="add_student.php" method="POST">
                <div class="form-row">
                    <!-- Field 1: Student ID -->
                    <div class="form-group">
                        <label for="student_id">Student ID:</label>
                        <input type="text" id="student_id" name="student_id" required>
                    </div>
                    <!-- Field 2: Name -->
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <!-- Field 3: Group -->
                    <div class="form-group">
                        <label for="group">Group:</label>
                        <input type="text" id="group" name="group" required>
                    </div>
                </div>
                <button type="submit" class="btn">Add Student</button>
            </form>
        </section>


        <!-- SECTION 2: THE TABLE -->
        <table id="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2">ID</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Group</th>
                    
                    <!-- Sessions S1-S6 -->
                    <th colspan="2">S1</th>
                    <th colspan="2">S2</th>
                    <th colspan="2">S3</th>
                    <th colspan="2">S4</th>
                    <th colspan="2">S5</th>
                    <th colspan="2">S6</th>
                    
                    <th rowspan="2">Absences</th>
                    <th rowspan="2">Participation</th>
                    <th rowspan="2">Message</th>
                </tr>
                <tr>
                    <th>P</th><th>Pa</th><th>P</th><th>Pa</th><th>P</th><th>Pa</th>
                    <th>P</th><th>Pa</th><th>P</th><th>Pa</th><th>P</th><th>Pa</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $file = 'students.json';
                $students = [];

                if (file_exists($file)) {
                    $json_data = file_get_contents($file);
                    $students = json_decode($json_data, true);
                }

                if (!empty($students) && is_array($students)) {
                    foreach ($students as $student) {
                        // Use the exact keys from instructions
                        $id = htmlspecialchars($student['student_id'] ?? '');
                        $name = htmlspecialchars($student['name'] ?? '');
                        $group = htmlspecialchars($student['group'] ?? '');

                        if ($id === '' && $name === '') continue;

                        echo '<tr class="student-row">';
                        echo "<td>$id</td>";     // Display ID
                        echo "<td>$name</td>";   // Display Name
                        echo "<td>$group</td>";  // Display Group
                        
                        // Generate Checkboxes
                        for ($i = 0; $i < 6; $i++) {
                            echo '<td><input type="checkbox" class="presence"></td>';
                            echo '<td><input type="checkbox" class="participation"></td>';
                        }

                        echo '<td class="absences-count">0</td>';
                        echo '<td class="participation-count">0%</td>';
                        echo '<td class="message-cell"></td>';
                        echo '</tr>';
                    }
                } else {
                    echo "<tr><td colspan='18' style='text-align:center; padding: 20px;'>No students found. Add one above!</td></tr>";
                }
                ?>
            </tbody>
        </table>
        
        <!-- Controls -->
        <div class="center-container">
            <button id="show-report-btn" class="btn">Show Report</button>
            <button id="highlight-btn" class="btn btn-secondary">Highlight Excellent</button>
            <button id="reset-btn" class="btn btn-tertiary">Reset View</button>
        </div>

        <!-- Report Section -->
        <section id="summary-report-section" class="hidden card">
            <h2>Summary Report</h2>
            <div class="report-content">
                <div id="summary-text"></div>
                <div class="summary-chart-container">
                    <canvas id="summary-chart"></canvas>
                </div>
            </div>
        </section>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script1.2.js" defer></script>

</body>
</html>