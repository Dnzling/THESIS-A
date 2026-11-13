<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('suppliers', 'supplier_portal_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->foreignId('supplier_portal_id')
                    ->nullable()
                    ->after('store_id')
                    ->constrained('supplier_portals')
                    ->nullOnDelete();
                $table->index(['store_id', 'supplier_portal_id'], 'suppliers_store_portal_idx');
            });
        }

        // Backfill existing store supplier records when exactly one portal account
        // has the same normalized email. Ambiguous email matches are left unlinked.
        $portalEmails = DB::table('supplier_portals as portals')
            ->leftJoin('suppliers as source_suppliers', 'source_suppliers.id', '=', 'portals.supplier_id')
            ->leftJoin('users', 'users.id', '=', 'portals.user_id')
            ->selectRaw('portals.id, LOWER(TRIM(COALESCE(NULLIF(source_suppliers.email, ""), NULLIF(users.email, "")))) as normalized_email')
            ->whereNotNull(DB::raw('COALESCE(NULLIF(source_suppliers.email, ""), NULLIF(users.email, ""))'))
            ->get()
            ->filter(fn ($row) => $row->normalized_email !== '');

        $portalIdsByEmail = $portalEmails->groupBy('normalized_email');
        foreach ($portalIdsByEmail as $email => $portals) {
            if ($portals->count() !== 1) {
                continue;
            }

            DB::table('suppliers')
                ->whereNull('supplier_portal_id')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                ->update(['supplier_portal_id' => $portals->first()->id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('suppliers', 'supplier_portal_id')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropForeign(['supplier_portal_id']);
                $table->dropIndex('suppliers_store_portal_idx');
                $table->dropColumn('supplier_portal_id');
            });
        }
    }
};
