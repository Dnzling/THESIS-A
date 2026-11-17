<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('resignation_date')->nullable();
            $table->date('last_working_day')->nullable();
            $table->string('resignation_reason')->nullable();
            $table->string('handover_status')->nullable();
            $table->text('resignation_notes')->nullable();
            $table->string('resignation_letter_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'resignation_date', 'last_working_day', 'resignation_reason',
                'handover_status', 'resignation_notes', 'resignation_letter_path',
            ]);
        });
    }
};
