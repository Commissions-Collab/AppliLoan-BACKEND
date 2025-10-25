<!DOCTYPE html>
<html>
<head>
    <title>Membership Status Update</title>
</head>
<body>
    <h2>Hello {{ $user->name ?? 'Member' }},</h2>

    <p>Your membership application status has been updated to: 
        <strong>{{ ucfirst($status) }}</strong>.
    </p>

    @if ($status === 'approved')
        <p>🎉 Congratulations! You are now an approved member of our organization.</p>
    @elseif ($status === 'rejected')
        <p>Unfortunately, your membership application was not approved at this time. You may contact support for more information.</p>
    @else
        <p>Your application is still under review. Please wait for further updates.</p>
    @endif

    <p>Thank you,<br>
    The Membership Team</p>
</body>
</html>
