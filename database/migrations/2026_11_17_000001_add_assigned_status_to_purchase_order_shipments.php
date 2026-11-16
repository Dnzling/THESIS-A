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

        $definition = DB::selectOne("SHOW COLUMNS FROM `purchase_order_shipments` LIKE 'status'");
        if (! $definition || ! str_starts_with(strtolower((string) $definition->Type), 'enum(')) {
            return;
        }

        preg_match_all("/'([^']*)'/", (string) $definition->Type, $matches);
        $values = array_values(array_unique(array_merge($matches[1] ?? [], ['assigned'])));
        $quoted = implode(',', array_map(fn (string $value) => DB::connection()->getPdo()->quote($value), $values));
        $nullable = strtoupper((string) $definition->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $definition->Default === null
            ? ''
            : ' DEFAULT ' . DB::connection()->getPdo()->quote((string) $definition->Default);

        DB::statement("ALTER TABLE `purchase_order_shipments` MODIFY `status` ENUM({$quoted}) {$nullable}{$default}");
    }

    public function down(): void
    {
        // Keep assigned pickup records intact when rolling back application code.
    }
};
