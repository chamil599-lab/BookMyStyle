<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Account Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #8B5FBF 0%, #9D76C1 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
        <h1 style="color: white; margin: 0;">BookMyStyle</h1>
    </div>
    
    <div style="background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;">
        <h2 style="color: #2D3047;">Congratulations, {{ $user->name }}!</h2>
        
        <p>Great news! Your account has been approved.</p>
        
        <p>You can now access your dashboard and start managing your salon on BookMyStyle.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('dashboard') }}" style="background: #8B5FBF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold;">Go to Dashboard</a>
        </div>
        
        <p>Welcome aboard!<br>The BookMyStyle Team</p>
    </div>
</body>
</html>
