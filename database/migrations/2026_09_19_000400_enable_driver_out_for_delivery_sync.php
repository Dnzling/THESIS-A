<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            foreach ([
                'purchase_order_shipments',
                'purchase_orders',
                'purchase_requisitions',
                'stock_transfers',
            ] as $table) {
                $this->appendEnumValue($table, 'status', 'out_for_delivery');
            }
        }

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_transfers', 'driver_user_id')) {
                $table->foreignId('driver_user_id')->nullable()->after('driver_name')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_transfers', 'delivery_status')) {
                $table->string('delivery_status', 30)->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('stock_transfers', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')->nullable();
            }
            if (!Schema::hasColumn('stock_transfers', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            if (!Schema::hasColumn('stock_transfers', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('stock_transfers', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('stock_transfers', 'current_address')) {
                $table->text('current_address')->nullable();
            }
        });

        Schema::table('purchase_order_shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_shipments', 'out_for_delivery_at')) {
                $table->timestamp('out_for_delivery_at')->nullable()->after('dispatched_at');
            }
        });
    }

    public function down(): void
    {
        // Delivery statuses are intentionally retained to avoid truncating live records.
        Schema::table('stock_transfers', function (Blueprint $table) {
            foreach (['current_address', 'current_longitude', 'current_latitude', 'delivered_at', 'out_for_delivery_at', 'delivery_status'] as $column) {
                if (Schema::hasColumn('stock_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('stock_transfers', 'driver_user_id')) {
                $table->dropConstrainedForeignId('driver_user_id');
            }
        });
        if (Schema::hasColumn('purchase_order_shipments', 'out_for_delivery_at')) {
            Schema::table('purchase_order_shipments', fn (Blueprint $table) => $table->dropColumn('out_for_delivery_at'));
        }
    }

    private function appendEnumValue(string $table, string $column, string $value): void
    {
        $definition = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (!$definition || !str_starts_with(strtolower((string) $definition->Type), 'enum(')) {
            return;
        }

        preg_match_all("/'([^']*)'/", (string) $definition->Type, $matches);
        $values = array_values(array_unique(array_merge($matches[1] ?? [], [$value])));
        $quoted = implode(',', array_map(fn (string $item) => DB::connection()->getPdo()->quote($item), $values));
        $nullable = strtoupper((string) $definition->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $definition->Default === null
            ? ''
            : ' DEFAULT ' . DB::connection()->getPdo()->quote((string) $definition->Default);

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$quoted}) {$nullable}{$default}");
    }
};
