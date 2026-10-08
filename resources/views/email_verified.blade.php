<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Email Verified</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/icons/ic_ps_saibara.png') }}">
  <style>
    body {
      background-color: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .container {
      background-color: white;
      width: 300px;
      padding: 40px 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .checkmark {
      color: green;
      font-size: 60px;
    }

    h2 {
      margin: 20px 0 10px;
      font-size: 22px;
      color: #333;
    }

    p {
      color: #666;
      font-size: 14px;
      margin: 0;
    }

    .footer-icon {
      margin-top: 20px;
      opacity: 0.1;
    }

    .footer-icon img {
      width: 24px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="checkmark">&#10003;</div>
    <h2>Email Verified</h2>
    <p>Now you can sign in to the app</p>

  </div>
</body>
</html>
