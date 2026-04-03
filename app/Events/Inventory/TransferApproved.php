<?php

namespace App\Events\Inventory;

use App\Models\Inventory\StockTransfer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransferApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public StockTransfer $transfer) {}
}
