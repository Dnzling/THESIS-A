<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employment Contract - {{ $application->first_name }} {{ $application->last_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #172033; font-size: 10px; line-height: 1.5; }
        h1 { font-size: 19px; margin: 4px 0; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        p { margin: 5px 0; }
        .brand { color: #f97316; font-size: 18px; font-weight: bold; }
        .muted { color: #64748b; }
        .rule { border: 0; border-top: 1px solid #cbd5e1; margin: 10px 0 14px; }
        .notice { border: 1px solid #fdba74; background: #fff7ed; padding: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 7px 0; }
        th, td { border: 1px solid #dbe2ea; padding: 6px 7px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-weight: bold; }
        .terms td:first-child { width: 25%; color: #475569; font-weight: bold; }
        .schedule th, .schedule td { text-align: center; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; border: 0; padding: 24px 18px 0 0; }
        .signature-line { border-top: 1px solid #64748b; padding-top: 5px; margin-top: 28px; }
        .footer { border-top: 1px solid #cbd5e1; margin-top: 22px; padding-top: 7px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="brand">FURNISYNC</div>
    <h1>Employment Contract</h1>
    <div class="muted">Prepared {{ $printedAt->format('F j, Y g:i A') }} · Draft for review and signature</div>
    <hr class="rule">

    <p>This Employment Contract is made between <strong>{{ $store->name }}</strong> (the “Employer”), with its work location at {{ $branch->name }}, {{ $branch->address }}, {{ $branch->city }}, {{ $branch->province }}, and <strong>{{ $application->first_name }} {{ $application->middle_name }} {{ $application->last_name }}</strong> (the “Employee”).</p>

    <h2>Employment Details</h2>
    <table class="terms">
        <tr><td>Employee</td><td>{{ $application->first_name }} {{ $application->middle_name }} {{ $application->last_name }}</td></tr>
        <tr><td>Birthday</td><td>{{ $application->birthday ? \Carbon\Carbon::parse($application->birthday)->format('F j, Y') : 'Not provided' }}</td></tr>
        <tr><td>Address</td><td>{{ collect([$terms['address'] ?? $application->address, $terms['barangay'] ?? $application->barangay, $terms['city'] ?? $application->city, $terms['province'] ?? $application->province])->filter()->implode(', ') ?: 'Not provided' }}</td></tr>
        <tr><td>Email / Phone</td><td>{{ $application->email }} / {{ $terms['phone'] ?? $application->phone }}</td></tr>
        <tr><td>Employer Contact</td><td>{{ $store->email ?: 'Not set' }} / {{ $store->phone ?: 'Not set' }}</td></tr>
        <tr><td>Position</td><td>{{ $terms['position'] }} · {{ $department->name }} · {{ $role->display_name ?: $role->name }}</td></tr>
        <tr><td>Work Location</td><td>{{ $branch->name }} — {{ collect([$branch->address, $branch->barangay, $branch->city, $branch->province])->filter()->implode(', ') }}</td></tr>
        <tr><td>Employment Type</td><td>{{ ucwords(str_replace('_', ' ', $terms['employment_type'])) }}</td></tr>
        <tr><td>Employment Status</td><td>To be completed by the Employer before signing: regular / probationary / other: ____________________</td></tr>
        <tr><td>Start Date</td><td>{{ \Carbon\Carbon::parse($terms['hire_date'])->format('F j, Y') }}</td></tr>
        @if(!empty($terms['contract_end_date']))
            <tr><td>Contract End Date</td><td>{{ \Carbon\Carbon::parse($terms['contract_end_date'])->format('F j, Y') }}</td></tr>
        @endif
        <tr><td>Compensation</td><td>{{ $terms['pay_type'] === 'hourly' ? 'Hourly rate' : ($terms['pay_type'] === 'monthly' ? 'Monthly salary' : 'Compensation') }}: ₱{{ number_format((float) $terms['salary'], 2) }}{{ $terms['pay_type'] === 'hourly' ? ' per hour' : ($terms['pay_type'] === 'monthly' ? ' per month' : ' · hybrid pay basis') }}</td></tr>
    </table>

    <h2>Work Schedule</h2>
    <table class="schedule">
        <thead><tr><th>Day</th><th>Schedule</th></tr></thead>
        <tbody>
            @foreach($terms['weekly_schedule'] as $day)
                <tr>
                    <td>{{ ucfirst($day['day_of_week']) }}</td>
                    <td>{{ $day['is_off'] ? 'Rest day' : (\Carbon\Carbon::createFromFormat('H:i', $day['start_time'])->format('g:i A') . ' – ' . \Carbon\Carbon::createFromFormat('H:i', $day['end_time'])->format('g:i A')) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Terms of Employment</h2>
    <ol>
        <li>The Employee will perform the duties reasonably assigned to the stated position and follow lawful workplace instructions.</li>
        <li>The Employee will work at the listed branch and follow the schedule above, subject to reasonable changes communicated by the Employer.</li>
        <li>The Employer will pay the stated compensation on its regular payroll schedule and make deductions required by applicable law. The payroll frequency and any additional benefits should be confirmed in writing before signing.</li>
        <li>The Employee will protect confidential business, customer, employee, and operational information and return Employer property when requested or when employment ends.</li>
        <li>Leave, benefits, workplace policies, occupational safety, and other employment conditions will follow applicable Philippine law and the Employer’s written policies.</li>
        <li>Any change to these written terms must be documented and acknowledged by both parties. Ending employment will be handled in accordance with applicable law.</li>
    </ol>

    <div class="notice">This generated document is a draft containing the onboarding details entered by HR. Review all terms and complete any store-specific provisions before signing. The signed copy must be uploaded to complete onboarding.</div>

    <table class="signatures">
        <tr>
            <td><div class="signature-line"><strong>Employee Signature</strong><br>{{ $application->first_name }} {{ $application->last_name }}<br>Date: __________________</div></td>
            <td><div class="signature-line"><strong>For {{ $store->name }}</strong><br>Authorized Representative<br>Name / Title: __________________<br>Date: __________________</div></td>
        </tr>
    </table>

    <div class="footer">Printed {{ $printedAt->format('F j, Y g:i A') }} · Employment contract draft · {{ $store->name }}</div>
</body>
</html>
