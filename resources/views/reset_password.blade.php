<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Reset Password</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/icons/ic_ps_saibara.png') }}">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
    }

    .container {
      background-color: white;
      width: 100%;
      max-width: 420px;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .lock-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      font-size: 28px;
      color: white;
    }

    h2 {
      font-size: 24px;
      color: #333;
      margin-bottom: 8px;
    }

    .subtitle {
      color: #666;
      font-size: 14px;
      line-height: 1.5;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      color: #333;
      font-size: 14px;
      font-weight: 500;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      outline: none;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    input[type="email"]:disabled {
      background-color: #f5f5f5;
      cursor: not-allowed;
    }

    .password-requirements {
      font-size: 12px;
      color: #666;
      margin-top: 6px;
    }

    .error-message {
      background-color: #fee;
      border: 1px solid #fcc;
      color: #c33;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      display: none;
    }

    .error-message.show {
      display: block;
    }

    .success-message {
      background-color: #efe;
      border: 1px solid #cfc;
      color: #3c3;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      display: none;
    }

    .success-message.show {
      display: block;
    }

    .btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 10px;
    }

    .btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn:active:not(:disabled) {
      transform: translateY(0);
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 0.8s linear infinite;
      margin-right: 8px;
      vertical-align: middle;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
      color: #667eea;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
    }

    .back-link a:hover {
      text-decoration: underline;
    }

    .password-match-indicator {
      font-size: 12px;
      margin-top: 6px;
      display: none;
    }

    .password-match-indicator.match {
      color: #3c3;
      display: block;
    }

    .password-match-indicator.no-match {
      color: #c33;
      display: block;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="lock-icon">🔒</div>
      <h2>Reset Your Password</h2>
      <p class="subtitle">Enter your new password below</p>
    </div>

    <div id="errorMessage" class="error-message"></div>
    <div id="successMessage" class="success-message"></div>

    <form id="resetPasswordForm">
      <input type="hidden" id="token" name="token" value="{{ $token }}">
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input 
          type="email" 
          id="email" 
          name="email" 
          value="{{ $email }}" 
          readonly
          required
        />
      </div>

      <div class="form-group">
        <label for="password">New Password</label>
        <input 
          type="password" 
          id="password" 
          name="password" 
          placeholder="Enter new password"
          minlength="6"
          required
        />
        <div class="password-requirements">
          Must be 6 characters long
        </div>
      </div>

      <div class="form-group">
        <label for="password_confirmation">Confirm New Password</label>
        <input 
          type="password" 
          id="password_confirmation" 
          name="password_confirmation" 
          placeholder="Confirm new password"
          minlength="6"
          required
        />
        <div id="passwordMatchIndicator" class="password-match-indicator"></div>
      </div>

      <button type="submit" class="btn" id="submitBtn">
        Reset Password
      </button>
    </form>

  </div>

  <script>
    const form = document.getElementById('resetPasswordForm');
    const submitBtn = document.getElementById('submitBtn');
    const errorMessage = document.getElementById('errorMessage');
    const successMessage = document.getElementById('successMessage');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');
    const matchIndicator = document.getElementById('passwordMatchIndicator');

    // Password match indicator
    function checkPasswordMatch() {
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;

      if (confirmPassword.length === 0) {
        matchIndicator.className = 'password-match-indicator';
        matchIndicator.textContent = '';
        return;
      }

      if (password === confirmPassword) {
        matchIndicator.className = 'password-match-indicator match';
        matchIndicator.textContent = '✓ Passwords match';
      } else {
        matchIndicator.className = 'password-match-indicator no-match';
        matchIndicator.textContent = '✗ Passwords do not match';
      }
    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    // Form submission
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Hide previous messages
      errorMessage.classList.remove('show');
      successMessage.classList.remove('show');

      // Validate password match
      if (passwordInput.value !== confirmPasswordInput.value) {
        errorMessage.textContent = 'Passwords do not match';
        errorMessage.classList.add('show');
        return;
      }

      // Validate password length
      if (passwordInput.value.length < 6) {
        errorMessage.textContent = 'Password must be at least 6 characters long';
        errorMessage.classList.add('show');
        return;
      }

      // Disable button and show loading
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span>Resetting...';

      const formData = {
        token: document.getElementById('token').value,
        email: document.getElementById('email').value,
        password: passwordInput.value,
        password_confirmation: confirmPasswordInput.value
      };

      try {
        const response = await fetch('https://saibara.lampungprov.go.id/api/auth/reset-password', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (response.ok && data.status === 200) {
          successMessage.textContent = data.message || 'Password has been reset successfully!';
          successMessage.classList.add('show');
          form.reset();
          window.location.href = '{{ config("https://saibara.lampungprov.go.id", "/reset-password-success") }}';

        } else {
          errorMessage.textContent = data.message || 'Failed to reset password. Please try again.';
          errorMessage.classList.add('show');
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Reset Password';
        }
      } catch (error) {
        errorMessage.textContent = 'An error occurred. Please try again later.';
        errorMessage.classList.add('show');
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Reset Password';
      }
    });
  </script>
</body>
</html>
