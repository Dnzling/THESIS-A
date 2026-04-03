<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Goods Receipt</title>
    <style>
      body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
      .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
      .title { font-size: 20px; font-weight: 700; }
      .muted { color: #6b7280; }
      .section { margin-top: 16px; }
      .section h3 { margin: 0 0 8px 0; font-size: 14px; }
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
      th { background: #f3f4f6; text-align: left; }
      .grid { width: 100%; }
      .grid td { border: none; padding: 2px 0; }
      .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; background: #eef2ff; font-size: 11px; }
      .right { text-align: right; }
      .mt-8 { margin-top: 8px; }
    </style>
  </head>
  <body>
    <div class="header">
      <div>
        <div class="title">Goods Receipt Note</div>
        <div class="muted">GRN #{{ $grn_number }}</div>
      </div>
      <div class="right">
        <div>Receipt Date: {{ optional($receipt_date)->format('Y-m-d') }}</div>
        <div>Receipt Time: {{ optional($receipt_time)->format('H:i') }}</div>
        <div class="badge">{{ strtoupper($receipt_status) }}</div>
      </div>
    </div>

    <table class="grid">
      <tr>
        <td width="50%">
          <h3>Supplier</h3>
          <div>{{ $supplier?->supplier_name ?? '-' }}</div>
          <div class="muted">{{ $supplier?->company_name ?? '' }}</div>
          <div>{{ $supplier?->contact_person ?? '-' }}</div>
          <div>{{ $supplier?->email ?? '-' }}</div>
          <div>{{ $supplier?->phone ?? '-' }}</div>
          <div>{{ $supplier?->address ?? '-' }}</div>
        </td>
        <td width="50%">
          <h3>Purchase Order</h3>
          <div>PO #: {{ $purchase_order?->po_number ?? '-' }}</div>
          <div>Order Date: {{ optional($purchase_order?->order_date)->format('Y-m-d') }}</div>
          <div>Expected Delivery: {{ optional($purchase_order?->expected_delivery_date)->format('Y-m-d') }}</div>
          <div>Branch: {{ $branch?->name ?? '-' }}</div>
        </td>
      </tr>
    </table>

    <div class="section">
      <h3>Received Items</h3>
      <table>
        <thead>
          <tr>
            <th style="width: 35%">Product</th>
            <th style="width: 12%">Expected</th>
            <th style="width: 12%">Received</th>
            <th style="width: 12%">Damaged</th>
            <th style="width: 12%">Condition</th>
            <th style="width: 17%">Notes</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($items as $item)
            <tr>
              <td>
                {{ $item->product?->product_name ?? 'Unknown Product' }}
                <div class="muted">{{ $item->product?->sku ?? '' }}</div>
              </td>
              <td>{{ $item->quantity_expected }}</td>
              <td>{{ $item->quantity_received }}</td>
              <td>{{ $item->quantity_damaged }}</td>
              <td>{{ ucfirst($item->condition) }}</td>
              <td>{{ $item->notes ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="muted">No items recorded.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="section">
      <h3>Logistics</h3>
      <table class="grid">
        <tr>
          <td>Delivery Note #: {{ $delivery_note_number ?? '-' }}</td>
          <td>Vehicle #: {{ $vehicle_number ?? '-' }}</td>
          <td>Driver: {{ $driver_name ?? '-' }}</td>
        </tr>
      </table>
    </div>

    <div class="section">
      <h3>Verification</h3>
      <table class="grid">
        <tr>
          <td>Received By: {{ trim(($received_by?->fname ?? '').' '.($received_by?->lname ?? '')) ?: '-' }}</td>
          <td>Verified By: {{ trim(($verified_by?->fname ?? '').' '.($verified_by?->lname ?? '')) ?: '-' }}</td>
        </tr>
      </table>
    </div>

    @if (!empty($discrepancy_notes) || !empty($quality_notes))
      <div class="section">
        <h3>Notes</h3>
        <div class="mt-8">
          <strong>Discrepancy:</strong> {{ $discrepancy_notes ?? '-' }}
        </div>
        <div class="mt-8">
          <strong>Quality:</strong> {{ $quality_notes ?? '-' }}
        </div>
      </div>
    @endif
  </body>
</html>
