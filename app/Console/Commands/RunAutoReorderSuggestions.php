<?php

namespace App\Console\Commands;

use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\ReorderSuggestion;
use App\Services\Inventory\ReorderSuggestionNotificationService;
use App\Services\Inventory\ReorderSuggestionService;
use Illuminate\Console\Command;

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

            foreach (($result['suggestions'] ?? []) as $suggestion) {
                if ($suggestion instanceof ReorderSuggestion) {
                    app(ReorderSuggestionNotificationService::class)->notify($suggestion, 'scheduled_scan');
                }
            }

            $this->info("Store {$id}: refreshed {$generated} suggestion(s).");
        }

        return self::SUCCESS;
    }
}
