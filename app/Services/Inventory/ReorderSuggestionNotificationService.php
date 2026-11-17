<?php

namespace App\Services\Inventory;

use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Models\Inventory\ReorderSuggestion;
use App\Services\Core\PermissionService;

class ReorderSuggestionNotificationService
{
    private const REQUIRED_PERMISSION = 'warehouse.reorder.view';

    public function notify(ReorderSuggestion $suggestion, ?string $trigger = null): void
    {
        if ($suggestion->status !== 'pending' || !$suggestion->branch || $suggestion->branch->branch_type !== 'warehouse') {
            return;
        }

        $storeId = (int) $suggestion->branch->store_id;
        $permissionService = app(PermissionService::class);
        $recipients = User::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->get(['id', 'store_id', 'role_id'])
            ->filter(fn (User $user) => $permissionService->userHasPermission(
                $user,
                self::REQUIRED_PERMISSION,
                $storeId,
            ));

        if ($recipients->isEmpty()) {
            return;
        }

        $productName = $suggestion->product?->product_name ?? 'A product';
        $variation = $suggestion->variation?->variation_name;
        $name = $variation ? "{$productName} ({$variation})" : $productName;
        $message = sprintf(
            '%s has %s available; the reorder trigger is %s. Suggested replenishment: %s.',
            $name,
            rtrim(rtrim(number_format((float) $suggestion->current_stock, 2), '0'), '.'),
            rtrim(rtrim(number_format((float) data_get($suggestion->metadata, 'calculation.reorder_trigger', 0), 2), '0'), '.'),
            rtrim(rtrim(number_format((float) $suggestion->suggested_quantity, 2), '0'), '.'),
        );

        foreach ($recipients as $user) {
            SystemNotification::query()->updateOrCreate(
                [
                    'store_id' => $storeId,
                    'user_id' => (int) $user->id,
                    'entity_type' => 'reorder_suggestion',
                    'entity_id' => (int) $suggestion->id,
                    'action' => 'reorder_triggered',
                ],
                [
                    'branch_id' => (int) $suggestion->branch_id,
                    'module' => 'warehouse',
                    'title' => 'Reorder point reached',
                    'message' => $message,
                    'data' => [
                        'required_permission' => self::REQUIRED_PERMISSION,
                        'trigger' => $trigger ?? 'stock_update',
                        'suggestion_id' => (int) $suggestion->id,
                        'product_id' => (int) $suggestion->product_id,
                        'variation_id' => $suggestion->variation_id ? (int) $suggestion->variation_id : null,
                        'current_stock' => (float) $suggestion->current_stock,
                        'reorder_trigger' => (float) data_get($suggestion->metadata, 'calculation.reorder_trigger', 0),
                        'suggested_quantity' => (float) $suggestion->suggested_quantity,
                    ],
                    'link' => '/warehouse/reorder',
                    'severity' => 'warning',
                    'is_read' => false,
                    'read_at' => null,
                ],
            );
        }
    }
}
