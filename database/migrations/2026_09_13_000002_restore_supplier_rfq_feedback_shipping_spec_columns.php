<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('supplier_rfq_feedbacks')) {
            return;
        }

        Schema::table('supplier_rfq_feedbacks', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'length_cm')) {
                $table->decimal('length_cm', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'width_cm')) {
                $table->decimal('width_cm', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'height_cm')) {
                $table->decimal('height_cm', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('supplier_rfq_feedbacks', 'weight_kg')) {
                $table->decimal('weight_kg', 12, 3)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Schema repair: preserve restored columns on rollback.
    }
};
