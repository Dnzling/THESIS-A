<?php

namespace App\Events\Inventory;

use App\Models\Inventory\BranchInventory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLevelChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public BranchInventory $inventory,
        public int $oldQuantity,
        public int $newQuantity,
        public string $reason
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('inventory'),
        ];
    }
}
