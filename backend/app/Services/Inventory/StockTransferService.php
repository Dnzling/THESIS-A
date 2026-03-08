<?php

namespace App\Services\Inventory;

use App\Models\Inventory\StockTransfer;
use App\Models\Inventory\StockTransferItem;
use App\Models\Inventory\BranchDistance;
use App\Models\Inventory\InventoryConfiguration;
use App\Models\Store\Branch;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    protected InventoryService $inventoryService;
    protected ApprovalService $approvalService;

    public function __construct(
        InventoryService $inventoryService,
        ApprovalService $approvalService
    ) {
        $this->inventoryService = $inventoryService;
        $this->approvalService = $approvalService;
    }

    /**
     * Request a stock transfer between branches
     */
    public function requestTransfer(
        int $storeId,
        int $fromBranchId,
        int $toBranchId,
        array $items,
        ?string $notes = null
    ): StockTransfer {
        try {
            DB::beginTransaction();

            // Validate request
            $this->validateTransferRequest($storeId, $fromBranchId, $toBranchId, $items);

            // Create transfer
            $transfer = StockTransfer::create([
                'store_id' => $storeId,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'requested_by' => auth()->id(),
                'requested_at' => now(),
                'status' => 'pending',
                'notes' => $notes,
            ]);

            // Add transfer items and calculate totals
            $totalValue = 0;
            $totalQuantity = 0;

            foreach ($items as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity_requested' => $item['quantity'],
                    'unit_value' => $item['unit_value'] ?? 0,
                ]);

                $totalValue += ($item['quantity'] * ($item['unit_value'] ?? 0));
                $totalQuantity += $item['quantity'];
            }

            // Calculate transfer cost
            $transferCost = $this->calculateTransferCost($storeId, $fromBranchId, $toBranchId);

            $transfer->update([
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'transfer_cost' => $transferCost,
            ]);

            // Create approval workflow
            $this->approvalService->createApprovalProcess(
                'stock_transfer',
                $transfer->id,
                $totalValue,
                $storeId
            );

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve transfer from warehouse (sender)
     */
    public function approveSender(int $transferId): StockTransfer
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);

            if ($transfer->status !== 'pending') {
                throw new Exception("Cannot approve transfer with status: {$transfer->status}");
            }

            // Reserve stock in source branch
            foreach ($transfer->items as $item) {
                $this->inventoryService->reserveStock(
                    $transfer->store_id,
                    $transfer->from_branch_id,
                    $item->product_id,
                    $item->quantity_requested,
                    $item->variation_id
                );
            }

            $transfer->update([
                'status' => 'approved_sender',
                'approved_sender_by' => auth()->id(),
                'approved_sender_at' => now(),
            ]);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject transfer
     */
    public function rejectTransfer(int $transferId, ?string $reason = null): StockTransfer
    {
        $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);

        $transfer->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Cancel approval process
        $this->approvalService->cancelApprovalProcess('stock_transfer', $transferId);

        return $transfer;
    }

    /**
     * Approve finance (if amount exceeds threshold)
     */
    public function approveFinance(int $transferId): StockTransfer
    {
        $transfer = StockTransfer::findOrFail($transferId);

        $transfer->update([
            'status' => 'approved_finance',
            'approved_finance_by' => auth()->id(),
            'approved_finance_at' => now(),
        ]);

        return $transfer;
    }

    /**
     * Ship transfer with tracking
     */
    public function shipTransfer(int $transferId, ?string $trackingNumber = null): StockTransfer
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);

            if (!in_array($transfer->status, ['approved_sender', 'approved_finance'])) {
                throw new Exception("Cannot ship transfer with status: {$transfer->status}");
            }

            // Update status to shipped
            $transfer->update([
                'status' => 'shipped',
                'shipped_by' => auth()->id(),
                'shipped_at' => now(),
                'tracking_number' => $trackingNumber ?? $this->generateTrackingNumber($transferId),
            ]);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Receive transfer (confirmation by destination branch)
     */
    public function receiveTransfer(int $transferId, array $receivedItems): StockTransfer
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);

            if ($transfer->status !== 'shipped') {
                throw new Exception("Cannot receive transfer with status: {$transfer->status}");
            }

            // Update transfer items with received quantities
            foreach ($receivedItems as $itemData) {
                $item = StockTransferItem::findOrFail($itemData['id']);

                $item->update([
                    'quantity_received' => $itemData['quantity_received'],
                    'received_at' => now(),
                    'received_by' => auth()->id(),
                    'condition_notes' => $itemData['condition_notes'] ?? null,
                ]);

                // Add stock to destination branch
                $this->inventoryService->addStock(
                    $transfer->store_id,
                    $transfer->to_branch_id,
                    $item->product_id,
                    $itemData['quantity_received'],
                    "Transfer from branch {$transfer->from_branch_id}",
                    $item->variation_id
                );

                // Calculate loss/gain
                $loss = $item->quantity_requested - $itemData['quantity_received'];
                if ($loss > 0) {
                    $this->inventoryService->createTransaction(
                        $transfer->store_id,
                        $transfer->from_branch_id,
                        'TRANSFER_LOSS',
                        ['quantity' => $loss, 'reason' => 'In-transit loss']
                    );
                }
            }

            // Complete transfer
            $transfer->update([
                'status' => 'received',
                'received_by' => auth()->id(),
                'received_at' => now(),
            ]);

            // Release original reservations (for rejected items)
            $this->releaseUnreceivedStock($transfer);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel a transfer
     */
    public function cancelTransfer(int $transferId, ?string $reason = null): StockTransfer
    {
        try {
            DB::beginTransaction();

            $transfer = StockTransfer::lockForUpdate()->findOrFail($transferId);

            if (in_array($transfer->status, ['received', 'rejected'])) {
                throw new Exception("Cannot cancel transfer with status: {$transfer->status}");
            }

            // Release any reserved stock
            if (in_array($transfer->status, ['approved_sender', 'approved_finance', 'shipped'])) {
                foreach ($transfer->items as $item) {
                    $this->inventoryService->releaseReservation(
                        $transfer->store_id,
                        $transfer->from_branch_id,
                        $item->product_id,
                        $item->quantity_requested,
                        $item->variation_id
                    );
                }
            }

            $transfer->update([
                'status' => 'cancelled',
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            DB::commit();

            return $transfer;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate transfer cost based on distance and configuration
     */
    protected function calculateTransferCost(int $storeId, int $fromBranchId, int $toBranchId): float
    {
        $config = InventoryConfiguration::where('store_id', $storeId)->first();

        if (!$config || $config->transfer_cost_model === 'none') {
            return 0;
        }

        if ($config->transfer_cost_model === 'fixed') {
            return $config->getFixedTransferCost() ?? 0;
        }

        if ($config->transfer_cost_model === 'distance_based') {
            $distance = BranchDistance::where('from_branch_id', $fromBranchId)
                ->where('to_branch_id', $toBranchId)
                ->first();

            if ($distance) {
                return ($distance->distance_km ?? 0) * $config->getTransferCostPerKm();
            }
        }

        return 0;
    }

    /**
     * Generate tracking number
     */
    protected function generateTrackingNumber(int $transferId): string
    {
        return 'ST-' . now()->format('Ymd') . '-' . str_pad($transferId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validate transfer request
     */
    protected function validateTransferRequest(int $storeId, int $fromBranchId, int $toBranchId, array $items): void
    {
        // Check branches exist
        Branch::where('store_id', $storeId)->findOrFail($fromBranchId);
        Branch::where('store_id', $storeId)->findOrFail($toBranchId);

        if ($fromBranchId === $toBranchId) {
            throw new Exception("Cannot transfer to the same branch");
        }

        // Check stock availability
        foreach ($items as $item) {
            $available = $this->inventoryService->getAvailableStock(
                $fromBranchId,
                $item['product_id'],
                $item['variation_id'] ?? null
            );

            if ($available < $item['quantity']) {
                throw new Exception("Insufficient stock for product {$item['product_id']}. Available: {$available}, Requested: {$item['quantity']}");
            }
        }
    }

    /**
     * Release unreceived stock back to inventory
     */
    protected function releaseUnreceivedStock(StockTransfer $transfer): void
    {
        foreach ($transfer->items as $item) {
            if (!$item->quantity_received || $item->quantity_received < $item->quantity_requested) {
                $unreceived = $item->quantity_requested - ($item->quantity_received ?? 0);

                $this->inventoryService->releaseReservation(
                    $transfer->store_id,
                    $transfer->from_branch_id,
                    $item->product_id,
                    $unreceived,
                    $item->variation_id
                );
            }
        }
    }

    /**
     * Get pending transfers for user's branch
     */
    public function getPendingTransfers(int $storeId, int $branchId): Collection
    {
        return StockTransfer::where('store_id', $storeId)
            ->where('to_branch_id', $branchId)
            ->where('status', 'approved_sender')
            ->get();
    }

    /**
     * Get transfer history for branch
     */
    public function getTransferHistory(int $storeId, int $branchId, ?string $period = null): Collection
    {
        $query = StockTransfer::where('store_id', $storeId)
            ->where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                    ->orWhere('to_branch_id', $branchId);
            });

        if ($period) {
            $query->whereDate('created_at', '>=', now()->subDays($period));
        }

        return $query->orderByDesc('created_at')->get();
    }
}
