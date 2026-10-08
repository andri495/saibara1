<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Password Reset Successful</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/icons/ic_ps_saibara.png') }}">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
      background-color: white;
      width: 100%;
      max-width: 400px;
      padding: 50px 30px;
      border-radius: 16px;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .checkmark {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 30px;
      font-size: 40px;
      color: white;
      animation: scaleIn 0.5s ease-out;
    }

    @keyframes scaleIn {
      0% {
        transform: scale(0);
        opacity: 0;
      }
      50% {
        transform: scale(1.1);
      }
      100% {
        transform: scale(1);
        opacity: 1;
      }
    }

    h2 {
      margin: 0 0 15px;
      font-size: 26px;
      color: #333;
    }

    p {
      color: #666;
      font-size: 15px;
      margin: 0 0 30px;
      line-height: 1.6;
    }

    .btn {
      display: inline-block;
      padding: 14px 40px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn:active {
      transform: translateY(0);
    }

    .redirect-info {
      margin-top: 20px;
      font-size: 13px;
      color: #999;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="checkmark">✓</div>
    <h2>Password Reset Successful!</h2>
    <p>Your password has been successfully reset. You can now sign in with your new password.</p>
    
  </div>
</body>
</html>
