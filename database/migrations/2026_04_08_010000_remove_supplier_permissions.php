<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->deleteSupplierPermissions();
    }

    public function down(): void
    {
        // no-op: we intentionally do not restore removed supplier permissions
    }

    private function deleteSupplierPermissions(): void
    {
        $ids = DB::table('permissions')
            ->where('name', 'like', 'supplier.%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('navigation_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
