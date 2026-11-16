<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM purchase_requisitions LIKE 'status'");
        preg_match_all("/'([^']*)'/", (string) ($column->Type ?? ''), $matches);
        $statuses = array_values(array_unique(array_merge($matches[1] ?? [], [
            'out_for_delivery',
            'received',
        ])));

        $enumValues = implode(',', array_map(
            fn (string $status): string => DB::connection()->getPdo()->quote($status),
            $statuses
        ));

        DB::statement("ALTER TABLE purchase_requisitions MODIFY status ENUM({$enumValues}) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Keep lifecycle statuses so existing requisitions retain valid states.
    }
};
