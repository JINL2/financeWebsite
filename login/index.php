<?php
/**
 * Financial Management System - Login Page
 * Modern responsive login interface
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Financial Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="logo-container">
                    <i class="bi bi-graph-up-arrow logo-icon"></i>
                    <h1 class="logo-text">Finance</h1>
                </div>
                <p class="login-subtitle">Sign in to your account</p>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope me-2"></i>Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock me-2"></i>Password
                    </label>
                    <div class="password-input-container">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Enter your password"
                            required
                        >
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="error-message" style="display: none;"></div>

                <!-- Loading State -->
                <div id="loadingState" class="loading-state" style="display: none;">
                    <div class="spinner"></div>
                    <span>Signing in...</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                </button>

                <!-- Remember Me & Forgot Password -->
                <div class="login-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
            </form>

            <!-- Footer -->
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Financial Management System. All rights reserved.</p>
            </div>
        </div>

        <!-- Background Animation -->
        <div class="background-animation">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Login form functionality - Enhanced version
        function initializeLogin() {
            console.log('Initializing login functionality...');
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const errorMessage = document.getElementById('errorMessage');
            const loadingState = document.getElementById('loadingState');
            const loginBtn = document.getElementById('loginBtn');

            // Password toggle functionality
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
            });

            // Form validation
            function validateForm() {
                let isValid = true;
                
                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    showError('Please enter a valid email address');
                    emailInput.focus();
                    return false;
                }

                // Password validation
                if (passwordInput.value.length < 6) {
                    showError('Password must be at least 6 characters long');
                    passwordInput.focus();
                    return false;
                }

                return isValid;
            }

            // Show error message
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.style.display = 'block';
                errorMessage.classList.add('show');
                setTimeout(() => {
                    errorMessage.classList.remove('show');
                }, 100);
            }

            // Hide error message
            function hideError() {
                errorMessage.style.display = 'none';
                errorMessage.classList.remove('show');
            }

            // Show loading state
            function showLoading() {
                loginBtn.style.display = 'none';
                loadingState.style.display = 'flex';
            }

            // Hide loading state
            function hideLoading() {
                loginBtn.style.display = 'block';
                loadingState.style.display = 'none';
            }

            // Form submission
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                hideError();
                
                if (!validateForm()) {
                    return;
                }

                showLoading();

                const formData = new FormData();
                formData.append('email', emailInput.value.trim());
                formData.append('password', passwordInput.value);

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        body: formData
                    });

                    let responseText = await response.text();
                    
                    // Remove PHP warnings from response text
                    const jsonStart = responseText.indexOf('{');
                    if (jsonStart > 0) {
                        responseText = responseText.substring(jsonStart);
                    }
                    
                    const result = JSON.parse(responseText);

                    if (result.success) {
                        // Success - redirect to dashboard
                        window.location.href = `../dashboard/?user_id=${result.user_id}&company_id=${result.company_id}`;
                    } else {
                        // Show error
                        showError(result.message || 'Login failed. Please try again.');
                        hideLoading();
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    showError('Connection error. Please check your internet and try again.');
                    hideLoading();
                }
            });

            // Input event listeners for real-time validation
            emailInput.addEventListener('input', function() {
                if (errorMessage.style.display === 'block') {
                    hideError();
                }
            });

            passwordInput.addEventListener('input', function() {
                if (errorMessage.style.display === 'block') {
                    hideError();
                }
            });

            // Focus on email input on page load
            if (emailInput) {
                emailInput.focus();
            }
            
            console.log('Login functionality initialized successfully');
        }
        
        // Multiple initialization methods for better compatibility
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeLogin);
        } else {
            initializeLogin();
        }
        
        // Backup initialization
        window.addEventListener('load', function() {
            // Re-initialize if elements weren't found before
            const form = document.getElementById('loginForm');
            if (form && !form.hasAttribute('data-initialized')) {
                console.log('Re-initializing login functionality...');
                initializeLogin();
                form.setAttribute('data-initialized', 'true');
            }
        });
    </script>
</body>
</html>