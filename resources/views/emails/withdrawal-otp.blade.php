<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Verification Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #333333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #555555;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .otp-container {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-label {
            color: #ffffff;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .otp-code {
            font-size: 48px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .expiry-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .expiry-notice p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }
        .security-warning {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 6px;
            margin: 25px 0;
        }
        .security-warning p {
            margin: 0;
            color: #721c24;
            font-size: 14px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 13px;
        }
        .support-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🔐 Withdrawal Verification</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello,
            </div>
            
            <div class="message">
                You have requested to withdraw funds from your account. To ensure the security of your transaction, please use the verification code below:
            </div>
            
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <div class="expiry-notice">
                <p><strong>⏰ Important:</strong> This code will expire in <strong>10 minutes</strong>.</p>
            </div>
            
            <div class="security-warning">
                <p><strong>🛡️ Security Notice:</strong> Never share this code with anyone. Our team will never ask for your verification code.</p>
            </div>
            
            <div class="message">
                If you did not request this withdrawal, please contact our support team immediately.
            </div>
        </div>
        
        <div class="footer">
            <p>Need help? Contact us at <a href="mailto:support@yourapp.com" class="support-link">support@yourapp.com</a></p>
            <p>&copy; {{ date('Y') }} Your Trading Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
