<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Shift Assignment Updated</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Shift Assignment Update</h2>
    <p>Hello {{ $employee->fname ?? 'Employee' }},</p>

    <p>Your shift assignment has been updated.</p>

    <p><strong>New Shift:</strong> {{ $shift->name }}</p>
    <p><strong>Time:</strong> {{ $shift->start_time }} - {{ $shift->end_time }}</p>
    <p><strong>Effective Date:</strong> {{ \Carbon\Carbon::parse($effectiveDate)->format('M d, Y') }}</p>
    <p><strong>Reason:</strong> {{ $reason }}</p>

    <p>If you have any questions, please coordinate with HR.</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>

