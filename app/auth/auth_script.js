// rma9: waits until the registration page has fully loaded before running the JavaScript code.
// This makes sure all buttons and form fields exist before trying to use them.
document.addEventListener("DOMContentLoaded", () => {

    // rma9: finds every password visibility button on the registration page.
    // This allows both the password and confirm password fields to use the same code.
    const passwordToggleButtons =
        document.querySelectorAll(".password-toggle");

    // rma9: loops through each password toggle button and waits for the user to click it.
    passwordToggleButtons.forEach((button) => {

        // rma9: runs this code whenever the user clicks the eye icon.
        button.addEventListener("click", () => {

            // rma9: gets the ID of the password field that belongs to the button that was clicked.
            const targetId = button.dataset.target;

            // rma9: finds the matching password input using the ID above.
            const passwordInput = document.getElementById(targetId);

            // rma9: stops the function if the password field cannot be found.
            if (!passwordInput) {
                return;
            }

            // rma9: checks whether the password is currently hidden.
            const isCurrentlyHidden =
                passwordInput.type === "password";

            // rma9: switches between hiding and showing the password text.
            passwordInput.type =
                isCurrentlyHidden ? "text" : "password";

            // rma9: updates the accessibility label so screen readers know the current button action.
            button.setAttribute(
                "aria-label",
                isCurrentlyHidden
                    ? "Hide password"
                    : "Show password"
            );
        });
    });

    // rma9: finds the registration form so password validation can run before submission.
    const registrationForm =
        document.getElementById("registration-form");

    // rma9: finds the main password field.
    const password =
        document.getElementById("password");

    // rma9: finds the confirm password field.
    const confirmPassword =
        document.getElementById("confirm_password");

    // rma9: checks whether both password fields contain the same value.
    // If they do not match, the browser displays a validation message and prevents submission.
    function validateMatchingPasswords() {

        // rma9: exits the function if either password field cannot be found.
        if (!password || !confirmPassword) {
            return;
        }

        // rma9: only checks once the confirm password field has a value.
        // If the passwords do not match, a custom validation message is shown.
        if (
            confirmPassword.value !== "" &&
            password.value !== confirmPassword.value
        ) {
            confirmPassword.setCustomValidity(
                "Passwords do not match."
            );
        } else {

            // rma9: clears the validation message once both passwords match.
            confirmPassword.setCustomValidity("");
        }
    }

    // rma9: checks the passwords every time the user types in the password field.
    password?.addEventListener(
        "input",
        validateMatchingPasswords
    );

    // rma9: checks the passwords every time the user types in the confirm password field.
    confirmPassword?.addEventListener(
        "input",
        validateMatchingPasswords
    );

    // rma9: performs one final password comparison before the form is submitted.
    // If the passwords do not match, the browser blocks the registration request.
    registrationForm?.addEventListener("submit", () => {
        validateMatchingPasswords();
    });
});