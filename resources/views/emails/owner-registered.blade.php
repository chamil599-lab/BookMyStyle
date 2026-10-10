<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registration Pending Approval</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #8B5FBF 0%, #9D76C1 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">BookMyStyle</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #2D3047;">Welcome, {{ $user->name }}! 👋</h2>
        
        <p>Thank you for registering as a salon owner on BookMyStyle.</p>
        
        <div style="background: #FFF3CD; border-left: 4px solid #FFC107; padding: 15px; margin: 20px 0;">
            <p style="margin: 0;"><strong>⏳ Your account is pending approval</strong></p>
            <p style="margin: 10px 0 0 0;">Our team will review your registration and notify you once your account has been approved. This usually takes 24-48 hours.</p>
        </div>
        
        <p><strong>What happens next?</strong></p>
        <ul>
            <li>Our admin team will review your registration</li>
            <li>You'll receive an email once your account is approved</li>
            <li>After approval, you can login and start managing your salon</li>
        </ul>
        
        <p>If you have any questions, feel free to contact us.</p>
        
        <p>Best regards,<br>The BookMyStyle Team</p>
    </div>
</body>
</html>