<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stores', 'barangay')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->string('barangay', 150)->nullable()->after('city');
            });
        }

        // Preserve the registered location for existing stores by using their main branch.
        DB::table('stores')
            ->whereNull('barangay')
            ->orderBy('id')
            ->chunkById(200, function ($stores): void {
                foreach ($stores as $store) {
                    $barangay = DB::table('branches')
                        ->where('store_id', $store->id)
                        ->orderByDesc('is_main_branch')
                        ->orderBy('id')
                        ->value('barangay');

                    if ($barangay !== null && trim((string) $barangay) !== '') {
                        DB::table('stores')->where('id', $store->id)->update([
                            'barangay' => $barangay,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'barangay')) {
            Schema::table('stores', function (Blueprint $table): void {
                $table->dropColumn('barangay');
            });
        }
    }
};
