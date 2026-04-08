<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Interview Schedule</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Interview Scheduled</h2>
    <p>Hello {{ $interview->application->full_name }},</p>
    <p>Your interview for <strong>{{ $interview->application->jobPosting->title }}</strong> has been scheduled.</p>
    <p><strong>Date:</strong> {{ $interview->interview_date->format('F j, Y g:i A') }}</p>
    <p><strong>Interview Type:</strong> {{ $interview->interview_type }}</p>
    @if($interview->duration_minutes)
        <p><strong>Duration:</strong> {{ $interview->duration_minutes }} minutes</p>
    @endif
    @if($interview->notes)
        <p><strong>Notes:</strong> {{ $interview->notes }}</p>
    @endif
    <p>Please be ready ahead of time.</p>
    <p>Regards,<br>{{ config('app.name') }}</p>
</body>
</html>
