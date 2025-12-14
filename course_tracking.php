<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'professor') {
    header("Location: index.php"); exit;
}

$pdo = getDBConnection();
$course_id = $_GET['course_id'] ?? 0;

// 1. Get Course Info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();
if (!$course) die("Course not found. Please go back to dashboard and select a course.");

// 2. Get Last 6 Sessions (Dynamic S1-S6)
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE course_id = ? ORDER BY session_date ASC LIMIT 6");
$stmt->execute([$course_id]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Get All Students
$students = $pdo->query("SELECT * FROM users WHERE role='student' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Get Attendance Data Map
// Map[Student_ID][Session_ID] = {status, participation}
$attMap = [];
$attQuery = $pdo->prepare("
    SELECT a.* 
    FROM attendance a 
    JOIN sessions s ON a.session_id = s.id 
    WHERE s.course_id = ?
");
$attQuery->execute([$course_id]);
while ($row = $attQuery->fetch(PDO::FETCH_ASSOC)) {
    $attMap[$row['student_id']][$row['session_id']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tracking: <?php echo htmlspecialchars($course['name']); ?></title>
    <link rel="stylesheet" href="exo2.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="container" style="max-width: 1400px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>Tracking: <?php echo htmlspecialchars($course['name']); ?></h1>
        <a href="prof_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <!-- MAIN TRACKING TABLE -->
    <section class="card">
        <div style="overflow-x:auto;">
            <table id="attendance-table">
                <thead>
                    <tr>
                        <th rowspan="2">Matricule</th>
                        <th rowspan="2">Student Name</th>
                        
                        <!-- DYNAMIC SESSION HEADERS -->
                        <?php 
                        $colCount = 0;
                        foreach ($sessions as $s): 
                            $colCount++;
                        ?>
                            <th colspan="2" title="<?php echo $s['session_date']; ?>">
                                S<?php echo $colCount; ?><br>
                                <span style="font-size:0.7em"><?php echo date('M d', strtotime($s['session_date'])); ?></span>
                            </th>
                        <?php endforeach; ?>

                        <!-- Fill empty headers if less than 6 sessions -->
                        <?php for($i=$colCount; $i<6; $i++): ?>
                            <th colspan="2">S<?php echo $i+1; ?></th>
                        <?php endfor; ?>

                        <th rowspan="2">Absences</th>
                        <th rowspan="2">Participation</th>
                        <th rowspan="2">Message</th>
                    </tr>
                    <tr>
                        <!-- SUB HEADERS P/Pa -->
                        <?php for($i=0; $i<6; $i++): ?>
                            <th>P</th><th>Pa</th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr class="student-row">
                            <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>

                            <!-- LOOP SESSIONS -->
                            <?php 
                            foreach ($sessions as $s): 
                                $s_id = $s['id'];
                                $stu_id = $student['id'];
                                
                                // Check DB Data
                                $isPresent = false; 
                                $isPart = false;

                                if (isset($attMap[$stu_id][$s_id])) {
                                    $record = $attMap[$stu_id][$s_id];
                                    if ($record['status'] == 'present') $isPresent = true;
                                    if ($record['participation'] == 1) $isPart = true;
                                }
                            ?>
                                <!-- PRESENCE CHECKBOX -->
                                <td>
                                    <input type="checkbox" class="presence" 
                                           data-sid="<?php echo $s_id; ?>" 
                                           data-stuid="<?php echo $stu_id; ?>"
                                           <?php echo $isPresent ? 'checked' : ''; ?>>
                                </td>
                                <!-- PARTICIPATION CHECKBOX -->
                                <td>
                                    <input type="checkbox" class="participation" 
                                           data-sid="<?php echo $s_id; ?>" 
                                           data-stuid="<?php echo $stu_id; ?>"
                                           <?php echo $isPart ? 'checked' : ''; ?>>
                                </td>
                            <?php endforeach; ?>

                            <!-- Fill empty cells if less than 6 sessions -->
                            <?php for($i=$colCount; $i<6; $i++): ?>
                                <td>-</td><td>-</td>
                            <?php endfor; ?>

                            <!-- STATS CELLS (Calculated by JS) -->
                            <td class="absences-count">0</td>
                            <td class="participation-count">0%</td>
                            <td class="message-cell"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- REPORT CONTROLS -->
    <div class="center-container">
        <button id="show-report-btn" class="btn">Show Statistics Report</button>
        <button id="highlight-btn" class="btn btn-secondary">Highlight Excellent</button>
    </div>

    <!-- REPORT SECTION -->
    <section id="summary-report-section" class="hidden card">
        <h2>Performance Report</h2>
        <div class="report-content" style="display:flex; align-items:center; justify-content:center;">
            <div id="summary-text" style="margin-right:50px;"></div>
            <div class="summary-chart-container" style="width:300px; height:300px;">
                <canvas id="summary-chart"></canvas>
            </div>
        </div>
    </section>
</div>

<!-- JAVASCRIPT LOGIC -->
<script>
$(document).ready(function() {
    
    // 1. AJAX SAVING
    // When any checkbox is clicked, save to DB immediately 
    //JAX (Saving data without reloading)
   // When check a box, don't want the whole page to refresh.
    $('input[type="checkbox"]').change(function() {
        const checkbox = $(this);
        const type = checkbox.hasClass('presence') ? 'presence' : 'participation';
        const value = checkbox.is(':checked');
        const sid = checkbox.data('sid');
        const stuid = checkbox.data('stuid');

        // Prevent clicking if it's an empty column (no data-sid)
        if (!sid) return; 

        $.ajax({
            url: 'save_matrix.php',
            type: 'POST',
            data: { session_id: sid, student_id: stuid, type: type, value: value },
            success: function(res) { console.log('Saved'); },
            error: function() { alert('Connection error'); }
        });

        // Recalculate stats for this row visual only
        updateRowStats(checkbox.closest('tr'));
    });

    // 2. CALCULATE STATS
    function updateRowStats(row) {
        let totalSessions = <?php echo count($sessions); ?>;
        if (totalSessions === 0) return;

        let presentCount = row.find('.presence:checked').length;
        let partCount = row.find('.participation:checked').length;
        let absCount = totalSessions - presentCount;

        // Visual Updates
        row.find('.absences-count').text(absCount);
        row.find('.participation-count').text(partCount + " / " + totalSessions); // Simple count

        // Message Logic
        let msg = "";
        if (absCount >= 3) msg = "⚠️ Risk";
        else if (partCount == totalSessions) msg = "🌟 Excellent";
        else msg = "Good";
        
        row.find('.message-cell').text(msg);

        // Highlight Logic
        row.removeClass('highlight-green highlight-yellow highlight-red');
        if (absCount >= 3) row.addClass('highlight-red');
        else if (partCount >= totalSessions - 1) row.addClass('highlight-green');
    }

    // Initialize stats on load
    $('.student-row').each(function() {
        updateRowStats($(this));
    });

    // 3. SHOW REPORT (CHART JS)
    $('#show-report-btn').click(function() {
        let totalStudents = $('.student-row').length;
        let perfectPart = 0;
        let atRisk = 0;

        $('.student-row').each(function() {
            let msg = $(this).find('.message-cell').text();
            if (msg.includes("Excellent")) perfectPart++;
            if (msg.includes("Risk")) atRisk++;
        });

        let others = totalStudents - perfectPart - atRisk;

        $('#summary-text').html(`
            <p><strong>Total Students:</strong> ${totalStudents}</p>
            <p style="color:#2ecc71"><strong>High Participation:</strong> ${perfectPart}</p>
            <p style="color:#e74c3c"><strong>At Risk (Absences):</strong> ${atRisk}</p>
        `);

        $('#summary-report-section').removeClass('hidden');

        // Draw Chart
        const ctx = document.getElementById('summary-chart').getContext('2d');
        if (window.myChart) window.myChart.destroy();
        
        window.myChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Excellent', 'At Risk', 'Average'],
                datasets: [{
                    data: [perfectPart, atRisk, others],
                    backgroundColor: ['#2ecc71', '#e74c3c', '#3498db']
                }]
            }
        });
    });

    // 4. HIGHLIGHT BUTTON
    $('#highlight-btn').click(function() {
        $('.student-row').each(function() {
            if ($(this).find('.message-cell').text().includes("Excellent")) {
                $(this).css('background-color', 'rgba(46, 204, 113, 0.2)');
            } else {
                $(this).css('background-color', '');
            }
        });
    });
});
</script>

</body>
</html>