<?php

namespace App\Events\Inventory;

use App\Models\Inventory\StockAdjustment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdjustmentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public StockAdjustment $adjustment) {}
}
