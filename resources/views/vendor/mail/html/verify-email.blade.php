<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3490dc;
            color: white !important;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Verify Your Email Address</h1>
    <p>Hello {{ $user->name }},</p>
    <p>Please click the button below to verify your email:</p>
    <a href="{{ $verificationUrl }}" class="button">Verify Email</a>
    <p>If you didn't create an account, ignore this email.</p>
    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>

</html>
