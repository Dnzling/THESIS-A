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
            ->where('status', 'draft');
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

        // Check quantity match between PO and Invoice
        $poQuantity = $this->purchaseOrder->items->sum('quantity_ordered');
        $invoiceQuantity = $this->items->sum('quantity_invoiced');

        if ($poQuantity != $invoiceQuantity) {
            $result['issues'][] = "Quantity mismatch: PO={$poQuantity}, Invoice={$invoiceQuantity}";
        } else {
            $result['po_match'] = true;
        }

        // Check quantity match between GRN and Invoice
        if ($this->goodsReceipt) {
            $grnQuantity = $this->goodsReceipt->items->sum('quantity_received');
            if ($grnQuantity != $invoiceQuantity) {
                $result['issues'][] = "GRN quantity mismatch: GRN={$grnQuantity}, Invoice={$invoiceQuantity}";
            } else {
                $result['grn_match'] = true;
            }
        }

        // Check amount match
        $poAmount = $this->purchaseOrder->total_amount;
        $invoiceAmount = $this->net_amount;
        $tolerance = $poAmount * 0.02; // 2% tolerance

        if (abs($poAmount - $invoiceAmount) > $tolerance) {
            $result['issues'][] = "Amount variance: PO={$poAmount}, Invoice={$invoiceAmount}, Variance=" . number_format(
                (($invoiceAmount - $poAmount) / $poAmount) * 100,
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
        return $this->match_status === 'matched' && $this->status === 'draft';
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
