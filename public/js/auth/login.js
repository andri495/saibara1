/**
 * ============================================
 * LOGIN PAGE JAVASCRIPT
 * ============================================
 *
 * Handles login form interactions and validation
 *
 * Features:
 * - Password visibility toggle
 * - Form validation
 * - Loading state management
 * - Remember me functionality
 */

(function () {
    "use strict";

    /**
     * Initialize when DOM is ready
     */
    document.addEventListener("DOMContentLoaded", function () {
        initPasswordToggle();
        initFormValidation();
        initRememberMe();
    });

    /**
     * Password Visibility Toggle
     * Allows users to show/hide password
     */
    function initPasswordToggle() {
        const toggleBtn = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");

        if (!toggleBtn || !passwordInput) return;

        toggleBtn.addEventListener("click", function () {
            const type =
                passwordInput.getAttribute("type") === "password"
                    ? "text"
                    : "password";
            passwordInput.setAttribute("type", type);

            // Update icon
            const icon = this.querySelector(".icon-eye");
            icon.textContent = type === "password" ? "👁️" : "🙈";
        });
    }

    /**
     * Form Validation
     * Client-side validation before submission
     */
    function initFormValidation() {
        const form = document.getElementById("loginForm");
        const emailInput = document.getElementById("email");
        const passwordInput = document.getElementById("password");
        const submitBtn = document.getElementById("loginBtn");

        if (!form) return;

        form.addEventListener("submit", function (e) {
            let isValid = true;

            // Clear previous errors
            clearErrors();

            // Validate email
            if (!emailInput.value.trim()) {
                showError(emailInput, "Email harus diisi");
                isValid = false;
            } else if (!isValidEmail(emailInput.value)) {
                showError(emailInput, "Format email tidak valid");
                isValid = false;
            }

            // Validate password
            if (!passwordInput.value) {
                showError(passwordInput, "Password harus diisi");
                isValid = false;
            } else if (passwordInput.value.length < 6) {
                showError(passwordInput, "Password minimal 6 karakter");
                isValid = false;
            }

            // Prevent submission if invalid
            if (!isValid) {
                e.preventDefault();
                return false;
            }

            // Show loading state
            showLoading(submitBtn);
        });
    }

    /**
     * Email Validation Helper
     * @param {string} email - Email to validate
     * @returns {boolean}
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Show Error Message
     * @param {HTMLElement} input - Input element
     * @param {string} message - Error message
     */
    function showError(input, message) {
        input.classList.add("is-invalid");

        const formGroup = input.closest(".form-group");
        if (!formGroup) return;

        const errorSpan = document.createElement("span");
        errorSpan.className = "form-error";
        errorSpan.textContent = message;
        formGroup.appendChild(errorSpan);
    }

    /**
     * Clear All Errors
     */
    function clearErrors() {
        const invalidInputs = document.querySelectorAll(".is-invalid");
        invalidInputs.forEach((input) => input.classList.remove("is-invalid"));

        const errorMessages = document.querySelectorAll(".form-error");
        errorMessages.forEach((error) => error.remove());
    }

    /**
     * Show Loading State
     * @param {HTMLElement} button - Submit button
     */
    function showLoading(button) {
        const btnText = button.querySelector(".btn-text");

        if (btnText) {
            btnText.innerHTML = '<span class="spinner"></span> Loading...';
        }

        button.disabled = true;
        button.style.opacity = "0.8";
    }

    /**
     * Remember Me Functionality
     * Saves email to localStorage if checked
     */
    function initRememberMe() {
        const rememberCheckbox = document.getElementById("remember");
        const emailInput = document.getElementById("email");

        if (!rememberCheckbox || !emailInput) return;

        // Load saved email
        const savedEmail = localStorage.getItem("rememberedEmail");
        if (savedEmail) {
            emailInput.value = savedEmail;
            rememberCheckbox.checked = true;
        }

        // Save email on form submit
        const form = document.getElementById("loginForm");
        if (form) {
            form.addEventListener("submit", function () {
                if (rememberCheckbox.checked) {
                    localStorage.setItem("rememberedEmail", emailInput.value);
                } else {
                    localStorage.removeItem("rememberedEmail");
                }
            });
        }
    }
})();
