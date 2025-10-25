<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Rejected</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px;">
    <div style="max-width:600px;margin:auto;background:#fff;padding:20px;border-radius:8px;">
        <h2 style="color:#333;">Payment Rejected</h2>
        <p>Hello {{ $user->name ?? $user->full_name ?? 'Member' }},</p>

        <p>We reviewed your recent payment and it has been rejected. Details:</p>

        <ul>
            <li><strong>Amount:</strong> ₱{{ number_format($payment->amount_paid ?? $payment->amount ?? 0, 2) }}</li>
            <li><strong>Type:</strong> {{ $payment->schedule_id ? 'Monthly Payment' : 'Down Payment' }}</li>
            <li><strong>Date:</strong> {{ $payment->payment_date ?? now()->toDateString() }}</li>
        </ul>

        @if(!empty($reason))
            <p><strong>Reason:</strong> {{ $reason }}</p>
        @endif

        <p>If you believe this is an error, please contact support or re-submit your payment with a clearer receipt.</p>

        <p>Thank you,<br>The AppliLoan Team</p>
    </div>
</body>
</html>
