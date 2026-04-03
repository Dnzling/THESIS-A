<!DOCTYPE html>
<html>
<head>
    <title>Email Verification OTP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .otp-container {
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }
        .otp-code {
            margin: 0;
            color: #2d3748;
            letter-spacing: 10px;
            font-size: 32px;
            font-weight: bold;
        }
        .expiry-note {
            color: yellow;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h2>Email Verification</h2>
    
    @if($userName)
        <p>Hello {{ $userName }},</p>
    @else
        <p>Hello,</p>
    @endif
    
    <p>Your OTP for email verification is:</p>
    
    <div class="otp-container">
        <h1 class="otp-code">{{ $otp }}</h1>
    </div>
    
    <p>This OTP will expire in <span class="expiry-note">15 minutes</span>.</p>
    
    <p>If you didn't request this verification, please ignore this email.</p>
    
    <div class="footer">
        <p>Thank you,<br>
        <strong>{{ config('app.name') }}</strong></p>
        
        <p style="font-size: 12px; color: #a0aec0;">
            This is an automated message, please do not reply to this email.
        </p>
    </div>
</body>
</html>