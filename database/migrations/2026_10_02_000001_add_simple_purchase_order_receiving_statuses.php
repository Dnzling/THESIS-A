<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columnType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'purchase_orders')
            ->where('COLUMN_NAME', 'status')
            ->value('COLUMN_TYPE');

        if (!is_string($columnType) || !str_starts_with($columnType, 'enum(')) {
            throw new RuntimeException('Expected an enum status column on purchase_orders.');
        }

        preg_match_all("/'((?:''|[^'])*)'/", $columnType, $matches);
        $statuses = array_values(array_unique(array_merge($matches[1], [
            'pending_receipt',
            'partially_received',
            'goods_received',
        ])));
        $values = implode(',', array_map(
            fn (string $status) => "'" . str_replace("'", "''", $status) . "'",
            $statuses
        ));

        DB::statement("ALTER TABLE purchase_orders MODIFY status ENUM({$values}) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        // Preserve new statuses and existing orders if this data migration is rolled back.
    }
};
