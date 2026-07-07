<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Email Verification</title>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 480px; margin: 40px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #2D6A2F, #5A8A3C); padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0; color: #fff; font-size: 22px; font-weight: 800; letter-spacing: 1px; }
        .body { padding: 32px 24px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px; }
        .otp-box { background: #f0fdf4; border: 2px dashed #2D6A2F; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #2D6A2F; font-family: 'Courier New', monospace; }
        .expiry { font-size: 12px; color: #9ca3af; margin-top: 8px; }
        .footer { padding: 20px 24px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>HARVEST</h1>
        </div>
        <div class="body">
            <p>Hi {{ $userName }},</p>
            <p>Use the code below to verify your email address and activate your account.</p>
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>
            <p class="expiry">This code expires in 10 minutes.</p>
            <p style="color: #6b7280; font-size: 13px;">If you did not create this account, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} HARVEST. All rights reserved.
        </div>
    </div>
</body>
</html>
