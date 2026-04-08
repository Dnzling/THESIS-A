<?php

namespace App\Console\Commands;

use App\Models\Procurement\Inventory\ProcurementInventory;
use Illuminate\Console\Command;

class PopulateProcurementQty extends Command
{
    protected $signature = 'procurement:populate-qty';
    protected $description = 'Populate procurement inventory with realistic quantities';

    public function handle()
    {
        $this->info('Populating procurement inventory with quantities...');

        $inventory = ProcurementInventory::all();
        $updated = 0;

        foreach ($inventory as $item) {
            $item->update([
                'available_qty' => rand(50, 500),
                'on_order_qty' => rand(0, 100),
                'received_qty' => rand(100, 1000),
                'pending_receive_qty' => rand(0, 50)
            ]);
            $updated++;
        }

        $this->info("✓ Successfully updated {$updated} procurement inventory records with quantities");
    }
}
