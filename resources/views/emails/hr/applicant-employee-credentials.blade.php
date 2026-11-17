<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>Your employee account is ready</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f5f8; color:#182230; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f5f8; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%; max-width:600px; background-color:#ffffff; border:1px solid #e3e8ef; border-radius:12px; overflow:hidden;">
                    <tr>
                        <td style="padding:25px 32px; background-color:#f97316;">
                            <p style="margin:0; color:#ffffff; font-size:21px; line-height:1.2; font-weight:700; letter-spacing:.2px;">FURNISYNC</p>
                            <p style="margin:7px 0 0; color:#fff4ed; font-size:13px; line-height:1.5;">Employee account setup</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 8px; color:#f97316; font-size:12px; line-height:1.4; font-weight:700; letter-spacing:1px; text-transform:uppercase;">Welcome aboard</p>
                            <h1 style="margin:0 0 14px; color:#172033; font-size:25px; line-height:1.3; font-weight:700;">Your employee account is ready</h1>
                            <p style="margin:0 0 22px; color:#526174; font-size:15px; line-height:1.7;">Hello {{ $employee->fname ?? 'there' }}, your account has been set up. Use the sign-in details below to access your employee workspace.</p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e3e8ef; border-radius:9px;">
                                <tr>
                                    <td style="padding:17px 19px; border-bottom:1px solid #e3e8ef;">
                                        <p style="margin:0 0 5px; color:#718096; font-size:11px; line-height:1.4; font-weight:700; letter-spacing:.7px; text-transform:uppercase;">Employee number</p>
                                        <p style="margin:0; color:#172033; font-size:15px; line-height:1.5; font-weight:700;">{{ $employee->employee_number ?: 'Not assigned' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:17px 19px; border-bottom:1px solid #e3e8ef;">
                                        <p style="margin:0 0 5px; color:#718096; font-size:11px; line-height:1.4; font-weight:700; letter-spacing:.7px; text-transform:uppercase;">Sign-in email</p>
                                        <p style="margin:0; color:#172033; font-size:15px; line-height:1.5;">{{ $employee->user?->email }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:17px 19px;">
                                        <p style="margin:0 0 5px; color:#718096; font-size:11px; line-height:1.4; font-weight:700; letter-spacing:.7px; text-transform:uppercase;">Temporary password</p>
                                        <p style="margin:0; color:#172033; font-family:Consolas, 'Courier New', monospace; font-size:16px; line-height:1.5; font-weight:700; word-break:break-word;">{{ $temporaryPassword }}</p>
                                    </td>
                                </tr>
                            </table>

                            @php($branchName = $employee->branch?->name)
                            @php($storeName = $employee->store?->store_name ?? $employee->store?->name)
                            @if($branchName || $storeName)
                                <p style="margin:16px 0 0; color:#526174; font-size:13px; line-height:1.6;">
                                    @if($storeName)<strong>Store:</strong> {{ $storeName }}@endif
                                    @if($storeName && $branchName) &nbsp;|&nbsp; @endif
                                    @if($branchName)<strong>Branch:</strong> {{ $branchName }}@endif
                                </p>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0;">
                                <tr>
                                    <td align="center" bgcolor="#f97316" style="border-radius:7px;">
                                        <a href="{{ url('/login') }}" style="display:inline-block; padding:13px 22px; color:#ffffff; font-size:14px; line-height:1.2; font-weight:700; text-decoration:none;">Sign in to FurniSync</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:25px; background-color:#fff7ed; border-left:3px solid #f97316;">
                                <tr>
                                    <td style="padding:13px 15px; color:#7c3b12; font-size:13px; line-height:1.6;">
                                        <strong>Keep your account secure.</strong> Change your temporary password after signing in for the first time, and do not share it with anyone.
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:25px 0 0; color:#718096; font-size:13px; line-height:1.6;">If you were not expecting this account, please contact your store administrator.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:17px 32px; background-color:#f8fafc; border-top:1px solid #e8edf3;">
                            <p style="margin:0; color:#718096; font-size:12px; line-height:1.6;">This is an automated account email from {{ config('app.name', 'FurniSync') }}. Please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
