<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Interview scheduled</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        Your interview for {{ $interview->application->jobPosting->title }} has been scheduled.
    </div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 28px;background-color:#fff7ed;border-bottom:3px solid #ea580c;">
                            <p style="margin:0;color:#c2410c;font-size:20px;line-height:26px;font-weight:700;letter-spacing:1px;">FURNISYNC</p>
                            <p style="margin:5px 0 0;color:#64748b;font-size:13px;line-height:19px;">Applicant interview notification</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 14px;color:#172033;font-size:23px;line-height:30px;font-weight:700;">Your interview is scheduled</h1>
                            <p style="margin:0 0 20px;color:#475569;font-size:15px;line-height:24px;">
                                Hello {{ $interview->application->full_name }},<br>
                                We’re pleased to invite you to interview for <strong style="color:#172033;">{{ $interview->application->jobPosting->title }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e2e8f0;border-radius:8px;background-color:#f8fafc;">
                                <tr>
                                    <td style="padding:16px 18px;border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0 0 4px;color:#64748b;font-size:12px;line-height:17px;text-transform:uppercase;letter-spacing:.5px;">Date and time</p>
                                        <p style="margin:0;color:#172033;font-size:15px;line-height:22px;font-weight:700;">{{ $interview->interview_date->copy()->timezone(config('app.timezone'))->format('F j, Y') }}</p>
                                        <p style="margin:2px 0 0;color:#334155;font-size:14px;line-height:20px;">{{ $interview->interview_date->copy()->timezone(config('app.timezone'))->format('g:i A') }} ({{ config('app.timezone') }})</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 18px;{{ $interview->duration_minutes ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                                        <p style="margin:0 0 4px;color:#64748b;font-size:12px;line-height:17px;text-transform:uppercase;letter-spacing:.5px;">Interview type</p>
                                        <p style="margin:0;color:#172033;font-size:14px;line-height:21px;font-weight:600;">{{ $interview->interview_type }}</p>
                                    </td>
                                </tr>
                                @if($interview->duration_minutes)
                                    <tr>
                                        <td style="padding:14px 18px;{{ $interview->notes ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                                            <p style="margin:0 0 4px;color:#64748b;font-size:12px;line-height:17px;text-transform:uppercase;letter-spacing:.5px;">Expected duration</p>
                                            <p style="margin:0;color:#172033;font-size:14px;line-height:21px;">{{ $interview->duration_minutes }} minutes</p>
                                        </td>
                                    </tr>
                                @endif
                                @if($interview->notes)
                                    <tr>
                                        <td style="padding:14px 18px;">
                                            <p style="margin:0 0 4px;color:#64748b;font-size:12px;line-height:17px;text-transform:uppercase;letter-spacing:.5px;">Additional information</p>
                                            <p style="margin:0;color:#172033;font-size:14px;line-height:21px;white-space:pre-line;">{{ $interview->notes }}</p>
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <p style="margin:20px 0 0;color:#475569;font-size:14px;line-height:22px;">Please be ready at the scheduled time. We look forward to speaking with you.</p>
                            <p style="margin:20px 0 0;color:#475569;font-size:14px;line-height:22px;">Regards,<br><strong style="color:#172033;">{{ config('app.name') }} Recruitment Team</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background-color:#f8fafc;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;color:#94a3b8;font-size:11px;line-height:17px;">This message was sent about your job application. Please keep it for your reference.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
