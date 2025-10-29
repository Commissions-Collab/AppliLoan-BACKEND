<!DOCTYPE html>
<html>
<head>
    <title>Past Due Loan Payment</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; padding: 25px; border-radius: 8px;">
        <h2 style="color: #333;">Hello {{ $user->name ?? 'Member' }},</h2>

        <p>This is an important notice that your loan payment is past due.</p>

        <p><strong>Loan Number:</strong> {{ $loan->loan_number }}</p>
        <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($dueDate)->format('F j, Y') }}</p>
        <p><strong>Days Past Due:</strong> {{ abs($daysPast) }}</p>

        <p>Please make your payment as soon as possible to avoid further penalties or collection actions. If you've already paid, please ignore this message or contact support.</p>

        <br>
        <p>Thank you,<br>
        <strong>The Loan Department</strong></p>
    </div>
</body>
</html>
