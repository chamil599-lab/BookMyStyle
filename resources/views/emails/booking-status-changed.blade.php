<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking {{ ucfirst($booking->status) }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
            font-size: 14px;
        }
        .status-approved {
            background-color: #10b981;
            color: white;
        }
        .status-rejected {
            background-color: #ef4444;
            color: white;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .booking-details {
            background-color: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .booking-details h3 {
            margin-top: 0;
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #6b7280;
        }
        .detail-value {
            color: #111827;
        }
        .message-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .message-box.rejected {
            background-color: #fee2e2;
            border-left-color: #ef4444;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>BookMyStyle</h1>
            <span class="status-badge status-{{ $booking->status }}">
                {{ ucfirst($booking->status) }}
            </span>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">
                Hi {{ $booking->name }},
            </div>

            @if($booking->status === 'approved')
                <p>Great news! Your booking has been <strong>confirmed</strong>. We're looking forward to seeing you!</p>
            @else
                <p>We regret to inform you that your booking request has been <strong>declined</strong>.</p>
                <div class="message-box rejected">
                    <strong>Note:</strong> This may be due to salon capacity or scheduling conflicts. Please feel free to book another time slot or contact the salon directly.
                </div>
            @endif

            <!-- Booking Details -->
            <div class="booking-details">
                <h3>📋 Booking Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value">{{ $booking->service()->withTrashed()->first()?->title ?? 'Booked service' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">{{ \Carbon\Carbon::parse($booking->date)->format('l, F j, Y') }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">{{ $booking->time }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value">{{ $booking->phone }}</span>
                </div>
            </div>

            @if($booking->status === 'approved')
                <div class="message-box">
                    <strong>💡 Reminder:</strong> Please arrive 5-10 minutes before your appointment time.
                </div>
            @endif

            <p style="margin-top: 30px;">
                If you have any questions, feel free to reply to this email or contact the salon directly.
            </p>

            <p>
                Best regards,<br>
                <strong>BookMyStyle Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© {{ date('Y') }} BookMyStyle. All rights reserved.</p>
            <p>
                <a href="mailto:support@bookmystyle.com">Contact Support</a>
            </p>
        </div>
    </div>
</body>
</html>
