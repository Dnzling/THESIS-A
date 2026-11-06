<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_rfq_feedbacks', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('supplier_portal_id')->constrained('suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('supplier_rfq_feedbacks', 'response_source')) {
                $table->string('response_source', 20)->default('portal')->after('supplier_id');
            }
            if (! Schema::hasColumn('supplier_rfq_feedbacks', 'response_channel')) {
                $table->string('response_channel', 20)->nullable()->after('response_source');
            }
            if (! Schema::hasColumn('supplier_rfq_feedbacks', 'response_received_at')) {
                $table->timestamp('response_received_at')->nullable()->after('response_channel');
            }
            if (! Schema::hasColumn('supplier_rfq_feedbacks', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->after('response_received_at')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('supplier_rfq_feedbacks as feedbacks')
            ->join('supplier_portals as portals', 'portals.id', '=', 'feedbacks.supplier_portal_id')
            ->whereNull('feedbacks.supplier_id')
            ->update(['feedbacks.supplier_id' => DB::raw('portals.supplier_id')]);

        $this->replacePortalUniqueKey(
            'supplier_rfq_feedbacks',
            'uq_rfq_feedback_portal_item',
            'uq_rfq_feedback_supplier_item',
            'rfq_item_id'
        );

        Schema::table('supplier_po_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_po_feedbacks', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('supplier_portal_id')->constrained('suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('supplier_po_feedbacks', 'response_source')) {
                $table->string('response_source', 20)->default('portal')->after('supplier_id');
            }
            if (! Schema::hasColumn('supplier_po_feedbacks', 'response_channel')) {
                $table->string('response_channel', 20)->nullable()->after('response_source');
            }
            if (! Schema::hasColumn('supplier_po_feedbacks', 'response_date')) {
                $table->date('response_date')->nullable()->after('response_channel');
            }
            if (! Schema::hasColumn('supplier_po_feedbacks', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('delivery_notes');
            }
            if (! Schema::hasColumn('supplier_po_feedbacks', 'recorded_by')) {
                $table->foreignId('recorded_by')->nullable()->after('attachment_path')->constrained('users')->nullOnDelete();
            }
        });

        DB::table('supplier_po_feedbacks as feedbacks')
            ->join('supplier_portals as portals', 'portals.id', '=', 'feedbacks.supplier_portal_id')
            ->whereNull('feedbacks.supplier_id')
            ->update(['feedbacks.supplier_id' => DB::raw('portals.supplier_id')]);

        $this->replacePortalUniqueKey(
            'supplier_po_feedbacks',
            'uq_po_feedback_portal_po',
            'uq_po_feedback_supplier_po',
            'purchase_order_id'
        );

        if (DB::getDriverName() === 'mysql') {
            $this->appendEnumValue('purchase_order_shipments', 'status', 'en_route_to_supplier');
        }
    }

    private function replacePortalUniqueKey(string $tableName, string $oldIndex, string $newIndex, string $uniqueColumn): void
    {
        $foreignKey = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$tableName, 'supplier_portal_id']
        );

        if ($foreignKey) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropForeign($foreignKey->CONSTRAINT_NAME));
        }

        $oldIndexExists = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $oldIndex]
        );

        Schema::table($tableName, function (Blueprint $table) use ($oldIndexExists, $oldIndex) {
            if ($oldIndexExists) {
                $table->dropUnique($oldIndex);
            }
            $table->unsignedBigInteger('supplier_portal_id')->nullable()->change();
        });

        $newIndexExists = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $newIndex]
        );

        if (! $newIndexExists) {
            Schema::table($tableName, fn (Blueprint $table) => $table->unique(['supplier_id', $uniqueColumn], $newIndex));
        }

        $currentForeignKey = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$tableName, 'supplier_portal_id']
        );

        if (! $currentForeignKey) {
            Schema::table($tableName, fn (Blueprint $table) => $table->foreign('supplier_portal_id')->references('id')->on('supplier_portals')->cascadeOnDelete());
        }
    }

    public function down(): void
    {
        // Preserve offline responses and pickup history on rollback.
    }

    private function appendEnumValue(string $table, string $column, string $value): void
    {
        $definition = DB::selectOne("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        if (! $definition || ! str_starts_with(strtolower((string) $definition->Type), 'enum(')) {
            return;
        }

        preg_match_all("/'([^']*)'/", (string) $definition->Type, $matches);
        $values = array_values(array_unique(array_merge($matches[1] ?? [], [$value])));
        $quoted = implode(',', array_map(fn (string $item) => DB::connection()->getPdo()->quote($item), $values));
        $nullable = strtoupper((string) $definition->Null) === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $definition->Default === null ? '' : ' DEFAULT ' . DB::connection()->getPdo()->quote((string) $definition->Default);

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$quoted}) {$nullable}{$default}");
    }
};
