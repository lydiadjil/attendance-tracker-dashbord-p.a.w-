$(document).ready(function() {

    // Set default chart color for dark theme
    Chart.defaults.color = '#e2e8f0';

    // --- SELECTORS ---
    const addStudentForm = $('#add-student-form');
    const reportButton = $('#show-report-btn');
    const highlightButton = $('#highlight-btn');
    const resetButton = $('#reset-btn');
    const tableBody = $('#attendance-table tbody');

    // --- EVENT LISTENERS ---

    // Listener for the new student form submission
    addStudentForm.on('submit', function(event) {
        event.preventDefault(); // Stop the form from submitting the traditional way

        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();

        if (firstName === "" || lastName === "") {
            alert("Please enter both first and last name.");
            return;
        }

        // Create the HTML for the new row
        const newRowHTML = `
            <tr class="student-row">
                <td>${lastName}</td>
                <td>${firstName}</td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td><input type="checkbox" class="presence"></td>
                <td><input type="checkbox" class="participation"></td>
                <td class="absences-count"></td>
                <td class="participation-count"></td>
                <td class="message-cell"></td>
            </tr>`;

        // Append the new row and get the new jQuery object
        const newRow = $(newRowHTML).appendTo(tableBody);

        // Calculate its initial stats and color
        updateStudentStats(newRow[0]);

        // Clear the form fields
        $('#firstName').val('');
        $('#lastName').val('');
    });

    // Use event delegation for all interactive elements inside the table body
    // This ensures that new rows are interactive without re-binding events
    tableBody.on('change', 'input[type="checkbox"]', updateEverything);

    tableBody.on('mouseenter', '.student-row', function() {
        $(this).addClass('row-hover-highlight');
    }).on('mouseleave', '.student-row', function() {
        $(this).removeClass('row-hover-highlight');
    });

    tableBody.on('click', '.student-row', function() {
        const lastName = $(this).find('td:nth-child(1)').text();
        const firstName = $(this).find('td:nth-child(2)').text();
        const absences = $(this).find('.absences-count').text();
        alert(`Student: ${firstName} ${lastName}\n${absences}`);
    });

    // Button Listeners
    reportButton.on('click', generateSummaryReport);
    highlightButton.on('click', function() {
        $('.student-row').removeClass('highlight-green highlight-yellow highlight-red excellent-student-highlight').css('background-image', '');
        $('.student-row').each(function() {
            const absenceCount = parseInt($(this).find('.absences-count').text()) || 0;
            if (absenceCount < 3) {
                $(this).addClass('excellent-student-highlight');
            }
        });
    });
    resetButton.on('click', updateEverything);

    // --- INITIALIZATION ---
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
    let absenceCount = $row.find('.presence:not(:checked)').length;
    let participationCount = $row.find('.participation:checked').length;

    $row.find('.absences-count').text(`${absenceCount} Abs`);
    $row.find('.participation-count').text(`${participationCount} Par`);

    $row.removeClass('highlight-green highlight-yellow highlight-red excellent-student-highlight').css('background-image', '');

    if (absenceCount >= 5) {
        $row.addClass('highlight-red');
    } else if (absenceCount >= 3) {
        $row.addClass('highlight-yellow');
    } else {
        $row.addClass('highlight-green');
    }

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
        if (parseInt($(this).find('.absences-count').text()) === 0) presentStudents++;
        if (parseInt($(this).find('.participation-count').text()) > 0) participatedStudents++;
    });

    const absentStudents = totalStudents - presentStudents;

    $('#summary-text').html(`
        <p><strong>Total Students:</strong> ${totalStudents}</p>
        <p><strong>Students with Perfect Attendance:</strong> ${presentStudents}</p>
        <p><strong>Students who Participated:</strong> ${participatedStudents}</p>
    `);

    const ctx = document.getElementById('summary-chart').getContext('2d');
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

    $('#summary-report-section').removeClass('hidden');
}