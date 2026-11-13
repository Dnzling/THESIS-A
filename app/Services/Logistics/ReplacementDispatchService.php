<?php

namespace App\Services\Logistics;

use App\Models\CRM\EcommerceOrderReturn;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceDeliveryLog;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Logistics\DeliveryTrip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReplacementDispatchService
{
    public function dispatch(EcommerceOrderReturn $return, User $actor): void
    {
        $actor->loadMissing('employee');
        if (!$actor->employee?->id) {
            throw ValidationException::withMessages(['replacement' => 'An employee profile is required to record the inventory issue.']);
        }

        DB::transaction(function () use ($return, $actor): void {
            $case = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            $trip = DeliveryTrip::query()->findOrFail($case->replacement_trip_id);
            if (!in_array($case->status, ['approved', 'received'], true) || $case->replacement_status !== 'assigned' || $trip->status !== 'out_for_delivery') {
                throw ValidationException::withMessages(['replacement' => 'Only a reserved replacement assigned to a trip can be dispatched.']);
            }

            $inventory = BranchInventory::query()->where('store_id', $case->store_id)
                ->lockForUpdate()->findOrFail($case->replacement_inventory_id);
            $quantity = (int) ($case->received_quantity ?: $case->requested_quantity);
            if ($quantity <= 0 || (int) $inventory->quantity_reserved < $quantity || (int) $inventory->quantity_on_hand < $quantity) {
                throw ValidationException::withMessages(['replacement' => 'Reserved stock is no longer available.']);
            }

            $before = (int) $inventory->quantity_on_hand;
            $inventory->quantity_reserved -= $quantity;
            $inventory->quantity_on_hand -= $quantity;
            $inventory->suppressAutomaticTransactionLog();
            $inventory->save();

            InventoryTransaction::query()->create([
                'transaction_number' => 'INVTX-REPL-' . $case->id . '-' . now()->format('YmdHisv'),
                'store_id' => $case->store_id,
                'branch_id' => $inventory->branch_id,
                'product_id' => $inventory->product_id,
                'variation_id' => $inventory->variation_id,
                'transaction_type' => 'sale',
                'quantity_before' => $before,
                'quantity_change' => -$quantity,
                'quantity_after' => $inventory->quantity_on_hand,
                'reference_type' => 'ecommerce_return_replacement',
                'reference_id' => $case->id,
                'notes' => (float) $case->replacement_price_difference > 0
                    ? 'Higher-value replacement dispatched; price difference was recorded under ' . ($case->replacement_payment_reference ?: 'the return case') . '.'
                    : 'Replacement item dispatched for return case ' . ($case->return_number ?: '#' . $case->id) . '.',
                'unit_cost' => $inventory->average_cost ?? $inventory->unit_cost ?? 0,
                'total_value' => (float) ($inventory->average_cost ?? $inventory->unit_cost ?? 0) * $quantity,
                'created_by' => $actor->employee?->id,
                'transaction_date' => now(),
            ]);

            $case->replacement_status = 'out_for_delivery';
            $case->replacement_dispatched_at = now();
            $case->save();

            if ($case->source_type === 'pos' && $case->salesOrder && $case->salesOrder->status !== 'out_for_delivery') {
                $case->salesOrder->status = 'out_for_delivery';
                $case->salesOrder->save();
            } elseif ($case->source_type !== 'pos' && $case->order && $case->order->status !== 'out_for_delivery') {
                $order = $case->order;
                $previousStatus = (string) $order->status;
                $order->status = 'out_for_delivery';
                $order->save();
                if ($delivery = $order->delivery) {
                    EcommerceDeliveryLog::query()->create([
                        'delivery_id' => $delivery->id,
                        'order_id' => $order->id,
                        'store_id' => $order->store_id,
                        'event_type' => 'status_updated',
                        'status_from' => $previousStatus,
                        'status_to' => 'out_for_delivery',
                        'message' => 'Replacement dispatched on a delivery trip.',
                        'created_by' => $actor->id,
                    ]);
                }
            }
        });
    }
}

