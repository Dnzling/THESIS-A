<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rfq_suppliers')) {
            Schema::table('rfq_suppliers', function (Blueprint $table): void {
                if (!Schema::hasColumn('rfq_suppliers', 'email_sent_at')) {
                    $table->timestamp('email_sent_at')->nullable();
                }
                if (!Schema::hasColumn('rfq_suppliers', 'email_delivery_status')) {
                    $table->string('email_delivery_status', 30)->nullable();
                }
                if (!Schema::hasColumn('rfq_suppliers', 'email_send_error')) {
                    $table->text('email_send_error')->nullable();
                }
                if (!Schema::hasColumn('rfq_suppliers', 'email_reply_token')) {
                    $table->string('email_reply_token', 64)->nullable()->unique();
                }
            });
        }

        if (!Schema::hasTable('rfq_supplier_email_replies')) {
            Schema::create('rfq_supplier_email_replies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('rfq_supplier_id')->constrained('rfq_suppliers')->cascadeOnDelete();
                $table->string('message_id', 255)->unique();
                $table->string('from_email', 255);
                $table->string('from_name', 255)->nullable();
                $table->string('subject', 998)->nullable();
                $table->longText('body')->nullable();
                $table->timestamp('received_at');
                $table->timestamps();
                $table->index(['rfq_supplier_id', 'received_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_supplier_email_replies');
        if (Schema::hasTable('rfq_suppliers')) {
            Schema::table('rfq_suppliers', function (Blueprint $table): void {
                foreach (['email_reply_token', 'email_send_error', 'email_delivery_status', 'email_sent_at'] as $column) {
                    if (Schema::hasColumn('rfq_suppliers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
