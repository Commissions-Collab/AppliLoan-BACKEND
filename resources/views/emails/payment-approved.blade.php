<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Received</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px;">
    <div style="max-width:600px;margin:auto;background:#fff;padding:20px;border-radius:8px;">
        <h2 style="color:#333;">Payment Received</h2>
        <p>Hello {{ $user->name ?? $user->full_name ?? 'Member' }},</p>

        <p>We have received and approved your payment. Details:</p>

        <ul>
            <li><strong>Loan:</strong> {{ $loan->loan_number ?? 'N/A' }}</li>
            <li><strong>Amount:</strong> ₱{{ number_format($payment->amount_paid ?? $payment->amount ?? 0, 2) }}</li>
            <li><strong>Type:</strong> {{ $payment->schedule_id ? 'Monthly Payment' : 'Down Payment' }}</li>
            <li><strong>Date:</strong> {{ $payment->payment_date ?? now()->toDateString() }}</li>
        </ul>

        <p>If you have questions, reply to this email or contact support.</p>

        <p>Thank you,<br>The AppliLoan Team</p>
    </div>
</body>
</html>
