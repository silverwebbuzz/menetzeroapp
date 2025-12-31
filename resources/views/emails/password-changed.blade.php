<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed - {{ config('app.name', 'MENetZero') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 32px;
            margin-bottom: 10px;
        }
        h1 {
            color: #10b981;
            margin: 0;
            font-size: 28px;
        }
        .content {
            margin: 30px 0;
        }
        .content p {
            margin-bottom: 15px;
            color: #555;
        }
        .alert-box {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-box p {
            margin: 0;
            color: #065f46;
        }
        .info-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0;
            color: #92400e;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #10b981;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #059669;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .highlight {
            color: #10b981;
            font-weight: 600;
        }
        .timestamp {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">🔒</div>
            <h1>Password Changed Successfully</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            
            <div class="alert-box">
                <p><strong>✓ Your password has been successfully changed.</strong></p>
            </div>
            
            <p>This is a confirmation that your password for your {{ config('app.name', 'MENetZero') }} account was changed on <span class="highlight">{{ $changedAt->format('F d, Y \a\t g:i A') }}</span>.</p>
            
            <div class="info-box">
                <p><strong>⚠️ Security Notice:</strong></p>
                <p style="margin-top: 8px;">If you did not make this change, please contact our support team immediately. Your account security is important to us.</p>
            </div>
            
            <p>For your security, we recommend:</p>
            <ul style="color: #555; margin-left: 20px; margin-bottom: 20px;">
                <li>Using a strong, unique password</li>
                <li>Not sharing your password with anyone</li>
                <li>Regularly updating your password</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ route('client.dashboard') }}" class="button">Go to Dashboard</a>
            </div>
            
            <p>If you have any concerns about your account security, please don't hesitate to reach out to our support team.</p>
            
            <p>Best regards,<br>
            The {{ config('app.name', 'MENetZero') }} Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated security notification. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'MENetZero') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

