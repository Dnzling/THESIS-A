<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockIssue;
use App\Models\Inventory\StockReturn;
use App\Models\Inventory\StockTransfer;
use App\Models\Procurement\PurchaseOrder\PurchaseOrder;
use Illuminate\Http\Request;

class InventoryDocumentPrintController extends Controller
{
    public function purchaseOrder(Request $request, int $id)
    {
        $storeId = $this->authorizeStoreView($request, 'inventory.purchase_orders.view', 'inventory.purchase_orders.manage');
        $query = PurchaseOrder::with(['store', 'supplier', 'branch', 'createdBy.user', 'items.product', 'items.variation'])
            ->where('store_id', $storeId);
        $this->scopeAssignedBranch($query, $request);
        $order = $query->findOrFail($id);

        return $this->pdf('inventory.purchase-order-pdf', [
            'document' => $order,
            'store' => $order->store,
            'printedBy' => $this->printerName($request),
            'printedAt' => now(),
        ], 'PO-' . $order->po_number . '.pdf');
    }

    public function stockTransfer(Request $request, int $id)
    {
        $storeId = $this->authorizeStoreView($request, 'inventory.transfers.view', 'inventory.transfers.approve');
        $query = StockTransfer::with([
            'store', 'fromBranch', 'toBranch', 'items.product', 'items.variation',
            'requestedBy.user', 'senderApprovedBy.user', 'shippedBy.user', 'receivedBy.user',
        ])->where('store_id', $storeId);
        $this->scopeTransferBranch($query, $request);
        $transfer = $query->findOrFail($id);

        return $this->pdf('inventory.stock-transfer-pdf', [
            'document' => $transfer,
            'store' => $transfer->store,
            'printedBy' => $this->printerName($request),
            'printedAt' => now(),
        ], 'TRANSFER-' . $transfer->transfer_number . '.pdf');
    }

    public function stockIssue(Request $request, int $id)
    {
        $storeId = $this->storeId($request);
        $query = StockIssue::with([
            'store', 'branch', 'requester', 'approver', 'issuer',
            'items.inventoryItem.product', 'items.inventoryItem.variation',
        ])->where('store_id', $storeId);
        $this->scopeAssignedBranch($query, $request);
        $issue = $query->findOrFail($id);

        return $this->pdf('inventory.stock-issue-pdf', [
            'document' => $issue,
            'store' => $issue->store,
            'printedBy' => $this->printerName($request),
            'printedAt' => now(),
        ], 'ISSUE-' . $issue->issue_number . '.pdf');
    }

    public function stockReturn(Request $request, int $id)
    {
        $storeId = $this->storeId($request);
        $query = StockReturn::with([
            'store', 'fromBranch', 'toBranch', 'supplier', 'requestedBy.user', 'approvedBy.user',
            'shippedBy.user', 'receivedBy.user', 'items.product', 'items.variation',
        ])->where('store_id', $storeId);
        $this->scopeReturnBranch($query, $request);
        $stockReturn = $query->findOrFail($id);

        return $this->pdf('inventory.stock-return-pdf', [
            'document' => $stockReturn,
            'store' => $stockReturn->store,
            'printedBy' => $this->printerName($request),
            'printedAt' => now(),
        ], 'RETURN-' . $stockReturn->return_number . '.pdf');
    }

    private function authorizeStoreView(Request $request, string ...$permissions): int
    {
        $storeId = $this->storeId($request);
        $user = $request->user();
        $allowed = false;
        foreach ($permissions as $permission) {
            if ($user?->hasPermissionTo($permission, $storeId)) {
                $allowed = true;
                break;
            }
        }
        abort_unless($allowed, 403);

        return $storeId;
    }

    private function storeId(Request $request): int
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0, 403);

        return $storeId;
    }

    private function scopeAssignedBranch($query, Request $request): void
    {
        $branchId = (int) ($request->user()?->branch_id ?? $request->user()?->employee?->branch_id ?? 0);
        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }
    }

    private function scopeTransferBranch($query, Request $request): void
    {
        $branchId = (int) ($request->user()?->branch_id ?? $request->user()?->employee?->branch_id ?? 0);
        if ($branchId > 0) {
            $query->where(fn ($branches) => $branches
                ->where('from_branch_id', $branchId)
                ->orWhere('to_branch_id', $branchId));
        }
    }

    private function scopeReturnBranch($query, Request $request): void
    {
        $branchId = (int) ($request->user()?->branch_id ?? $request->user()?->employee?->branch_id ?? 0);
        if ($branchId > 0) {
            $query->where(fn ($branches) => $branches
                ->where('from_branch_id', $branchId)
                ->orWhere('to_branch_id', $branchId));
        }
    }

    private function printerName(Request $request): string
    {
        $user = $request->user();
        $name = trim(implode(' ', array_filter([
            $user?->fname,
            $user?->mname,
            $user?->lname,
        ])));

        return $name !== '' ? $name : (string) ($user?->email ?? 'Authenticated user');
    }

    private function pdf(string $view, array $data, string $filename)
    {
        $pdf = \PDF::loadView($view, $data)->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
        ]);
    }
}
