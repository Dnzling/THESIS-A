<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_key', 50)->unique();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->decimal('monthly_price', 12, 2)->default(0);
            $table->decimal('yearly_price', 12, 2)->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('subscription_plans')->insert([
            [
                'plan_key' => 'simple',
                'name' => 'Simple',
                'description' => 'For single stores and single locations.',
                'monthly_price' => 1490,
                'yearly_price' => 14304,
                'features' => json_encode([
                    'Up to 500 furniture items',
                    'Basic inventory tracking',
                    '2 staff accounts',
                    'Email support',
                    'Basic reports',
                    '1 store location',
                ]),
                'is_featured' => false,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_key' => 'unlimited',
                'name' => 'Unlimited',
                'description' => 'For multi-store operations and fast growth.',
                'monthly_price' => 3500,
                'yearly_price' => 33600,
                'features' => json_encode([
                    'Unlimited furniture items',
                    'Advanced inventory management',
                    'Unlimited staff accounts',
                    'Priority phone and email support',
                    'Advanced analytics and reports',
                    'Multiple store locations',
                    'Custom API access',
                    '3D model integration',
                    'Bulk import and export',
                    'Custom branding',
                ]),
                'is_featured' => true,
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
