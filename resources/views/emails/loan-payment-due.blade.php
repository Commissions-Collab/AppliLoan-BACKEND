<!DOCTYPE html>
<html>
<head>
    <title>Loan Payment Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 8px;">
        <h2 style="color: #333;">Hello {{ $user->name ?? 'Member' }},</h2>

        <p>This is a friendly reminder that your loan payment is due soon.</p>

        <p><strong>Loan Number:</strong> {{ $loan->loan_number }}</p>
        <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($dueDate)->format('F j, Y') }}</p>

        @if ($daysRemaining === 0)
            <p>Your payment is <strong>due today</strong>. Please make sure to complete it to avoid penalties.</p>
        @else
            <p>You have <strong>{{ $daysRemaining }}</strong> day(s) left until your payment due date.</p>
        @endif

        <p>Please visit your loan dashboard or contact our office if you have any questions.</p>

        <br>
        <p>Thank you,<br>
        <strong>The Loan Department</strong></p>
    </div>
</body>
</html>
