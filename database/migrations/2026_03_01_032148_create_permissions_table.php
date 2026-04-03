<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1️⃣ Permissions table (base table, no foreign keys)
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // e.g., 'merchandising.products.view'
            $table->string('display_name', 200); // Human-readable name
            $table->string('module', 50); // e.g., 'merchandising', 'hr', 'inventory'
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module', 'is_active']);
        });

        // 2️⃣ Role-Permission pivot (depends on roles + permissions)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['role_id', 'permission_id']);
        });

        // 3️⃣ User-specific permission overrides (depends on users + permissions)
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('permission_id');
            $table->enum('type', ['grant', 'revoke'])->default('grant');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['user_id', 'permission_id']);
        });

        // 4️⃣ Navigation items (no dependencies, but has self-referencing foreign key)
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // e.g., 'merchandising.products'
            $table->string('display_name', 200);
            $table->string('module', 50); // 'merchandising', 'hr', 'inventory', etc.
            $table->string('section', 200)->nullable(); // For grouping in UI (e.g., 'Products', 'Settings')
            $table->string('route_name', 200); // Vue router name
            $table->string('route_path', 200); // /merchandising/products
            $table->string('icon', 100)->nullable(); // pi pi-box
            $table->unsignedBigInteger('parent_id')->nullable(); // For nested menus
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable(); // { "subtitle": "...", "badge": "New" }
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('navigation_items')
                ->onDelete('cascade');

            $table->index(['module', 'is_active', 'display_order']);
        });

        // 5️⃣ Navigation-Permission link (depends on navigation_items + permissions)
        Schema::create('navigation_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('navigation_item_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('navigation_item_id')
                ->references('id')
                ->on('navigation_items')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->unique(['navigation_item_id', 'permission_id'], 'nav_perm_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in REVERSE order (to avoid foreign key constraint errors)
        Schema::dropIfExists('navigation_permissions');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};