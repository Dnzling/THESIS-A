<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['ecommerce_order_deliveries', 'order_deliveries'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (!Schema::hasColumn($tableName, 'courier_provider')) {
                    $table->string('courier_provider', 30)->default('internal')->after('courier_contact');
                }
                if (!Schema::hasColumn($tableName, 'lalamove_quotation_id')) {
                    $table->string('lalamove_quotation_id', 64)->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_order_id')) {
                    $table->string('lalamove_order_id', 64)->nullable()->unique();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_share_link')) {
                    $table->text('lalamove_share_link')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_status')) {
                    $table->string('lalamove_status', 40)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_service_type')) {
                    $table->string('lalamove_service_type', 50)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_pickup_stop_id')) {
                    $table->string('lalamove_pickup_stop_id', 64)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_dropoff_stop_id')) {
                    $table->string('lalamove_dropoff_stop_id', 64)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_price_breakdown')) {
                    $table->json('lalamove_price_breakdown')->nullable();
                }
                if (!Schema::hasColumn($tableName, 'lalamove_quote_expires_at')) {
                    $table->timestamp('lalamove_quote_expires_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $columns = [
                'courier_provider',
                'lalamove_quotation_id',
                'lalamove_order_id',
                'lalamove_share_link',
                'lalamove_status',
                'lalamove_service_type',
                'lalamove_pickup_stop_id',
                'lalamove_dropoff_stop_id',
                'lalamove_price_breakdown',
                'lalamove_quote_expires_at',
            ];

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns): void {
                $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($tableName, $column)));
                if ($existing) {
                    $table->dropColumn($existing);
                }
            });
        }
    }
};
