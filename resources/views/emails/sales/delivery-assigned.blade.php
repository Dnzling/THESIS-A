<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery arranged</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,sans-serif;">
    <div style="max-width:620px;margin:32px auto;padding:0 16px;">
        <div style="overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#fff;">
            <div style="padding:22px 28px;background:#f97316;color:#fff;">
                <div style="font-size:20px;font-weight:700;letter-spacing:.3px;">FURNISYNC</div>
                <div style="margin-top:4px;font-size:13px;">{{ $assignment['store_name'] }}</div>
            </div>
            <div style="padding:28px;">
                <p style="margin:0 0 10px;font-size:20px;font-weight:700;">Your delivery has been scheduled</p>
                <p style="margin:0 0 22px;color:#526078;line-height:1.6;">
                    Hello {{ $assignment['customer_name'] }}, your order <strong>{{ $assignment['order_number'] }}</strong>
                    has been assigned to a delivery trip.
                </p>

                <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                    <tr>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;color:#64748b;">Trip reference</td>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;text-align:right;font-weight:700;">{{ $assignment['trip_reference'] }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;color:#64748b;">Scheduled departure</td>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;text-align:right;font-weight:600;">{{ $assignment['departure_date'] ?: 'To be scheduled' }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;color:#64748b;">Requested delivery date</td>
                        <td style="padding:12px 0;border-top:1px solid #e7ebf1;text-align:right;font-weight:600;">{{ $assignment['delivery_date'] ?: 'Not provided' }}</td>
                    </tr>
                </table>

                @if (!empty($assignment['tracking_number']) && !empty($assignment['tracking_url']))
                    <div style="margin:24px 0;text-align:center;">
                        <a href="{{ $assignment['tracking_url'] }}" style="display:inline-block;padding:12px 22px;border-radius:8px;background:#f97316;color:#fff;text-decoration:none;font-weight:700;">Track your order</a>
                        <div style="margin-top:10px;color:#64748b;font-size:12px;">Tracking number: <strong>{{ $assignment['tracking_number'] }}</strong></div>
                    </div>
                @endif

                <p style="margin:20px 0 0;color:#64748b;font-size:12px;line-height:1.6;">
                    The departure date is when the trip is scheduled to leave. We will contact you if the delivery schedule changes.
                </p>
            </div>
        </div>
        <p style="margin:16px 0;text-align:center;color:#94a3b8;font-size:11px;">This is an automated order update from FurniSync.</p>
    </div>
</body>
</html>
