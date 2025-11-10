 const form = document.getElementById('addStudentForm');

    form.addEventListener('submit', function(event) {
      // Prevent the form from submitting by default
      event.preventDefault();

      if (validateForm()) {
        // If validation is successful, you can proceed with form submission
        // For this example, we'll just log a success message
        console.log("Form submitted successfully!");
        // To actually submit the form, you would use:
        // form.submit();
      }
    });

    function validateForm() {
      let isValid = true;

      // Clear previous error messages
      document.getElementById('studentIdError').textContent = '';
      document.getElementById('lastNameError').textContent = '';
      document.getElementById('firstNameError').textContent = '';
      document.getElementById('emailError').textContent = '';

      const studentId = document.getElementById('studentId').value.trim();
      const lastName = document.getElementById('lastName').value.trim();
      const firstName = document.getElementById('firstName').value.trim();
      const email = document.getElementById('email').value.trim();

      // Regular expressions for validation
      const numbersOnly = /^[0-9]+$/;
      const lettersOnly = /^[A-Za-z]+$/;
      const emailFormat = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

      // Student ID validation
      if (studentId === '') {
        document.getElementById('studentIdError').textContent = 'Student ID is required.';
        isValid = false;
      } else if (!studentId.match(numbersOnly)) {
        document.getElementById('studentIdError').textContent = 'Student ID must contain only numbers.';
        isValid = false;
      }

      // Last Name validation
      if (lastName !== '' && !lastName.match(lettersOnly)) {
        document.getElementById('lastNameError').textContent = 'Last Name must contain only letters.';
        isValid = false;
      }

      // First Name validation
      if (firstName !== '' && !firstName.match(lettersOnly)) {
        document.getElementById('firstNameError').textContent = 'First Name must contain only letters.';
        isValid = false;
      }

      // Email validation
      if (email === '') {
        document.getElementById('emailError').textContent = 'Email is required.';
        isValid = false;
      } else if (!email.match(emailFormat)) {
        document.getElementById('emailError').textContent = 'Please enter a valid email format (e.g., name@example.com).';
        isValid = false;
      }

      return isValid;
    }