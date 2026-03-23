<!DOCTYPE html>
<html>
<head>
    <title>FurniShop OTP Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 640px;
            margin: 0 auto;
            padding: 24px;
            background: #f8fafc;
        }
        .card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #7c5af2, #8f5eff);
            color: #fff;
            padding: 20px 24px;
            font-size: 20px;
            font-weight: bold;
        }
        .content {
            padding: 24px;
        }
        .otp-box {
            text-align: center;
            margin: 20px 0;
            padding: 18px;
            border-radius: 10px;
            background: #f5f3ff;
            border: 1px dashed #8f5eff;
        }
        .otp {
            margin: 0;
            font-size: 34px;
            letter-spacing: 10px;
            color: #6d28d9;
            font-weight: 700;
        }
        .muted {
            color: #6b7280;
            font-size: 13px;
            margin-top: 22px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">FurniShop</div>
        <div class="content">
            <p>Hello{{ $userName ? ' ' . $userName : '' }},</p>
            <p>Use this OTP to verify your FurniShop customer account:</p>

            <div class="otp-box">
                <p class="otp">{{ $otp }}</p>
            </div>

            <p>This OTP expires in <strong>15 minutes</strong>.</p>
            <p>If you did not request this, you can safely ignore this email.</p>

            <p class="muted">
                This is an automated message from FurniShop Customer Portal.
            </p>
        </div>
    </div>
</body>
</html>
