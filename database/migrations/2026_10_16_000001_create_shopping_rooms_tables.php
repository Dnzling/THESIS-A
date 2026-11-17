<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shopping_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_rooms', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('shopping_room_id')->constrained('shopping_rooms')->cascadeOnDelete();
            $table->primary(['product_id', 'shopping_room_id']);
        });

        $names = ['Living Room', 'Bedroom', 'Dining Room', 'Kitchen', 'Home Office', 'Outdoor', 'Kids Room'];
        $existingImages = [
            'living-room' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1100&q=85',
            'bedroom' => 'https://images.unsplash.com/photo-1618220179428-22790b461013?auto=format&fit=crop&w=1100&q=85',
            'home-office' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1100&q=85',
        ];
        foreach ($names as $order => $name) {
            $slug = Str::slug($name);
            DB::table('shopping_rooms')->insert([
                'name' => $name, 'slug' => $slug, 'display_order' => $order,
                'image_path' => $existingImages[$slug] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $rooms = DB::table('shopping_rooms')->pluck('id', 'slug');
        foreach (DB::table('tags')->where('tag_type', 'Room')->whereNull('deleted_at')->distinct()->pluck('tag_name') as $legacyName) {
            $slug = Str::slug($legacyName);
            if ($slug === '' || isset($rooms[$slug])) continue;
            $id = DB::table('shopping_rooms')->insertGetId([
                'name' => $legacyName, 'slug' => $slug, 'is_active' => false,
                'display_order' => count($rooms), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $rooms[$slug] = $id;
        }
        DB::table('product_tags as pt')->join('tags as t', 't.id', '=', 'pt.tag_id')
            ->join('products as p', 'p.id', '=', 'pt.product_id')
            ->where('t.tag_type', 'Room')->whereNull('t.deleted_at')
            ->whereColumn('t.store_id', 'p.store_id')->where('p.product_type', 'finished_good')
            ->select('pt.product_id', 't.tag_name')->orderBy('pt.product_id')
            ->chunk(500, function ($tags) use ($rooms) {
                foreach ($tags as $tag) {
                    $roomId = $rooms[Str::slug($tag->tag_name)] ?? null;
                    if ($roomId) DB::table('product_rooms')->updateOrInsert([
                        'product_id' => $tag->product_id, 'shopping_room_id' => $roomId,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_rooms');
        Schema::dropIfExists('shopping_rooms');
    }
};
