<?php

namespace App\Events\Inventory;

use App\Models\Inventory\StockAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertGenerated
{
    use Dispatchable, SerializesModels;

    public function __construct(public StockAlert $alert) {}
}
