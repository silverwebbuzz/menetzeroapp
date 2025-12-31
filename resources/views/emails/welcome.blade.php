<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name', 'MENetZero') }}</title>
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
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">🌱</div>
            <h1>Welcome to {{ config('app.name', 'MENetZero') }}!</h1>
        </div>
        
        <div class="content">
            <p>Hello <strong>{{ $user->name }}</strong>,</p>
            
            <p>Thank you for registering with {{ config('app.name', 'MENetZero') }}! We're excited to have you on board.</p>
            
            <p>Your account has been successfully created with the email: <span class="highlight">{{ $user->email }}</span></p>
            
            <p>You can now start tracking and managing your carbon emissions, set up your company profile, and begin your journey towards net-zero emissions.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('client.dashboard') }}" class="button">Go to Dashboard</a>
            </div>
            
            <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team.</p>
            
            <p>Best regards,<br>
            The {{ config('app.name', 'MENetZero') }} Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'MENetZero') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

