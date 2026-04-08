<?php

namespace App\Http\Controllers\Api\Procurement\SupplierPortal;

use App\Http\Controllers\Controller;
use App\Models\Procurement\SupplierPortal\SupplierDeliveryTemplate;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierDeliveryTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();

        $templates = SupplierDeliveryTemplate::where('supplier_portal_id', $portal->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'truck_brand' => 'nullable|string|max:100',
            'truck_type' => 'nullable|string|max:100',
            'wheel_count' => 'nullable|integer|min:2|max:24',
            'plate_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:150',
            'driver_contact' => 'nullable|string|max:50',
            'cost_per_km' => 'nullable|numeric|min:0',
        ]);

        $template = SupplierDeliveryTemplate::create([
            'supplier_portal_id' => $portal->id,
            'supplier_id' => $portal->supplier_id,
            'created_by' => auth()->id(),
            ...$validated,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery template saved.',
            'data' => $template,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();

        $template = SupplierDeliveryTemplate::where('supplier_portal_id', $portal->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'truck_brand' => 'nullable|string|max:100',
            'truck_type' => 'nullable|string|max:100',
            'wheel_count' => 'nullable|integer|min:2|max:24',
            'plate_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:150',
            'driver_contact' => 'nullable|string|max:50',
            'cost_per_km' => 'nullable|numeric|min:0',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Delivery template updated.',
            'data' => $template->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $portal = SupplierPortal::where('user_id', auth()->id())->firstOrFail();

        $template = SupplierDeliveryTemplate::where('supplier_portal_id', $portal->id)
            ->where('id', $id)
            ->firstOrFail();

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery template deleted.',
        ]);
    }
}
