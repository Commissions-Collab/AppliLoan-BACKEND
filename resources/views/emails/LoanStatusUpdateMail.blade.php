<!DOCTYPE html>
<html>
<head>
    <title>Loan Application Status</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 8px;">
        <h2 style="color: #333;">Hello {{ $user->name ?? 'Applicant' }},</h2>

        <p>Your loan application has been <strong>{{ ucfirst($loan->status) }}</strong>.</p>

        @if ($loan->status === 'approved')
            <p>🎉 Congratulations! Your loan has been approved. Please visit your dashboard for the next steps.</p>
        @elseif ($loan->status === 'rejected')
            <p>Unfortunately, your loan application was rejected.</p>
            <p><strong>Reason:</strong> {{ $loan->rejection_reason ?? 'Not specified.' }}</p>
        @elseif ($loan->status === 'cancelled')
            <p>Your loan application has been cancelled upon request or due to incomplete requirements.</p>
        @else
            <p>Your loan application is still under review. You will be notified once a decision is made.</p>
        @endif

        <br>
        <p>Thank you,<br>
        <strong>The Loan Processing Team</strong></p>
    </div>
</body>
</html>
