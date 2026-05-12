/**
 * Scenario C: Client-side validation and Ajax email checking
 */
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("signup-form");
    const emailInput = document.getElementById("email");
    const emailStatus = document.getElementById("email-availability-status");

    // C.06: Validate the email using an Ajax request
    emailInput.addEventListener("blur", function() {
        const email = emailInput.value.trim();
        
        // Clear status if empty
        if (email === "") {
            emailStatus.textContent = "";
            return;
        }

        // 1. Basic Email Format Validation (Regex)
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            emailStatus.textContent = "Please enter a valid email format.";
            emailStatus.style.color = "red";
            return; // Stop here, do not send Ajax request
        }

        // 2. If format is valid, send Ajax request to check_email.php
        fetch("check_email.php?email=" + encodeURIComponent(email))
            .then(response => response.json())
            .then(data => {
                if (data.available === false) {
                    emailStatus.textContent = "This email is already taken.";
                    emailStatus.style.color = "red";
                } else {
                    emailStatus.textContent = "Email is available!";
                    emailStatus.style.color = "green";
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // C.05: Validate the data in the browser using JavaScript
    form.addEventListener("submit", function(event) {
        const password = document.getElementById("password").value;
        const passwordConfirmation = document.getElementById("password_confirmation").value;

        if (password.length < 6) {
            alert("Password must be at least 6 characters long.");
            event.preventDefault(); // Stop form submission
            return;
        }

        if (password !== passwordConfirmation) {
            alert("Passwords do not match!");
            event.preventDefault(); // Stop form submission
            return;
        }
    });
});