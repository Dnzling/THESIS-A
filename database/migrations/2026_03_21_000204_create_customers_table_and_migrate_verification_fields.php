<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified');
                $table->boolean('verification_required')->default(false);
                $table->decimal('verification_trigger_amount', 12, 2)->nullable();
                $table->timestamp('verification_triggered_at')->nullable();
                $table->text('verification_rejection_reason')->nullable();
                $table->unsignedBigInteger('verification_reviewed_by')->nullable();
                $table->timestamp('verification_reviewed_at')->nullable();
                $table->timestamps();

                $table->foreign('verification_reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasColumn('users', 'customer_verification_status')) {
            DB::table('users')
                ->select([
                    'id',
                    'customer_verification_status',
                    'customer_verification_required',
                    'customer_verification_trigger_amount',
                    'customer_verification_triggered_at',
                    'customer_verification_rejection_reason',
                    'customer_verification_reviewed_by',
                    'customer_verification_reviewed_at',
                ])
                ->orderBy('id')
                ->chunk(500, function ($rows) {
                    $payload = [];
                    $now = now();
                    foreach ($rows as $row) {
                        $payload[] = [
                            'user_id' => $row->id,
                            'verification_status' => $row->customer_verification_status ?? 'unverified',
                            'verification_required' => (bool) ($row->customer_verification_required ?? false),
                            'verification_trigger_amount' => $row->customer_verification_trigger_amount,
                            'verification_triggered_at' => $row->customer_verification_triggered_at,
                            'verification_rejection_reason' => $row->customer_verification_rejection_reason,
                            'verification_reviewed_by' => $row->customer_verification_reviewed_by,
                            'verification_reviewed_at' => $row->customer_verification_reviewed_at,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (!empty($payload)) {
                        DB::table('customers')->upsert(
                            $payload,
                            ['user_id'],
                            [
                                'verification_status',
                                'verification_required',
                                'verification_trigger_amount',
                                'verification_triggered_at',
                                'verification_rejection_reason',
                                'verification_reviewed_by',
                                'verification_reviewed_at',
                                'updated_at',
                            ]
                        );
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropForeign(['customer_verification_reviewed_by']);
                } catch (\Throwable $e) {
                    // Ignore missing foreign constraint.
                }
            });

            Schema::table('users', function (Blueprint $table) {
                $columns = [
                    'customer_verification_status',
                    'customer_verification_required',
                    'customer_verification_trigger_amount',
                    'customer_verification_triggered_at',
                    'customer_verification_rejection_reason',
                    'customer_verification_reviewed_by',
                    'customer_verification_reviewed_at',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'customer_verification_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('customer_verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                    ->default('unverified')
                    ->after('is_active');
                $table->boolean('customer_verification_required')->default(false)->after('customer_verification_status');
                $table->decimal('customer_verification_trigger_amount', 12, 2)->nullable()->after('customer_verification_required');
                $table->timestamp('customer_verification_triggered_at')->nullable()->after('customer_verification_trigger_amount');
                $table->text('customer_verification_rejection_reason')->nullable()->after('customer_verification_triggered_at');
                $table->unsignedBigInteger('customer_verification_reviewed_by')->nullable()->after('customer_verification_rejection_reason');
                $table->timestamp('customer_verification_reviewed_at')->nullable()->after('customer_verification_reviewed_by');
                $table->foreign('customer_verification_reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('customers')) {
            DB::table('customers')->orderBy('id')->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('users')
                        ->where('id', $row->user_id)
                        ->update([
                            'customer_verification_status' => $row->verification_status,
                            'customer_verification_required' => $row->verification_required,
                            'customer_verification_trigger_amount' => $row->verification_trigger_amount,
                            'customer_verification_triggered_at' => $row->verification_triggered_at,
                            'customer_verification_rejection_reason' => $row->verification_rejection_reason,
                            'customer_verification_reviewed_by' => $row->verification_reviewed_by,
                            'customer_verification_reviewed_at' => $row->verification_reviewed_at,
                        ]);
                }
            });

            Schema::dropIfExists('customers');
        }
    }
};

