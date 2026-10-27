<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>POS Receipt</title>
    <style>
      body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
      .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
      .title { font-size: 20px; font-weight: 700; }
      .muted { color: #6b7280; }
      .section { margin-top: 16px; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
      th { background: #f3f4f6; text-align: left; }
      .grid { width: 100%; }
      .grid td { border: none; padding: 2px 0; }
      .right { text-align: right; }
    </style>
  </head>
  <body>
    <div class="header">
      <div>
        <div class="title">POS Receipt</div>
        <div class="muted">{{ $receipt?->receipt_number ?? $order->order_number ?? ('POS-' . $order->id) }}</div>
      </div>
      <div class="right">
        <div>Date: {{ optional($receipt?->issued_at ?? $order->created_at)->format('Y-m-d H:i') }}</div>
        <div>Payment Status: {{ ucwords(str_replace('_', ' ', $order->payment_status ?? 'pending')) }}</div>
      </div>
    </div>

    <table class="grid">
      <tr>
        <td width="50%">
          <h3>Store</h3>
          <div>{{ $store?->name ?? '-' }}</div>
          <div class="muted">{{ $store?->address ?? '' }}</div>
          <div>{{ $store?->city ?? '' }} {{ $store?->province ?? '' }}</div>
          <div>{{ $store?->phone ?? '' }}</div>
          <div>{{ $store?->email ?? '' }}</div>
        </td>
        <td width="50%">
          <h3>Order</h3>
          <div>Order #: {{ $order->order_number ?? ('POS-' . $order->id) }}</div>
          <div>Branch: {{ $branch?->name ?? '-' }}</div>
          <div>Cashier: {{ trim(($issued_by?->fname ?? '').' '.($issued_by?->lname ?? '')) ?: '-' }}</div>
          <div>Customer: {{ $order->customer_name ?? 'Walk-in' }}</div>
          <div>Phone: {{ $order->customer_phone ?? '-' }}</div>
          <div>Fulfillment: {{ $order->delivery_required ? 'Delivery' : 'Pickup' }}</div>
        </td>
      </tr>
    </table>

    <div class="section">
      <h3>Items</h3>
      <table>
        <thead>
          <tr>
            <th style="width: 42%">Product</th>
            <th style="width: 10%">Qty</th>
            <th style="width: 16%">Base Unit</th>
            <th style="width: 16%">Custom Fee / Unit</th>
            <th style="width: 16%">Line Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $item)
            <tr>
              <td>
                {{ $item->product_name ?? 'Product' }}
                <div class="muted">{{ $item->sku ?? '' }}</div>
                @if (!empty($item->customization_request))
                  <div><strong>Upholstery:</strong> {{ $item->customization_request }}</div>
                  <div class="muted">Estimated lead time: {{ $item->customization_lead_time_days }} days</div>
                @endif
              </td>
              <td>{{ $item->quantity }}</td>
              <td>{{ number_format((float) $item->unit_price, 2) }}</td>
              <td>{{ number_format((float) ($item->customization_fee ?? 0), 2) }}</td>
              <td>{{ number_format((float) $item->line_total, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="muted">No items recorded.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="section">
      <table class="grid">
        <tr>
          <td>Subtotal: {{ number_format((float) $order->subtotal, 2) }}</td>
          <td>Discount: {{ number_format((float) $order->discount_amount, 2) }}</td>
          <td>Tax: {{ number_format((float) $order->tax_amount, 2) }}</td>
          <td>Shipping: {{ number_format((float) ($order->shipping_fee ?? 0), 2) }}</td>
          <td class="right"><strong>Total: {{ number_format((float) $order->total_amount, 2) }}</strong></td>
        </tr>
        @if ((float) ($order->commission_rate ?? 0) > 0)
        <tr>
          <td colspan="4">Store plan commission: {{ rtrim(rtrim(number_format((float) $order->commission_rate, 2), '0'), '.') }}% ({{ number_format((float) ($order->commission_amount ?? 0), 2) }}; not included in customer total)</td>
        </tr>
        @endif
        <tr>
          <td>Payment Method: {{ ($order->payment_method ?? null) === 'card' ? 'Online Payment' : (($order->payment_method ?? null) === 'gcash' ? 'GCash' : ucfirst(str_replace('_', ' ', $order->payment_method ?? '-'))) }}</td>
          <td>Amount Tendered: {{ number_format((float) ($order->amount_tendered ?? 0), 2) }}</td>
          <td>Change: {{ number_format((float) ($order->change_amount ?? 0), 2) }}</td>
          <td class="right">Reference: {{ $order->payment_reference ?? $receipt?->payment_reference ?? '-' }}</td>
        </tr>
        @if ($order->paid_at)
        <tr>
          <td colspan="4">Paid At: {{ optional($order->paid_at)->format('M d, Y h:i A') }}</td>
        </tr>
        @endif
      </table>
    </div>
  </body>
</html>
