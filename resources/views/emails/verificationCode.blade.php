<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($type) && $type === 'forgot-password' ? 'Reset Your Password' : 'Verify Your Account' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #e0e0e0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px 0;
            text-align: center;
        }
        .title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .verification-code {
            background-color: #f8fafc;
            border: 2px dashed #2563eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 4px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fef3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #664d03;
        }
        .footer {
            text-align: center;
            padding: 20px 0;
            border-top: 1px solid #e0e0e0;
            color: #888;
            font-size: 14px;
        }
        .support {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">AppliLoan</div>
            <p style="margin: 0; color: #666;">Secure Loan Management System</p>
        </div>
        
        <div class="content">
            <h1 class="title">
                @if(isset($type) && $type === 'forgot-password')
                    Reset Your Password
                @else
                    Verify Your Account
                @endif
            </h1>
            
            <p class="message">
                @if(isset($type) && $type === 'forgot-password')
                    We received a request to reset your password. Use the verification code below to proceed with resetting your password.
                @else
                    Thank you for registering with AppliLoan! To complete your account setup, please verify your email address using the code below.
                @endif
            </p>
            
            <div class="verification-code">
                <p style="margin: 0; font-size: 14px; color: #666;">Your verification code is:</p>
                <div class="code">{{ $code }}</div>
                <p style="margin: 0; font-size: 12px; color: #999;">Enter this code in the app to continue</p>
            </div>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> This code will expire in 10 minutes for security reasons. 
                @if(isset($type) && $type === 'forgot-password')
                    If you didn't request a password reset, please ignore this email.
                @else
                    If you didn't create an account, please ignore this email.
                @endif
            </div>
            
            <div class="support">
                <strong>Need help?</strong><br>
                If you're having trouble with the verification process, please contact our support team.
                We're here to help you get started with AppliLoan!
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} AppliLoan. All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>