$(document).ready(function() {

    // Set default chart color for dark theme
    Chart.defaults.color = '#e2e8f0';

    // --- SELECTORS ---
    const tableBody = $('#attendance-table tbody');
    const reportButton = $('#show-report-btn');
    const highlightButton = $('#highlight-btn');
    const resetButton = $('#reset-btn');

    // --- REMOVED: The Form Submit Listener ---
    // We removed the $('#add-student-form').on('submit'...) block.
    // Now, the HTML <form> tag will send the data to add_student.php automatically.

    // --- EVENT LISTENERS ---

    // 1. Listen for changes on ANY checkbox (even new ones from PHP)
    tableBody.on('change', 'input[type="checkbox"]', function() {
        // Find the specific row that changed and update only that student
        const row = $(this).closest('tr');
        updateStudentStats(row);
    });

    // 2. Hover effects
    tableBody.on('mouseenter', '.student-row', function() {
        $(this).addClass('row-hover-highlight');
    }).on('mouseleave', '.student-row', function() {
        $(this).removeClass('row-hover-highlight');
    });

    // 3. Click row to see details
    tableBody.on('click', '.student-row', function(e) {
        // Prevent alert when clicking checkboxes
        if ($(e.target).is('input')) return;

        const lastName = $(this).find('td:nth-child(1)').text();
        const firstName = $(this).find('td:nth-child(2)').text();
        const absences = $(this).find('.absences-count').text();
        // alert(`Student: ${firstName} ${lastName}\n${absences}`); 
        // Commented out alert because it can be annoying, uncomment if needed
    });

    // 4. Button Listeners
    reportButton.on('click', generateSummaryReport);
    
    highlightButton.on('click', function() {
        // Clear old highlights
        $('.student-row').removeClass('highlight-green highlight-yellow highlight-red excellent-student-highlight').css('background-image', '');
        
        // Add specific highlight for excellent students (< 3 absences)
        $('.student-row').each(function() {
            const absenceCount = parseInt($(this).find('.absences-count').text()) || 0;
            if (absenceCount < 3) {
                $(this).addClass('excellent-student-highlight');
            }
        });
    });

    resetButton.on('click', function() {
        // Re-run the main calculation to reset colors based on attendance
        updateEverything();
    });

    // --- INITIALIZATION ---
    // Run this immediately to calculate stats for students loaded from PHP
    updateEverything();
});


// --- CORE FUNCTIONS ---

function updateEverything() {
    $('.student-row').each(function() {
        updateStudentStats(this);
    });
}

function updateStudentStats(row) {
    const $row = $(row);
    
    // Count unchecked "presence" boxes
    let absenceCount = $row.find('.presence:not(:checked)').length;
    // Count checked "participation" boxes
    let participationCount = $row.find('.participation:checked').length;

    // Update text
    $row.find('.absences-count').text(`${absenceCount} Abs`);
    $row.find('.participation-count').text(`${participationCount} Par`);

    // Reset Classes
    $row.removeClass('highlight-green highlight-yellow highlight-red excellent-student-highlight').css('background-image', '');

    // Apply Logic
    if (absenceCount >= 5) {
        $row.addClass('highlight-red');
    } else if (absenceCount >= 3) {
        $row.addClass('highlight-yellow');
    } else {
        $row.addClass('highlight-green');
    }

    // Update Message
    const messageCell = $row.find('.message-cell');
    if (absenceCount >= 5) {
        messageCell.text('Excluded – too many absences');
    } else if (absenceCount >= 3) {
        messageCell.text(participationCount < 3 ? 'Warning – low attendance & participation' : 'Warning – low attendance');
    } else {
        messageCell.text(participationCount >= 4 ? 'Good attendance – Excellent participation' : 'Good attendance');
    }
}

function generateSummaryReport() {
    const allStudentRows = $('.student-row');
    const totalStudents = allStudentRows.length;
    let presentStudents = 0, participatedStudents = 0;

    allStudentRows.each(function() {
        // Parse the text "0 Abs" -> integer 0
        const absText = $(this).find('.absences-count').text();
        const parText = $(this).find('.participation-count').text();
        
        if (parseInt(absText) === 0) presentStudents++;
        if (parseInt(parText) > 0) participatedStudents++;
    });

    const absentStudents = totalStudents - presentStudents;

    $('#summary-text').html(`
        <p><strong>Total Students:</strong> ${totalStudents}</p>
        <p><strong>Students with Perfect Attendance:</strong> ${presentStudents}</p>
        <p><strong>Students who Participated:</strong> ${participatedStudents}</p>
    `);

    // Chart Logic
    const canvas = document.getElementById('summary-chart');
    // Ensure canvas exists before drawing
    if (canvas) {
        const ctx = canvas.getContext('2d');
        if (window.mySummaryChart) window.mySummaryChart.destroy();

        window.mySummaryChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Perfect Attendance', 'Have Absences'],
                datasets: [{
                    data: [presentStudents, absentStudents],
                    backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(255, 99, 132, 0.7)'],
                    borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Student Attendance Breakdown' }
                }
            }
        });
        
        // Show the section
        $('#summary-report-section').removeClass('hidden');
    }
}