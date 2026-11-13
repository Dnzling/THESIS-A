<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCatalog\ShoppingRoom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShoppingRoomController extends Controller
{
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        return response()->json(['data' => ShoppingRoom::orderBy('display_order')->orderBy('name')->get()
            ->map(fn ($room) => $this->payload($room))]);
    }

    public function update(Request $request, ShoppingRoom $room): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('shopping_rooms', 'name')->ignore($room->id)],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $data['name'] = trim($data['name']);
        if ($data['name'] === '') throw ValidationException::withMessages(['name' => 'Enter a room name.']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ecommerce/rooms', 'public');
            $oldPath = $room->image_path;
            $data['image_path'] = $path;
            $room->update(collect($data)->except('image')->all());
            if ($oldPath && str_starts_with($oldPath, 'ecommerce/rooms/')) Storage::disk('public')->delete($oldPath);
        } else {
            $room->update(collect($data)->except('image')->all());
        }

        return response()->json(['data' => $this->payload($room->fresh())]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('shopping_rooms', 'name')],
            'is_active' => ['required', 'boolean'],
            'display_order' => ['required', 'integer', 'min:0'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $name = trim($data['name']);
        $slug = Str::slug($name);
        if ($slug === '') throw ValidationException::withMessages(['name' => 'Enter a valid room name.']);
        if (ShoppingRoom::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'A room with this name already exists.']);
        }

        $path = $request->file('image')->store('ecommerce/rooms', 'public');
        try {
            $room = ShoppingRoom::create([
                'name' => $name,
                'slug' => $slug,
                'image_path' => $path,
                'is_active' => $data['is_active'],
                'display_order' => $data['display_order'],
            ]);
        } catch (\Throwable $error) {
            Storage::disk('public')->delete($path);
            throw $error;
        }

        return response()->json(['data' => $this->payload($room)], 201);
    }

    public function publicIndex(): JsonResponse
    {
        return response()->json(['data' => ShoppingRoom::where('is_active', true)
            ->orderBy('display_order')->orderBy('name')->get()->map(fn ($room) => $this->payload($room))]);
    }

    private function payload(ShoppingRoom $room): array
    {
        return [
            'id' => $room->id, 'name' => $room->name, 'slug' => $room->slug,
            'is_active' => $room->is_active, 'display_order' => $room->display_order,
            'image_url' => $room->image_path
                ? (str_starts_with($room->image_path, 'https://') ? $room->image_path : Storage::disk('public')->url($room->image_path))
                : null,
        ];
    }
}
