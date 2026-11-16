<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery attempt update</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#172033;font-family:Arial,sans-serif;">
    <div style="max-width:620px;margin:32px auto;padding:0 16px;">
        <div style="overflow:hidden;border:1px solid #e2e8f0;border-radius:14px;background:#fff;">
            <div style="padding:22px 28px;background:#f97316;color:#fff;">
                <div style="font-size:20px;font-weight:700;letter-spacing:.3px;">FURNISYNC</div>
                <div style="margin-top:4px;font-size:13px;">{{ $attempt['store_name'] }}</div>
            </div>
            <div style="padding:28px;line-height:1.6;">
                <p style="margin:0 0 10px;font-size:20px;font-weight:700;">We could not complete your delivery</p>
                <p style="margin:0 0 16px;color:#526078;">Hello {{ $attempt['name'] }}, our team attempted to deliver order <strong>{{ $attempt['order_number'] }}</strong>, but could not complete the delivery.</p>
                <div style="padding:14px 16px;border:1px solid #fed7aa;border-radius:10px;background:#fff7ed;">
                    <strong>Reason</strong><br>{{ $attempt['reason'] }}
                </div>
                <p style="margin:18px 0 0;color:#526078;">Your order has been returned to the store's dispatch queue. The store will arrange another delivery attempt and update you with the schedule.</p>
            </div>
        </div>
        <p style="margin:16px 0;text-align:center;color:#94a3b8;font-size:11px;">This is an automated update from FurniSync.</p>
    </div>
</body>
</html>
