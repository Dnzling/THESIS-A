<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columnType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', 'goods_receipts')
            ->where('COLUMN_NAME', 'receipt_status')
            ->value('COLUMN_TYPE');

        if (!is_string($columnType) || !str_starts_with($columnType, 'enum(')) {
            throw new RuntimeException('Expected an enum receipt_status column on goods_receipts.');
        }

        preg_match_all("/'((?:''|[^'])*)'/", $columnType, $matches);
        $statuses = array_values(array_unique(array_merge($matches[1], ['draft'])));
        $values = implode(',', array_map(
            fn (string $status) => "'" . str_replace("'", "''", $status) . "'",
            $statuses
        ));

        DB::statement("ALTER TABLE goods_receipts MODIFY receipt_status ENUM({$values}) NOT NULL DEFAULT 'full'");
    }

    public function down(): void
    {
        // Keep draft receipts intact if this migration is rolled back.
    }
};
