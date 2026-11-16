<?php
// backend/app/Models/Procurement/Invoice/Invoice.php

namespace App\Models\Procurement\Invoice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use App\Models\Procurement\Receiving\GoodsReceipt;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Store\Store;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'invoice_number',
        'supplier_id',
        'purchase_order_id',
        'goods_receipt_id',
        'invoice_date',
        'due_date',
        'invoice_amount',
        'currency',
        'tax_amount',
        'shipping_cost',
        'discount_amount',
        'net_amount',
        'invoice_file_path',
        'status',
        'match_status',
        'match_notes',
        'payment_status',
        'payment_date',
        'payment_amount',
        'payment_method',
        'paid_to_bank_name',
        'paid_to_account_name',
        'paid_to_account_number_masked',
        'paid_to_account_type',
        'paid_to_bank_branch',
        'remarks',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    protected $appends = [
        'subtotal',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Scope for invoices awaiting match
     */
    public function scopePendingMatch($query)
    {
        return $query->where('match_status', 'pending')
            ->whereIn('status', ['draft', 'pending_approval']);
    }

    /**
     * Scope for matched invoices
     */
    public function scopeMatched($query)
    {
        return $query->where('match_status', 'matched')
            ->where('status', 'approved');
    }

    /**
     * Scope for invoices with exceptions
     */
    public function scopeExceptions($query)
    {
        return $query->where('match_status', 'exception');
    }

    /**
     * Scope for invoices awaiting payment
     */
    public function scopeAwaitingPayment($query)
    {
        return $query->where('payment_status', 'pending')
            ->where('match_status', 'matched');
    }

    /**
     * Perform 3-way matching
     */
    public function performThreeWayMatch(): array
    {
        $result = [
            'status' => 'pending',
            'po_match' => false,
            'grn_match' => false,
            'amount_match' => false,
            'issues' => [],
        ];

        if (!$this->purchaseOrder) {
            $result['issues'][] = 'PO not found';
            return $result;
        }

        $resolutions = \App\Models\Procurement\Receiving\GoodsReceiptResolution::query()
            ->where('purchase_order_id', $this->purchase_order_id)
            ->get(['status', 'supplier_action', 'supplier_credit_amount', 'items']);
        $unresolvedDiscrepancy = $resolutions->contains(fn ($resolution) => in_array(
            $resolution->status,
            ['pending_supplier', 'accepted', 'delivery_submitted', 'credit_submitted', 'disputed', 'rejected'],
            true
        ));
        if ($unresolvedDiscrepancy) {
            $result['issues'][] = 'Goods receipt discrepancy is awaiting supplier or procurement resolution';
        }

        $creditedUnits = $resolutions
            ->where('status', 'resolved')
            ->whereIn('supplier_action', ['issue_credit', 'dispute'])
            ->flatMap(fn ($resolution) => $resolution->items ?? [])
            ->sum('quantity_due');
        $unresolvedUnits = $resolutions
            ->whereIn('status', ['pending_supplier', 'accepted', 'delivery_submitted', 'credit_submitted', 'disputed', 'rejected'])
            ->whereNotIn('supplier_action', ['issue_credit', 'dispute'])
            ->flatMap(fn ($resolution) => $resolution->items ?? [])
            ->sum('quantity_due');

        // Invoice quantity must match accepted usable units plus quantities
        // formally settled by supplier credit or Procurement's accepted dispute.
        $poQuantity = max(0, (int) $this->purchaseOrder->items->sum('quantity_received') + (int) $creditedUnits - (int) $unresolvedUnits);
        $invoiceQuantity = $this->items->sum('quantity_invoiced');

        if ($poQuantity != $invoiceQuantity) {
            $result['issues'][] = "Quantity mismatch: PO={$poQuantity}, Invoice={$invoiceQuantity}";
        } else {
            $result['po_match'] = true;
        }

        // Check quantity match between GRN and Invoice
        if ($this->goodsReceipt || $this->purchase_order_id) {
            // A deficient delivery can be completed by one or more immutable
            // follow-up GRNs. Finance matches the cumulative accepted quantity.
            $grnQuantity = \App\Models\Procurement\Receiving\GoodsReceiptItem::query()
                ->whereHas('goodsReceipt', fn ($query) => $query
                    ->where('purchase_order_id', $this->purchase_order_id)
                    ->where('receipt_status', '!=', 'draft'))
                ->selectRaw('COALESCE(SUM(quantity_received - quantity_damaged), 0) as accepted_quantity')
                ->value('accepted_quantity');
            $grnQuantity = max(0, (int) $grnQuantity + (int) $creditedUnits - (int) $unresolvedUnits);
            if ($grnQuantity != $invoiceQuantity) {
                $result['issues'][] = "GRN quantity mismatch: GRN={$grnQuantity}, Invoice={$invoiceQuantity}";
            } else {
                $result['grn_match'] = true;
            }
        }

        // 3-way amount comparison should use product subtotal only.
        // Taxes/freight/discounts are invoice-level charges and are tracked separately.
        $approvedCredits = (float) $resolutions
            ->where('status', 'resolved')
            ->whereIn('supplier_action', ['issue_credit', 'dispute'])
            ->sum('supplier_credit_amount');
        $unresolvedAmount = (float) $resolutions
            ->whereIn('status', ['pending_supplier', 'accepted', 'delivery_submitted', 'credit_submitted', 'disputed', 'rejected'])
            ->whereNotIn('supplier_action', ['issue_credit', 'dispute'])
            ->sum(function ($resolution) {
                $items = collect($resolution->items ?? []);
                $poItems = $this->purchaseOrder->items->keyBy('id');
                return $items->sum(function (array $item) use ($poItems) {
                    $poItem = $poItems->get((int) ($item['purchase_order_item_id'] ?? 0));
                    return (int) ($item['quantity_due'] ?? 0) * (float) ($poItem?->unit_cost ?? 0);
                });
            });
        $poAmount = max(0, (float) ($this->purchaseOrder->subtotal ?? 0) - $approvedCredits - $unresolvedAmount);
        $invoiceAmount = (float) ($this->invoice_amount ?? 0);
        $tolerance = max($poAmount * 0.02, 0.01); // 2% tolerance, minimum 0.01
        $base = $poAmount > 0 ? $poAmount : 1;

        if (abs($poAmount - $invoiceAmount) > $tolerance) {
            $result['issues'][] = "Subtotal variance: PO={$poAmount}, Invoice={$invoiceAmount}, Variance=" . number_format(
                (($invoiceAmount - $poAmount) / $base) * 100,
                2
            ) . "%";
        } else {
            $result['amount_match'] = true;
        }

        // Determine overall match status
        if (empty($result['issues'])) {
            $result['status'] = 'matched';
        } else {
            $result['status'] = 'exception';
        }

        $this->match_status = $result['status'];
        $this->match_notes = json_encode($result['issues']);
        $this->save();

        return $result;
    }

    public function getSubtotalAttribute(): string
    {
        return (string) $this->invoice_amount;
    }

    /**
     * Get match status color for UI
     */
    public function getMatchStatusColorAttribute(): string
    {
        return match($this->match_status) {
            'matched' => 'success',
            'exception' => 'danger',
            'pending' => 'warning',
            default => 'info',
        };
    }

    /**
     * Check if invoice can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'pending_approval' && $this->match_status !== 'exception';
    }

    /**
     * Approve invoice
     */
    public function approve(): bool
    {
        if (!$this->canApprove()) {
            return false;
        }

        $this->status = 'approved';
        $this->payment_status = 'pending';
        return $this->save();
    }

    /**
     * Schedule payment
     */
    public function schedulePayment(\DateTime $paymentDate): bool
    {
        if ($this->payment_status !== 'pending') {
            return false;
        }

        $this->payment_date = $paymentDate;
        return $this->save();
    }

    /**
     * Mark as paid
     */
    public function markAsPaid(string $paymentMethod, float $amount): bool
    {
        $this->payment_status = 'paid';
        $this->payment_method = $paymentMethod;
        $this->payment_amount = $amount;
        return $this->save();
    }
}
