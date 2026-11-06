<?php

namespace App\Console\Commands;

use App\Models\Core\SystemNotification;
use App\Models\Inventory\BranchInventory;
use App\Services\Inventory\ReorderSuggestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunAutoReorderSuggestions extends Command
{
    protected $signature = 'inventory:reorder-suggestions:auto-run {--store_id=}';

    protected $description = 'Run a daily safety scan to catch reorder suggestions missed during stock updates.';

    public function __construct(private readonly ReorderSuggestionService $reorderSuggestionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $storeId = $this->option('store_id');

        $storeIds = BranchInventory::query()
            ->whereNotNull('store_id')
            ->when($storeId, fn ($query) => $query->where('store_id', (int) $storeId))
            ->distinct()
            ->pluck('store_id');

        if ($storeIds->isEmpty()) {
            $this->info('No stores with inventory were found for the reorder safety scan.');
            return self::SUCCESS;
        }

        foreach ($storeIds as $id) {
            $id = (int) $id;
            $result = $this->reorderSuggestionService->generateSuggestions($id);
            $generated = (int) ($result['total_generated'] ?? 0);

            if ($generated > 0) {
                $this->createGroupedNotifications($id, $generated);
            }

            $this->info("Store {$id}: refreshed {$generated} suggestion(s).");
        }

        return self::SUCCESS;
    }

    private function createGroupedNotifications(int $storeId, int $generatedCount): void
    {
        $recipientIds = $this->resolveRecipientUserIds($storeId);
        if (empty($recipientIds)) {
            return;
        }

        $title = "YOU HAVE {$generatedCount} REORDER SUGGESTIONS";
        $message = "{$generatedCount} new reorder suggestion(s) are ready for review.";

        foreach ($recipientIds as $userId) {
            SystemNotification::create([
                'store_id' => $storeId,
                'branch_id' => null,
                'user_id' => (int) $userId,
                'module' => 'inventory',
                'entity_type' => 'reorder_suggestion',
                'action' => 'auto_generated_grouped',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'suggestion_count' => $generatedCount,
                    'trigger' => 'scheduler',
                ],
                'link' => '/inventory/reorder-suggestions',
                'severity' => 'warning',
                'is_read' => false,
            ]);
        }
    }

    private function resolveRecipientUserIds(int $storeId): array
    {
        $permissionName = 'inventory.reorder_suggestions.view';

        $permissionId = DB::table('permissions')
            ->where('name', $permissionName)
            ->where('is_active', true)
            ->value('id');

        if (empty($permissionId)) {
            return [];
        }

        $roleBasedUserIds = DB::table('users')
            ->join('role_permissions', 'users.role_id', '=', 'role_permissions.role_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('role_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $grantedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('user_permissions.type', 'grant')
            ->where('user_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $revokedUserIds = DB::table('user_permissions')
            ->join('users', 'users.id', '=', 'user_permissions.user_id')
            ->where('users.store_id', $storeId)
            ->where('users.is_active', true)
            ->where('user_permissions.type', 'revoke')
            ->where('user_permissions.permission_id', (int) $permissionId)
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return collect(array_merge($roleBasedUserIds, $grantedUserIds))
            ->unique()
            ->reject(fn ($id) => in_array((int) $id, $revokedUserIds, true))
            ->values()
            ->all();
    }
}
