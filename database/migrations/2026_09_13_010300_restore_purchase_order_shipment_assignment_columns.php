<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_shipments')) {
            return;
        }

        Schema::table('purchase_order_shipments', function (Blueprint $table): void {
            if (! Schema::hasColumn('purchase_order_shipments', 'assistant_user_ids')) {
                $table->json('assistant_user_ids')->nullable()->after('driver_contact');
            }

            if (! Schema::hasColumn('purchase_order_shipments', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('destination_address');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_order_shipments')) {
            return;
        }

        Schema::table('purchase_order_shipments', function (Blueprint $table): void {
            if (Schema::hasColumn('purchase_order_shipments', 'expected_delivery_date')) {
                $table->dropColumn('expected_delivery_date');
            }

            if (Schema::hasColumn('purchase_order_shipments', 'assistant_user_ids')) {
                $table->dropColumn('assistant_user_ids');
            }
        });
    }
};
