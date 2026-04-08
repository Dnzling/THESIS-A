<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $supplierLegacyPrefixes = [
        'supplier-portal.',
        'supplier-verifications.',
    ];

    private array $jobHiringLegacyPrefixes = [
        'view-job-',
        'create-job-',
        'edit-job-',
        'delete-job-',
        'update-application-status',
        'view-interviews',
        'schedule-interviews',
        'update-interviews',
        'delete-interviews',
        'view-job-applications',
        'delete-job-applications',
        'accept-offers',
        'decline-offers',
    ];

    public function up(): void
    {
        $this->deletePermissions($this->supplierLegacyPrefixes, true);
        $this->deletePermissions($this->jobHiringLegacyPrefixes, false);
    }

    public function down(): void
    {
        // no-op, we don't restore removed legacy permissions
    }

    private function deletePermissions(array $prefixes, bool $isPrefixMatch): void
    {
        $query = DB::table('permissions');
        $query->where(function ($q) use ($prefixes, $isPrefixMatch) {
            foreach ($prefixes as $p) {
                if ($isPrefixMatch) {
                    $q->orWhere('name', 'like', $p . '%');
                } else {
                    $q->orWhere('name', 'like', $p . '%');
                }
            }
        });

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('navigation_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
