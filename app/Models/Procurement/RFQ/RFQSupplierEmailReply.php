<?php

namespace App\Models\Procurement\RFQ;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RFQSupplierEmailReply extends Model
{
    protected $table = 'rfq_supplier_email_replies';

    protected $fillable = [
        'rfq_supplier_id',
        'message_id',
        'from_email',
        'from_name',
        'subject',
        'body',
        'received_at',
    ];

    protected $casts = ['received_at' => 'datetime'];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(RFQSupplier::class, 'rfq_supplier_id');
    }
}
