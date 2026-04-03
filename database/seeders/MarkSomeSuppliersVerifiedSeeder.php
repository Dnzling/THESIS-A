<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Procurement\Supplier\Supplier;

class MarkSomeSuppliersVerifiedSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $portals = DB::table('supplier_portals')
                ->whereIn('status', ['pending', 'rejected'])
                ->whereNotNull('supplier_id')
                ->orderBy('id')
                ->limit(3)
                ->get();

            foreach ($portals as $portal) {
                DB::table('supplier_portals')
                    ->where('id', $portal->id)
                    ->update([
                        'status' => 'approved',
                        'verified_by' => $portal->verified_by ?? 1,
                        'verified_at' => now(),
                        'rejection_reason' => null,
                        'updated_at' => now(),
                    ]);

                Supplier::where('id', $portal->supplier_id)->update([
                    'status' => 'active',
                ]);
            }
        });
    }
}
