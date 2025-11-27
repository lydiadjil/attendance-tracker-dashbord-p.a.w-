document.addEventListener('DOMContentLoaded', function() {
    
    // Get the form by the ID we added in exo.php
    const form = document.getElementById('addStudentForm');

    // Check if form exists to prevent errors on other pages
    if (form) {
        form.addEventListener('submit', function(event) {
            // 1. Prevent the form from submitting immediately
            event.preventDefault();

            if (validateForm()) {
                // 2. If validation passes, manually submit the form to PHP
                console.log("Validation successful. Sending to add_student.php...");
                
                // --- THIS IS THE KEY MODIFICATION ---
                form.submit(); 
            }
        });
    }

    function validateForm() {
        let isValid = true;

        // --- 1. Select Elements (Matching your exo.php IDs) ---
        const studentIdInput = document.getElementById('student_id');
        const nameInput = document.getElementById('name');
        const groupInput = document.getElementById('group');

        // Select Error Spans
        const idError = document.getElementById('idError');
        const nameError = document.getElementById('nameError');
        const groupError = document.getElementById('groupError');

        // --- 2. Reset Error Messages ---
        idError.textContent = '';
        nameError.textContent = '';
        groupError.textContent = '';

        // Get values
        const studentId = studentIdInput.value.trim();
        const name = nameInput.value.trim();
        const group = groupInput.value.trim();

        // --- 3. Define Rules ---
        const numbersOnly = /^[0-9]+$/;
        // Allows letters and spaces (e.g., "John Doe")
        const lettersOnly = /^[A-Za-z\s]+$/; 

        // --- 4. Validation Logic ---

        // Validate Student ID
        if (studentId === '') {
            idError.textContent = 'Student ID is required.';
            isValid = false;
        } else if (!studentId.match(numbersOnly)) {
            idError.textContent = 'Student ID must contain only numbers.';
            isValid = false;
        }

        // Validate Full Name
        if (name === '') {
            nameError.textContent = 'Full Name is required.';
            isValid = false;
        } else if (!name.match(lettersOnly)) {
            nameError.textContent = 'Name must contain only letters and spaces.';
            isValid = false;
        }

        // Validate Group
        if (group === '') {
            groupError.textContent = 'Group is required.';
            isValid = false;
        }

        return isValid;
    }
});