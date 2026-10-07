<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\Merchandising\Model3dRequest;
use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Services\Core\PermissionService;
use App\Models\ProductCatalog\ProductAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Model3dRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user()->hasRole('super_admin');
        abort_unless($admin || $request->user()->store_id, 403);
        $query = Model3dRequest::with(['product:id,product_name,sku', 'store:id,name', 'asset:id,file_name,model_format']);
        if (!$admin) $query->where('owner_store_id', $request->user()->store_id);
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        $response = ['success' => true, 'data' => $query->latest()->paginate(15)];
        if (!$admin) $response['monthly_allowance'] = $this->monthlyAllowance($request, (int) $request->user()->store_id);
        return response()->json($response);
    }

    public function show(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeView($request, $modelRequest);
        return response()->json(['success' => true, 'data' => $modelRequest->load(['product:id,product_name,sku', 'store:id,name', 'asset:id,file_name,model_format'])]);
    }

    public function download(Request $request, Model3dRequest $modelRequest)
    {
        $this->authorizeView($request, $modelRequest);
        abort_unless($modelRequest->status === 'published', 403, 'The store must approve this model before it can be downloaded.');
        abort_unless($modelRequest->model_path && Storage::disk('public')->exists($modelRequest->model_path), 404);
        return Storage::disk('public')->download($modelRequest->model_path, $modelRequest->reference_number . '.glb');
    }

    public function store(Request $request): JsonResponse
    {
        abort_if($request->user()->hasRole('super_admin'), 403);
        $storeId = (int) $request->user()->store_id;
        abort_unless($storeId > 0, 403);
        abort_unless(app(PermissionService::class)->userHasPermission($request->user(), 'merchandising.3d.manage', $storeId), 403, 'Your subscription does not include 3D model requests.');
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', $storeId)->where('product_type', 'finished_good')],
            'materials' => ['nullable', 'string', 'max:2000'],
            'length_cm' => ['required', 'numeric', 'gt:0'],
            'width_cm' => ['required', 'numeric', 'gt:0'],
            'height_cm' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photos' => ['required', 'array', 'min:1', 'max:12'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $paths = [];
        try {
            $record = DB::transaction(function () use ($request, $data, $storeId, &$paths) {
                // Serialize requests for this store so simultaneous submissions cannot exceed its allowance.
                $planId = DB::table('stores')->where('id', $storeId)->lockForUpdate()->value('subscription_tier');
                $limit = DB::table('subscription_plans')->where('id', $planId)->where('is_active', true)->value('max_3d_model_requests_per_month');
                $monthStart = now()->startOfMonth();
                $monthEnd = now()->startOfMonth()->addMonth();
                $used = Model3dRequest::where('owner_store_id', $storeId)->where('created_at', '>=', $monthStart)->where('created_at', '<', $monthEnd)->count();
                if ($limit === null || $used >= (int) $limit) {
                    throw ValidationException::withMessages(['subscription' => 'Your monthly 3D model request allowance has been reached.']);
                }
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
                $record->update(['reference_number' => sprintf('3DR-%s-%06d', $record->created_at->format('Y'), $record->id)]);
                return $record;
            });
        } catch (\Throwable $error) {
            foreach ($paths as $path) Storage::disk('public')->delete($path);
            throw $error;
        }
        $this->notifyAdmins($record, 'submitted', 'New 3D model request', 'A store submitted a 3D model request for review.');
        return response()->json(['success' => true, 'data' => $record, 'message' => '3D model request submitted.'], 201);
    }

    public function storeAction(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeStore($request, $modelRequest);
        $data = $request->validate([
            'action' => ['required', Rule::in(['request_revision', 'approve'])],
            'revision_reason' => ['required_if:action,request_revision', 'nullable', 'string', 'max:3000'],
        ]);
        $status = match ($data['action']) {
            'request_revision', 'approve' => 'ready_for_review',
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
            $modelRequest->update(['status' => 'changes_requested', 'revision_reason' => trim($data['revision_reason'])]);
        }
        if ($data['action'] === 'request_revision') {
            $this->notifyAdmins($modelRequest, 'changes_requested', '3D model revision requested', 'The store requested changes to the submitted 3D model.');
        }
        return response()->json(['success' => true, 'data' => $modelRequest->refresh()]);
    }

    public function adminAction(Request $request, Model3dRequest $modelRequest): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['start', 'upload_model'])],
            'model' => ['required_if:action,upload_model', 'file', 'max:51200', function ($attribute, $file, $fail) {
                if (!$file || strtolower($file->getClientOriginalExtension()) !== 'glb') {
                    $fail('Select a .glb model file.');
                    return;
                }
                $handle = fopen($file->getRealPath(), 'rb');
                $header = $handle ? fread($handle, 20) : false;
                if ($handle) fclose($handle);
                $parts = is_string($header) && strlen($header) === 20
                    ? unpack('Vmagic/Vversion/Vlength/Vjson_length/Vjson_type', $header) : false;
                if (!$parts || $parts['magic'] !== 0x46546C67 || $parts['version'] !== 2
                    || $parts['length'] !== $file->getSize() || $parts['json_type'] !== 0x4E4F534A
                    || $parts['json_length'] < 4 || $parts['json_length'] + 20 > $parts['length']) {
                    $fail('The file is not a valid GLB 2.0 model.');
                }
            }],
        ]);
        if ($data['action'] === 'start') {
            abort_unless(in_array($modelRequest->status, ['submitted', 'changes_requested', 'accepted', 'quoted'], true), 422);
            $modelRequest->update(['status' => 'in_production']);
        } else {
            abort_unless(in_array($modelRequest->status, ['in_production', 'changes_requested'], true), 422);
            $path = $request->file('model')->store("3d-requests/{$modelRequest->owner_store_id}/models", 'public');
            $modelRequest->update(['model_path' => $path, 'status' => 'ready_for_review']);
            $this->notifyStore($modelRequest, 'ready_for_review', '3D model ready for review', 'Your finished 3D model is ready to review.');
        }
        return response()->json(['success' => true, 'data' => $modelRequest]);
    }

    private function authorizeAdmin(Request $request): void { abort_unless($request->user()->hasRole('super_admin'), 403); }

    private function monthlyAllowance(Request $request, int $storeId): array
    {
        $planId = DB::table('stores')->where('id', $storeId)->value('subscription_tier');
        $limit = DB::table('subscription_plans')->where('id', $planId)->where('is_active', true)->value('max_3d_model_requests_per_month');
        $monthStart = now()->startOfMonth();
        $used = Model3dRequest::where('owner_store_id', $storeId)->where('created_at', '>=', $monthStart)
            ->where('created_at', '<', now()->startOfMonth()->addMonth())->count();
        return [
            'limit' => (int) $limit,
            'used' => $used,
            'remaining' => max(0, (int) $limit - $used),
            'can_request' => $limit !== null && $used < (int) $limit
                && app(PermissionService::class)->userHasPermission($request->user(), 'merchandising.3d.manage', $storeId),
        ];
    }
    private function authorizeStore(Request $request, Model3dRequest $modelRequest): void { abort_unless(!$request->user()->hasRole('super_admin') && (int) $request->user()->store_id === (int) $modelRequest->owner_store_id, 403); }
    private function authorizeView(Request $request, Model3dRequest $modelRequest): void { if (!$request->user()->hasRole('super_admin')) $this->authorizeStore($request, $modelRequest); }

    private function notifyAdmins(Model3dRequest $modelRequest, string $action, string $title, string $message): void
    {
        User::query()->where('is_active', true)->whereHas('role', fn ($query) => $query->where('name', 'super_admin'))
            ->pluck('id')->each(fn ($userId) => $this->createNotification($modelRequest, (int) $userId, $action, $title, $message, '/admin/3d-model-requests'));
    }

    private function notifyStore(Model3dRequest $modelRequest, string $action, string $title, string $message): void
    {
        $recipientIds = User::query()->where('store_id', $modelRequest->owner_store_id)->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'owner'))->pluck('id');
        $recipientIds->push($modelRequest->requested_by)->unique()->each(fn ($userId) => $this->createNotification($modelRequest, (int) $userId, $action, $title, $message, '/merchandising/3d-requests'));
    }

    private function createNotification(Model3dRequest $modelRequest, int $userId, string $action, string $title, string $message, string $link): void
    {
        SystemNotification::create([
            'store_id' => $modelRequest->owner_store_id,
            'user_id' => $userId,
            'module' => 'merchandising',
            'entity_type' => 'model_3d_request',
            'entity_id' => $modelRequest->id,
            'action' => $action,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }
}
