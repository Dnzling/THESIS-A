<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierDirectoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Supplier::query()
            ->with('store:id,name')
            ->withCount([
                'products',
                'purchaseOrders',
                'contracts as active_contracts_count' => fn ($contracts) => $contracts->where('status', 'active'),
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($store) => $store->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 20)));
        $suppliers = $query->orderBy('supplier_name')->paginate($perPage);
        $suppliers->getCollection()->transform(fn (Supplier $supplier) => $this->summary($supplier));

        $totals = Supplier::query()
            ->selectRaw('COUNT(*) as suppliers_count')
            ->selectRaw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count")
            ->selectRaw("SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count")
            ->selectRaw("SUM(CASE WHEN status = 'blacklisted' THEN 1 ELSE 0 END) as blacklisted_count")
            ->selectRaw('COALESCE(SUM(total_orders), 0) as orders_count')
            ->selectRaw('COALESCE(SUM(total_amount_purchased), 0) as purchased_total')
            ->selectRaw('COALESCE(AVG(rating), 0) as average_rating')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => $suppliers,
                'performance' => [
                    'suppliers_count' => (int) $totals->suppliers_count,
                    'active_count' => (int) $totals->active_count,
                    'inactive_count' => (int) $totals->inactive_count,
                    'blacklisted_count' => (int) $totals->blacklisted_count,
                    'orders_count' => (int) $totals->orders_count,
                    'purchased_total' => (float) $totals->purchased_total,
                    'average_rating' => round((float) $totals->average_rating, 2),
                ],
            ],
        ]);
    }

    public function show(int $supplier): JsonResponse
    {
        $record = Supplier::with('store:id,name')
            ->withCount([
                'products',
                'purchaseOrders',
                'contracts as active_contracts_count' => fn ($contracts) => $contracts->where('status', 'active'),
            ])
            ->findOrFail($supplier);

        $record->load([
            'products:id,product_name,sku',
            'contracts' => fn ($contracts) => $contracts->latest()->limit(10),
            'purchaseOrders' => fn ($orders) => $orders->select('id', 'supplier_id', 'po_number', 'status', 'total_amount', 'order_date')->latest()->limit(10),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $this->summary($record),
                'products' => $record->products->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->product_name,
                    'sku' => $product->sku,
                ])->values(),
                'contracts' => $record->contracts,
                'recent_orders' => $record->purchaseOrders,
                'performance' => $this->performanceData($record),
            ],
        ]);
    }

    public function performance(int $supplier): JsonResponse
    {
        $record = Supplier::findOrFail($supplier);

        return response()->json([
            'success' => true,
            'data' => $this->performanceData($record),
        ]);
    }

    private function summary(Supplier $supplier): array
    {
        return [
            'id' => $supplier->id,
            'supplier_name' => $supplier->supplier_name,
            'company_name' => $supplier->company_name,
            'logo_url' => $supplier->logo_url,
            'contact_person' => $supplier->contact_person,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'city' => $supplier->city,
            'province' => $supplier->province,
            'supplier_type' => $supplier->supplier_type,
            'status' => $supplier->status,
            'rating' => (float) $supplier->rating,
            'total_orders' => (int) $supplier->total_orders,
            'total_amount_purchased' => (float) $supplier->total_amount_purchased,
            'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            'products_count' => (int) ($supplier->products_count ?? 0),
            'purchase_orders_count' => (int) ($supplier->purchase_orders_count ?? 0),
            'active_contracts_count' => (int) ($supplier->active_contracts_count ?? 0),
            'store' => $supplier->store ? ['id' => $supplier->store->id, 'name' => $supplier->store->name] : null,
            'created_at' => optional($supplier->created_at)->toDateTimeString(),
        ];
    }

    private function performanceData(Supplier $supplier): array
    {
        $evaluations = $supplier->performanceEvaluations();
        $averages = (clone $evaluations)->selectRaw(
            'COUNT(*) as evaluation_count, AVG(quality_score) as quality, AVG(quantity_accuracy_score) as quantity_accuracy, AVG(delivery_timeliness_score) as delivery_timeliness, AVG(packaging_condition_score) as packaging, AVG(overall_rating) as overall'
        )->first();

        return [
            'rating' => (float) $supplier->rating,
            'total_orders' => (int) $supplier->total_orders,
            'total_amount_purchased' => (float) $supplier->total_amount_purchased,
            'average_order_value' => (float) $supplier->average_order_value,
            'on_time_deliveries' => (int) $supplier->on_time_deliveries,
            'late_deliveries' => (int) $supplier->late_deliveries,
            'on_time_delivery_rate' => $supplier->on_time_delivery_rate,
            'evaluation_count' => (int) ($averages->evaluation_count ?? 0),
            'evaluation_averages' => [
                'quality' => $averages?->quality !== null ? round((float) $averages->quality, 2) : null,
                'quantity_accuracy' => $averages?->quantity_accuracy !== null ? round((float) $averages->quantity_accuracy, 2) : null,
                'delivery_timeliness' => $averages?->delivery_timeliness !== null ? round((float) $averages->delivery_timeliness, 2) : null,
                'packaging' => $averages?->packaging !== null ? round((float) $averages->packaging, 2) : null,
                'overall' => $averages?->overall !== null ? round((float) $averages->overall, 2) : null,
            ],
        ];
    }
}
