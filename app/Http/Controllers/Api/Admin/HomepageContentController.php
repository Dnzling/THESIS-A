<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\HomepageFurnitureItem;
use App\Models\Admin\HomepageModuleSlide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HomepageContentController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->content(true)]);
    }

    public function index(): JsonResponse
    {
        $this->authorizeSuperAdmin();

        return response()->json(['success' => true, 'data' => $this->content(false)]);
    }

    public function updateModule(Request $request, HomepageModuleSlide $module): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1200'],
            'benefits' => ['nullable', 'array', 'max:5'],
            'benefits.*' => ['string', 'max:180'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('marketing/home/modules', 'public');
            $this->deleteManagedFile($module->image_path);
            $validated['image_path'] = $newPath;
        }

        unset($validated['image']);
        $module->update($validated);

        return response()->json(['success' => true, 'message' => 'Module showcase updated.', 'data' => $this->moduleData($module->fresh())]);
    }

    public function storeModule(Request $request): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1200'],
            'benefits' => ['nullable', 'array', 'max:5'],
            'benefits.*' => ['string', 'max:180'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('marketing/home/modules', 'public');
        }

        unset($validated['image']);
        $module = HomepageModuleSlide::create($validated);

        return response()->json(['success' => true, 'message' => 'Module showcase added.', 'data' => $this->moduleData($module)], 201);
    }

    public function destroyModule(HomepageModuleSlide $module): JsonResponse
    {
        $this->authorizeSuperAdmin();
        $this->deleteManagedFile($module->image_path);
        $module->delete();

        return response()->json(['success' => true, 'message' => 'Module showcase removed.']);
    }

    public function updateFurniture(Request $request, HomepageFurnitureItem $furniture): JsonResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:180'],
            'is_active' => ['required', 'boolean'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'model' => ['nullable', 'file', 'max:102400'],
        ]);

        if ($request->hasFile('model')) {
            $extension = strtolower((string) $request->file('model')->getClientOriginalExtension());
            if (!in_array($extension, ['glb', 'gltf', 'obj', 'ply'], true)) {
                throw ValidationException::withMessages(['model' => ['Use a GLB, GLTF, OBJ, or PLY model.']]);
            }
            $newPath = $request->file('model')->store('marketing/home/models', 'public');
            $this->deleteManagedFile($furniture->model_path);
            $validated['model_path'] = $newPath;
            $validated['model_format'] = $extension;
        }

        if ($request->hasFile('thumbnail')) {
            $newPath = $request->file('thumbnail')->store('marketing/home/furniture', 'public');
            $this->deleteManagedFile($furniture->thumbnail_path);
            $validated['thumbnail_path'] = $newPath;
        }

        unset($validated['model'], $validated['thumbnail']);
        $furniture->update($validated);

        return response()->json(['success' => true, 'message' => 'Furniture showcase updated.', 'data' => $this->furnitureData($furniture->fresh())]);
    }

    private function content(bool $activeOnly): array
    {
        $modules = HomepageModuleSlide::query()->when($activeOnly, fn ($query) => $query->where('is_active', true))->orderBy('sort_order')->get()->map(fn ($module) => $this->moduleData($module));
        $furniture = HomepageFurnitureItem::query()->when($activeOnly, fn ($query) => $query->where('is_active', true))->orderBy('slot')->limit(4)->get()->map(fn ($item) => $this->furnitureData($item));

        return ['modules' => $modules, 'furniture' => $furniture];
    }

    private function moduleData(HomepageModuleSlide $module): array
    {
        return [...$module->toArray(), 'image_url' => $this->fileUrl($module->image_path)];
    }

    private function furnitureData(HomepageFurnitureItem $item): array
    {
        return [...$item->toArray(), 'thumbnail_url' => $this->fileUrl($item->thumbnail_path), 'model_url' => $this->fileUrl($item->model_path)];
    }

    private function fileUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    private function deleteManagedFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'marketing/home/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403, 'Super Admin access only.');
    }
}
