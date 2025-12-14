<?php
require 'db_connect.php';

// --- PART A: EXPORT (Download List with Stats) ---
if (isset($_POST['export_stats'])) {
    $pdo = getDBConnection();
    
    // Set headers to download file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=student_stats_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // 1. CSV Column Headers
    fputcsv($output, ['Matricule', 'Full Name', 'Email', 'Total Sessions', 'Present', 'Absent', 'Participation Score']);

    // 2. Complex Query to calculate stats per student
    $sql = "
        SELECT 
            u.matricule, 
            u.full_name, 
            u.email,
            COUNT(DISTINCT s.id) as total_sessions,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN a.participation = 1 THEN 1 ELSE 0 END) as participation_score
        FROM users u
        LEFT JOIN attendance a ON u.id = a.student_id
        LEFT JOIN sessions s ON a.session_id = s.id
        WHERE u.role = 'student'
        GROUP BY u.id
    ";

    $stmt = $pdo->query($sql);
    
    // 3. Write Data to File
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

// --- PART B: IMPORT (Upload CSV) ---
$import_message = "";
if (isset($_POST['import_students']) && isset($_FILES['csv_file'])) {
    $pdo = getDBConnection();
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (is_uploaded_file($file)) {
        $handle = fopen($file, "r");
        
        // Skip the first row (Header)
        fgetcsv($handle); 
        
        $count = 0;
        $default_pass = password_hash('123456', PASSWORD_DEFAULT);

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // EXPECTED CSV FORMAT: Name, Email, Matricule
            $name = $data[0] ?? '';
            $email = $data[1] ?? '';
            $matricule = $data[2] ?? '';

            if ($name && $email) {
                try {
                    // Check if email exists
                    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check->execute([$email]);
                    
                    if (!$check->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, matricule) VALUES (?, ?, ?, 'student', ?)");
                        $stmt->execute([$name, $email, $default_pass, $matricule]);
                        $count++;
                    }
                } catch (Exception $e) {
                    continue; // Skip errors
                }
            }
        }
        fclose($handle);
        $import_message = "Successfully imported $count students!";
    }
}
?>