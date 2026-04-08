<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Legacy finance permission prefixes we want to prune.
     */
    private array $legacyPrefixes = [
        'finance.transactions.',   // e.g., finance.transactions.view.store
        'finance.documents.',      // e.g., finance.documents.view.store
        'finance.workflows.',      // e.g., finance.workflows.view.store
        'finance.settings.',       // e.g., finance.settings.view.store
    ];

    public function up(): void
    {
        $this->deleteLegacyPermissions();
    }

    public function down(): void
    {
        // No-op: we intentionally do not restore the legacy permissions.
    }

    private function deleteLegacyPermissions(): void
    {
        $ids = DB::table('permissions')
            ->where(function ($q) {
                foreach ($this->legacyPrefixes as $prefix) {
                    $q->orWhere('name', 'like', $prefix . '%');
                }
            })
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        // Clean junction tables first to avoid foreign key issues.
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('navigation_permissions')->whereIn('permission_id', $ids)->delete();

        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
