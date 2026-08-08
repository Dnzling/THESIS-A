<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Logistics\ReturnPickupController;
use App\Http\Controllers\Api\Sales\SalesReturnController;
use App\Http\Controllers\Api\Sales\SalesRefundController;
use App\Models\Core\User;
use App\Models\Ecommerce\EcommerceOrderItem;
use App\Models\Ecommerce\EcommerceOrder;
use App\Models\Ecommerce\EcommerceOrderReturn;
use App\Models\Inventory\InventoryTransaction;
use App\Models\ProductCatalog\Product;
use App\Models\Sales\SalesRefund;
use App\Models\Store\Store;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestReturnFlow extends Command
{
    protected $signature = 'test:return-flow
        {--commit : Persist created records instead of rolling back}
        {--store_id= : Store scope (defaults to first store)}
        {--user_id= : Acting user (defaults to first active user in store)}
        {--order_item_id= : Use a specific ecommerce_order_items.id}
        {--driver_user_id= : Driver user id to assign (defaults to another active user in store)}
        {--return_type=refund : Resolution path to test (refund or replacement)}
    ';

    protected $description = 'Smoke-test ecommerce return approval, pickup, inventory inspection, and refund/replacement resolution.';

    public function handle(): int
    {
        if (!Schema::hasTable('ecommerce_order_returns')) {
            $this->error('Missing table: ecommerce_order_returns');
            return 1;
        }
        if (!Schema::hasTable('logistics_return_pickups')) {
            $this->error('Missing table: logistics_return_pickups. Run migrations first.');
            return 1;
        }

        $store = $this->option('store_id')
            ? Store::query()->find((int) $this->option('store_id'))
            : Store::query()->first();

        if (!$store) {
            $this->error('No store found.');
            return 1;
        }

        $user = $this->resolveActingUser((int) $store->id);
        if (!$user) {
            $this->error('No suitable acting user found (needs store_id + active).');
            return 1;
        }

        $this->info('Using store_id=' . $store->id . ' user_id=' . $user->id . ' (' . ($user->email ?? 'no-email') . ')');

        Auth::login($user);

        $commit = (bool) $this->option('commit');
        $returnType = strtolower((string) $this->option('return_type'));
        if (!in_array($returnType, ['refund', 'replacement'], true)) {
            $this->error('--return_type must be refund or replacement.');
            return 1;
        }

        DB::beginTransaction();
        try {
            [$orderItem, $order] = $this->resolveOrderItem($store->id, $user->id);

            $this->info('Using order_item_id=' . $orderItem->id . ' order_id=' . $order->id . ' order_number=' . ($order->order_number ?? '-'));
            $this->info('Order status=' . ($order->status ?? '-') . ' (must be delivered/completed for real customer endpoint)');

            $return = EcommerceOrderReturn::query()->create([
                'order_id' => (int) $order->id,
                'order_item_id' => (int) $orderItem->id,
                'store_id' => (int) $store->id,
                'user_id' => (int) $order->user_id,
                'requested_quantity' => 1,
                'reason' => 'Smoke test return request',
                'details' => 'Created by artisan test:return-flow',
                'evidence_urls' => [],
                'status' => 'pending_verification',
            ]);

            $this->line('Return created id=' . $return->id . ' status=' . $return->status);

            $salesController = app(SalesReturnController::class);

            // Approve
            $approveReq = Request::create("/api/sales/returns/{$return->id}/status", 'PUT', [
                'status' => 'approved',
                'return_type' => $returnType,
                'review_notes' => 'Approved by smoke test',
            ]);
            $approveReq->setUserResolver(fn () => $user);
            $approveRes = $salesController->updateStatus($approveReq, $return->fresh());
            $approvePayload = $approveRes->getData(true);
            $this->line('Approved: ' . json_encode(['success' => $approvePayload['success'] ?? null, 'status' => $approvePayload['data']['status'] ?? null]));

            // Schedule pickup
            $scheduledAt = now()->addDay()->format('Y-m-d H:i:s');
            $pickupReq = Request::create("/api/sales/returns/{$return->id}/pickup", 'POST', [
                'scheduled_at' => $scheduledAt,
                'pickup_name' => $order->shipping_name,
                'pickup_phone' => $order->shipping_phone,
                'pickup_address' => $order->shipping_address,
                'notes' => 'Pickup scheduled by smoke test',
            ]);
            $pickupReq->setUserResolver(fn () => $user);
            $pickupRes = $salesController->createPickup($pickupReq, $return->fresh());
            $pickupPayload = $pickupRes->getData(true);
            $pickupId = $pickupPayload['data']['pickup']['id'] ?? null;
            if (!$pickupId) {
                throw new \RuntimeException('Pickup was not created. Response=' . json_encode($pickupPayload));
            }
            $this->line('Pickup created id=' . $pickupId . ' scheduled_at=' . $scheduledAt);

            // Logistics: assign driver and upload proof
            $driver = $this->resolveDriverUser((int) $store->id, (int) $user->id);
            if (!$driver) {
                throw new \RuntimeException('No suitable driver user found.');
            }
            $this->info('Assigning driver_user_id=' . $driver->id);

            $logisticsController = app(ReturnPickupController::class);
            $pickupModel = \App\Models\Logistics\ReturnPickup::query()->findOrFail((int) $pickupId);

            $assignReq = Request::create("/api/logistics/return-pickups/{$pickupId}/assign-driver", 'POST', [
                'driver_user_id' => $driver->id,
            ]);
            $assignReq->setUserResolver(fn () => $user);
            $assignRes = $logisticsController->assignDriver($assignReq, $pickupModel);
            $assignPayload = $assignRes->getData(true);
            $this->line('Driver assigned: ' . json_encode(['success' => $assignPayload['success'] ?? null]));

            [$photoFile, $sigFile] = $this->makeTempImages();
            $proofReq = Request::create("/api/logistics/return-pickups/{$pickupId}/proof", 'POST', [
                'notes' => 'Proof uploaded by smoke test',
            ], [], [
                'photo' => $photoFile,
                'signature' => $sigFile,
            ]);
            $proofReq->setUserResolver(fn () => $user);
            $proofRes = $logisticsController->uploadProof($proofReq, $pickupModel->fresh());
            $proofPayload = $proofRes->getData(true);
            $this->line('Pickup proof uploaded: ' . json_encode(['success' => $proofPayload['success'] ?? null, 'status' => $proofPayload['data']['status'] ?? null]));

            // Receive (Inventory posting)
            $invBefore = InventoryTransaction::query()->where('reference_type', 'ecommerce_order_return')->where('reference_id', $return->id)->count();
            $refundBefore = SalesRefund::query()->where('order_type', 'ecommerce_return')->where('order_id', $return->id)->count();
            $receiveReq = Request::create("/api/sales/returns/{$return->id}/receive", 'POST', [
                'received_quantity' => 1,
                'condition' => 'good',
                'notes' => 'Received by smoke test',
            ]);
            $receiveReq->setUserResolver(fn () => $user);
            $receiveRes = $salesController->receive($receiveReq, EcommerceOrderReturn::query()->findOrFail($return->id));
            $receivePayload = $receiveRes->getData(true);
            $this->line('Received (inventory): ' . json_encode(['success' => $receivePayload['success'] ?? null, 'status' => $receivePayload['data']['status'] ?? null]));

            $invAfter = InventoryTransaction::query()->where('reference_type', 'ecommerce_order_return')->where('reference_id', $return->id)->count();
            if ($invAfter <= $invBefore) {
                throw new \RuntimeException('Expected inventory transaction to be created.');
            }

            if ($returnType === 'refund') {
                // Inventory automatically notifies Finance by creating a pending refund.
                $refundAfter = SalesRefund::query()->where('order_type', 'ecommerce_return')->where('order_id', $return->id)->count();
                if ($refundAfter <= $refundBefore) {
                    throw new \RuntimeException('Expected sales_refunds record to be created.');
                }

                $createdRefund = SalesRefund::query()
                    ->where('order_type', 'ecommerce_return')
                    ->where('order_id', $return->id)
                    ->latest('id')
                    ->firstOrFail();
                $refundApprovalReq = Request::create("/api/sales/refunds/{$createdRefund->id}/status", 'PUT', [
                    'status' => 'approved',
                    'notes' => 'Approved by smoke test',
                ]);
                $refundApprovalReq->setUserResolver(fn () => $user);
                $refundApprovalRes = app(SalesRefundController::class)->updateStatus($refundApprovalReq, $createdRefund);
                $refundApprovalPayload = $refundApprovalRes->getData(true);
                $this->line('Refund approved: ' . json_encode(['status' => $refundApprovalPayload['data']['status'] ?? null]));
            }

            $returnFinal = EcommerceOrderReturn::query()->findOrFail($return->id);
            $expectedStatus = $returnType === 'refund' ? 'refunded' : 'replaced';
            if ((string) $returnFinal->status !== $expectedStatus) {
                throw new \RuntimeException("Expected return to be {$expectedStatus}, got: {$returnFinal->status}");
            }

            if ($commit) {
                DB::commit();
                $this->warn('Committed records (use --commit only when you want to keep the smoke test data).');
            } else {
                DB::rollBack();
                $this->info('Rolled back (default). No records persisted.');
            }

            $this->info($returnType === 'refund'
                ? 'Refund path validated (Inventory + Finance).'
                : 'Replacement path validated (Inventory receipt + replacement issue).');

            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Smoke test failed: ' . $e->getMessage());
            return 1;
        } finally {
            Auth::logout();
        }
    }

    private function resolveActingUser(int $storeId): ?User
    {
        if ($this->option('user_id')) {
            return User::query()->find((int) $this->option('user_id'));
        }

        return User::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    private function resolveDriverUser(int $storeId, int $excludeUserId): ?User
    {
        if ($this->option('driver_user_id')) {
            return User::query()->find((int) $this->option('driver_user_id'));
        }

        return User::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where('id', '!=', $excludeUserId)
            ->orderBy('id')
            ->first();
    }

    private function resolveOrderItem(int $storeId, int $actingUserId): array
    {
        if ($this->option('order_item_id')) {
            $item = EcommerceOrderItem::query()->with('order')->findOrFail((int) $this->option('order_item_id'));
            return [$item, $item->order];
        }

        $item = EcommerceOrderItem::query()
            ->with('order')
            ->whereHas('order', function ($q) use ($storeId) {
                $q->where('store_id', $storeId)
                    ->whereIn('status', ['delivered']);
            })
            ->whereDoesntHave('returnRequests', function ($q) {
                $q->whereIn('status', ['pending_verification', 'approved', 'received', 'refund_pending', 'refunded', 'replaced']);
            })
            ->orderByDesc('id')
            ->first();

        if (!$item || !$item->order) {
            $product = Product::query()->first();
            if (!$product) {
                throw new \RuntimeException('No eligible delivered order item found, and no product exists to create a dummy order.');
            }

            $order = EcommerceOrder::query()->create([
                'store_id' => $storeId,
                'user_id' => $actingUserId,
                'order_number' => 'TEST-RET-' . time(),
                'status' => 'delivered',
                'payment_method' => 'cod',
                'payment_status' => 'paid',
                'shipping_name' => 'Test Customer',
                'shipping_phone' => '0000000000',
                'shipping_email' => 'test@example.com',
                'shipping_address' => 'Test Address',
                'subtotal' => 1000,
                'tax_amount' => 0,
                'shipping_fee' => 0,
                'discount_amount' => 0,
                'total_amount' => 1000,
                'notes' => 'Created by artisan test:return-flow',
                'placed_at' => now(),
            ]);

            $item = EcommerceOrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'branch_inventory_id' => null,
                'product_name' => $product->product_name ?? 'Test Product',
                'sku' => $product->sku ?? null,
                'quantity' => 1,
                'unit_price' => 1000,
                'tax_rate' => 0,
                'line_subtotal' => 1000,
                'line_tax' => 0,
                'line_total' => 1000,
            ]);

            $item->load('order');
        }

        // Ensure customer user exists on order; sales/logistics acting user can be different.
        if (empty($item->order->user_id)) {
            throw new \RuntimeException('Selected order has no user_id.');
        }

        // If acting user is not in same store, fail fast (tenant scope).
        if ((int) $item->order->store_id !== $storeId) {
            throw new \RuntimeException('Order store_id mismatch.');
        }

        return [$item, $item->order];
    }

    private function makeTempImages(): array
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/azxZC8AAAAASUVORK5CYII='
        );

        $photoPath = tempnam(sys_get_temp_dir(), 'ret_photo_') . '.png';
        $sigPath = tempnam(sys_get_temp_dir(), 'ret_sig_') . '.png';
        file_put_contents($photoPath, $png);
        file_put_contents($sigPath, $png);

        $photo = new UploadedFile($photoPath, 'photo.png', 'image/png', null, true);
        $sig = new UploadedFile($sigPath, 'signature.png', 'image/png', null, true);

        return [$photo, $sig];
    }
}
