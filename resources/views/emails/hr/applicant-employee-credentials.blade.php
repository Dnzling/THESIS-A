<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Account Created</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Welcome to {{ config('app.name') }}</h2>
    <p>Hello {{ $employee->fname ?? $employee->first_name ?? 'there' }},</p>
    <p>Your employee account has been created.</p>
    <p><strong>Employee Number:</strong> {{ $employee->employee_number }}</p>
    <p><strong>Email:</strong> {{ $employee->user?->email }}</p>
    <p><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
    <p>Please change your password after your first login.</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
