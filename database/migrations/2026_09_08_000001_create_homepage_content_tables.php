<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_module_slides', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->text('description');
            $table->json('benefits')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('homepage_furniture_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('slot')->unique();
            $table->string('label', 80);
            $table->string('description', 180)->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('model_path')->nullable();
            $table->string('model_format', 12)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('homepage_module_slides')->insert([
            ['name' => 'Store Management Module', 'description' => 'Control branches, store profiles, user roles, permissions, verification, and operating settings from one secure workspace.', 'benefits' => json_encode(['Keep every branch governed and consistent.']), 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Inventory Module', 'description' => 'See stock by branch, monitor reorder levels, record adjustments, and move products with a complete audit trail.', 'benefits' => json_encode(['Reduce stockouts and avoid excess inventory.']), 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Procurement Module', 'description' => 'Create purchase requisitions, collect supplier quotations, compare offers, issue purchase orders, and track receiving.', 'benefits' => json_encode(['Buy the right stock from the right supplier.']), 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Human Resources Module', 'description' => 'Manage employees, departments, attendance, shifts, leave, payroll records, and workforce policies in one place.', 'benefits' => json_encode(['Give your people team a reliable source of truth.']), 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Job Hiring Module', 'description' => 'Publish openings, organize applicants, schedule interviews, evaluate candidates, and move successful hires into onboarding.', 'benefits' => json_encode(['Turn recruitment into a clear, repeatable process.']), 'sort_order' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sales Module', 'description' => 'Handle customer orders, payments, receipts, fulfillment status, returns, and sales visibility across every branch.', 'benefits' => json_encode(['Keep each sale traceable from order to completion.']), 'sort_order' => 6, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Merchandising Module', 'description' => 'Organize catalogs, pricing, promotions, product media, variations, and immersive 3D assets for stronger product presentation.', 'benefits' => json_encode(['Make furniture easier to discover and trust.']), 'sort_order' => 7, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Logistics Module', 'description' => 'Coordinate delivery zones, vehicles, drivers, trips, proof of delivery, and return pickups from one operational view.', 'benefits' => json_encode(['Deliver customer orders with fewer handoff errors.']), 'sort_order' => 8, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('homepage_furniture_items')->insert([
            ['slot' => 1, 'label' => 'Chair', 'description' => 'Seating details and proportions', 'model_path' => 'platform/sofa.glb', 'model_format' => 'glb', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slot' => 2, 'label' => 'Table', 'description' => 'Surface, legs, and full silhouette', 'model_path' => 'platform/sofa.glb', 'model_format' => 'glb', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slot' => 3, 'label' => 'Cabinet', 'description' => 'Storage form and finish', 'model_path' => 'platform/sofa.glb', 'model_format' => 'glb', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slot' => 4, 'label' => 'Bed', 'description' => 'Frame scale and room presence', 'model_path' => 'platform/sofa.glb', 'model_format' => 'glb', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_furniture_items');
        Schema::dropIfExists('homepage_module_slides');
    }
};
