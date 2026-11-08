<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'ecommerce_order_returns';

        // This migration may be retried after MySQL has applied some DDL before
        // a later statement failed, so each schema change is guarded.
        foreach (['order_id', 'order_item_id', 'user_id'] as $column) {
            $this->dropForeignKeyForColumn($tableName, $column);
        }

        Schema::table($tableName, function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->change();
            $table->foreignId('order_item_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->change();
        });

        $this->addColumnIfMissing($tableName, 'source_type', function (Blueprint $table): void {
            $table->string('source_type', 20)->default('ecommerce')->after('store_id');
        });
        $this->addColumnIfMissing($tableName, 'return_contact_name', function (Blueprint $table): void {
            $table->string('return_contact_name')->nullable()->after('source_type');
        });
        $this->addColumnIfMissing($tableName, 'return_contact_phone', function (Blueprint $table): void {
            $table->string('return_contact_phone', 50)->nullable()->after('return_contact_name');
        });
        $this->addColumnIfMissing($tableName, 'return_pickup_address', function (Blueprint $table): void {
            $table->text('return_pickup_address')->nullable()->after('return_contact_phone');
        });
        $this->addColumnIfMissing($tableName, 'sales_order_id', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('order_item_id');
        });
        $this->addColumnIfMissing($tableName, 'sales_order_item_id', function (Blueprint $table): void {
            $table->unsignedBigInteger('sales_order_item_id')->nullable()->after('sales_order_id');
        });
        $this->addColumnIfMissing($tableName, 'orphaned_order_item_id', function (Blueprint $table): void {
            $table->unsignedBigInteger('orphaned_order_item_id')->nullable()->after('sales_order_item_id');
        });

        $this->moveLegacyPosItemReferences();
        $this->preserveOrphanedEcommerceItemReferences();

        $this->addForeignKeyIfMissing($tableName, 'order_id', 'ecommerce_orders', 'cascade');
        $this->addForeignKeyIfMissing($tableName, 'order_item_id', 'ecommerce_order_items', 'cascade');
        $this->addForeignKeyIfMissing($tableName, 'user_id', 'users', 'cascade');
        $this->addForeignKeyIfMissing($tableName, 'sales_order_id', 'sales_pos_orders', 'restrict');
        $this->addForeignKeyIfMissing($tableName, 'sales_order_item_id', 'sales_pos_order_items', 'restrict');

        $this->addIndexIfMissing($tableName, ['sales_order_id', 'status'], 'ecom_return_pos_order_status_idx');
        $this->addIndexIfMissing($tableName, ['sales_order_item_id'], 'ecom_return_pos_item_idx');
    }

    public function down(): void
    {
        if (Schema::hasColumn('ecommerce_order_returns', 'source_type')
            && DB::table('ecommerce_order_returns')->where('source_type', 'pos')->exists()) {
            throw new RuntimeException('POS-linked CRM returns exist; migrate or remove them before rolling back this migration.');
        }
        if (Schema::hasColumn('ecommerce_order_returns', 'orphaned_order_item_id')
            && DB::table('ecommerce_order_returns')->whereNotNull('orphaned_order_item_id')->exists()) {
            throw new RuntimeException('Returns with missing ecommerce items exist; reconcile them before rolling back this migration.');
        }

        foreach (['order_id', 'order_item_id', 'user_id', 'sales_order_id', 'sales_order_item_id'] as $column) {
            $this->dropForeignKeyForColumn('ecommerce_order_returns', $column);
        }

        foreach (['ecom_return_pos_order_status_idx', 'ecom_return_pos_item_idx'] as $index) {
            if ($this->indexExists('ecommerce_order_returns', $index)) {
                Schema::table('ecommerce_order_returns', fn (Blueprint $table) => $table->dropIndex($index));
            }
        }

        $columns = [
            'source_type', 'return_contact_name', 'return_contact_phone', 'return_pickup_address',
            'sales_order_id', 'sales_order_item_id', 'orphaned_order_item_id',
        ];
        $existingColumns = array_values(array_filter($columns, fn (string $column): bool => Schema::hasColumn('ecommerce_order_returns', $column)));
        if ($existingColumns) {
            Schema::table('ecommerce_order_returns', fn (Blueprint $table) => $table->dropColumn($existingColumns));
        }

        Schema::table('ecommerce_order_returns', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable(false)->change();
            $table->foreignId('order_item_id')->nullable(false)->change();
            $table->foreignId('user_id')->nullable(false)->change();
        });

        $this->addForeignKeyIfMissing('ecommerce_order_returns', 'order_id', 'ecommerce_orders', 'cascade');
        $this->addForeignKeyIfMissing('ecommerce_order_returns', 'order_item_id', 'ecommerce_order_items', 'cascade');
        $this->addForeignKeyIfMissing('ecommerce_order_returns', 'user_id', 'users', 'cascade');
    }

    private function addColumnIfMissing(string $tableName, string $columnName, Closure $definition): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, $definition);
        }
    }

    private function moveLegacyPosItemReferences(): void
    {
        if (!Schema::hasTable('sales_pos_order_items') || !Schema::hasTable('sales_pos_orders')) {
            return;
        }

        // Older POS return rows were stored in the ecommerce item column. Move
        // only references that are absent from ecommerce but valid for POS.
        DB::table('ecommerce_order_returns as returns')
            ->join('sales_pos_order_items as pos_items', 'pos_items.id', '=', 'returns.order_item_id')
            ->join('sales_pos_orders as pos_orders', 'pos_orders.id', '=', 'pos_items.order_id')
            ->leftJoin('ecommerce_order_items as ecommerce_items', 'ecommerce_items.id', '=', 'returns.order_item_id')
            ->whereNull('ecommerce_items.id')
            ->whereNotNull('returns.order_item_id')
            ->update([
                'returns.source_type' => 'pos',
                'returns.sales_order_id' => DB::raw('pos_orders.id'),
                'returns.sales_order_item_id' => DB::raw('pos_items.id'),
                'returns.order_id' => null,
                'returns.order_item_id' => null,
            ]);
    }

    private function preserveOrphanedEcommerceItemReferences(): void
    {
        $orphanedRows = DB::table('ecommerce_order_returns as returns')
            ->leftJoin('ecommerce_order_items as items', 'items.id', '=', 'returns.order_item_id')
            ->whereNotNull('returns.order_item_id')
            ->whereNull('items.id')
            ->orderBy('returns.id')
            ->select('returns.id', 'returns.order_item_id')
            ->get();

        foreach ($orphanedRows as $row) {
            DB::table('ecommerce_order_returns')
                ->where('id', $row->id)
                ->update([
                    'orphaned_order_item_id' => $row->order_item_id,
                    'order_item_id' => null,
                ]);
        }
    }

    private function addForeignKeyIfMissing(string $tableName, string $column, string $referencedTable, string $deleteAction): void
    {
        if ($this->foreignKeyExistsForColumn($tableName, $column)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $deleteAction): void {
            $foreign = $table->foreign($column)->references('id')->on($referencedTable);
            $deleteAction === 'restrict' ? $foreign->restrictOnDelete() : $foreign->cascadeOnDelete();
        });
    }

    private function dropForeignKeyForColumn(string $tableName, string $column): void
    {
        $foreignKey = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? '
            . 'AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$tableName, $column]
        );

        if ($foreignKey) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($foreignKey->CONSTRAINT_NAME));
        }
    }

    private function foreignKeyExistsForColumn(string $tableName, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function addIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, fn (Blueprint $table) => $table->index($columns, $indexName));
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::raw('DATABASE()'))
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
