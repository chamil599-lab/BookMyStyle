<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Update</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #8B5FBF 0%, #9D76C1 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">BookMyStyle</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #2D3047;">Hello, {{ $user->name }}</h2>
        
        <p>Thank you for your interest in joining BookMyStyle.</p>
        
        <p>After reviewing your registration, we regret to inform you that we are unable to approve your account at this time.</p>
        
        <p>If you believe this was a mistake, please contact our support team.</p>
        
        <p>Best regards,<br>The BookMyStyle Team</p>
    </div>
</body>
</html>