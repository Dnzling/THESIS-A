<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\Merchandising\Model3dRequest;
use App\Models\ProductCatalog\Product;
use App\Models\ProductCatalog\ProductAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Model3dRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user()->hasRole('super_admin');
        abort_unless($admin || $request->user()->store_id, 403);
        $query = Model3dRequest::with(['product:id,product_name,sku', 'store:id,name', 'asset:id,file_name,model_format']);
        if (!$admin) $query->where('owner_store_id', $request->user()->store_id);
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        return response()->json(['success' => true, 'data' => $query->latest()->paginate(15)]);
    }

    public function show(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeView($request, $modelRequest);
        return response()->json(['success' => true, 'data' => $modelRequest->load(['product:id,product_name,sku', 'store:id,name', 'asset:id,file_name,model_format'])]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()->hasRole('super_admin'), 403);
        $storeId = (int) $request->user()->store_id;
        abort_unless($storeId > 0, 403);
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'materials' => ['nullable', 'string', 'max:2000'],
            'length_cm' => ['required', 'numeric', 'gt:0'],
            'width_cm' => ['required', 'numeric', 'gt:0'],
            'height_cm' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photos' => ['required', 'array', 'min:1', 'max:12'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        abort_unless(Product::whereKey($data['product_id'])->where('store_id', $storeId)->exists(), 422);
        $paths = [];
        try {
            foreach ($request->file('photos') as $photo) $paths[] = $photo->store("3d-requests/{$storeId}/references", 'public');
            $record = Model3dRequest::create([
                'owner_store_id' => $storeId,
                'product_id' => $data['product_id'],
                'requested_by' => $request->user()->id,
                'materials' => $data['materials'] ?? null,
                'length_cm' => $data['length_cm'],
                'width_cm' => $data['width_cm'],
                'height_cm' => $data['height_cm'],
                'notes' => $data['notes'] ?? null,
                'reference_photos' => $paths,
                'status' => 'submitted',
            ]);
        } catch (\Throwable $error) {
            foreach ($paths as $path) Storage::disk('public')->delete($path);
            throw $error;
        }
        return response()->json(['success' => true, 'data' => $record, 'message' => '3D model request submitted.'], 201);
    }

    public function quote(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_unless(in_array($modelRequest->status, ['submitted', 'quoted'], true), 422);
        $data = $request->validate([
            'quoted_price' => ['required', 'numeric', 'min:0'],
            'included_revisions' => ['required', 'integer', 'min:0', 'max:20'],
            'quote_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $modelRequest->update([...$data, 'status' => 'quoted']);
        return response()->json(['success' => true, 'data' => $modelRequest]);
    }

    public function storeAction(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeStore($request, $modelRequest);
        $data = $request->validate(['action' => ['required', Rule::in(['accept_quote', 'request_revision', 'approve'])], 'notes' => ['nullable', 'string', 'max:3000']]);
        $status = match ($data['action']) {
            'accept_quote' => 'quoted', 'request_revision', 'approve' => 'ready_for_review',
        };
        abort_unless($modelRequest->status === $status, 422);
        if ($data['action'] === 'approve') {
            abort_unless($modelRequest->model_path && Storage::disk('public')->exists($modelRequest->model_path), 422);
            DB::transaction(function () use ($modelRequest) {
                $asset = ProductAsset::create([
                    'store_id' => $modelRequest->owner_store_id,
                    'product_id' => $modelRequest->product_id,
                    'asset_type' => '3D_Model',
                    'file_name' => basename($modelRequest->model_path),
                    'file_path' => $modelRequest->model_path,
                    'file_size_kb' => (int) ceil(Storage::disk('public')->size($modelRequest->model_path) / 1024),
                    'mime_type' => 'model/gltf-binary',
                    'model_format' => 'glb',
                    'is_ar_compatible' => true,
                    'is_primary' => !ProductAsset::where('product_id', $modelRequest->product_id)->where('asset_type', '3D_Model')->exists(),
                ]);
                $modelRequest->update(['status' => 'published', 'product_asset_id' => $asset->id]);
            });
        } else {
            $modelRequest->update(['status' => $data['action'] === 'accept_quote' ? 'accepted' : 'changes_requested', 'notes' => ($data['notes'] ?? null) ?: $modelRequest->notes]);
        }
        return response()->json(['success' => true, 'data' => $modelRequest->refresh()]);
    }

    public function adminAction(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['action' => ['required', Rule::in(['start', 'upload_model'])], 'model' => ['required_if:action,upload_model', 'file', 'mimes:glb', 'max:51200']]);
        if ($data['action'] === 'start') {
            abort_unless(in_array($modelRequest->status, ['accepted', 'changes_requested'], true), 422);
            $modelRequest->update(['status' => 'in_production']);
        } else {
            abort_unless(in_array($modelRequest->status, ['in_production', 'changes_requested'], true), 422);
            $path = $request->file('model')->store("3d-requests/{$modelRequest->owner_store_id}/models", 'public');
            $modelRequest->update(['model_path' => $path, 'status' => 'ready_for_review']);
        }
        return response()->json(['success' => true, 'data' => $modelRequest]);
    }

    private function authorizeAdmin(Request $request): void { abort_unless($request->user()->hasRole('super_admin'), 403); }
    private function authorizeStore(Request $request, Model3dRequest $modelRequest): void { abort_unless(!$request->user()->hasRole('super_admin') && (int) $request->user()->store_id === (int) $modelRequest->owner_store_id, 403); }
    private function authorizeView(Request $request, Model3dRequest $modelRequest): void { if (!$request->user()->hasRole('super_admin')) $this->authorizeStore($request, $modelRequest); }
}
