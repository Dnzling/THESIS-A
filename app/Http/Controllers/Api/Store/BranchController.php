<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Hr\Employee;
use App\Services\Store\CaviteLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get branches for the user's store
            $query = Branch::where('store_id', $user->store_id)
                ->select('id', 'name', 'branch_code', 'province', 'city', 'barangay', 'address', 'status', 'contact_number', 'branch_type', 'is_main_branch', 'latitude', 'longitude', 'geofence_radius_m', 'geofence_enabled')
                ->orderBy('name');

            if ($request->filled('branch_type')) {
                $query->where('branch_type', (string) $request->input('branch_type'));
            }

            $branches = $query->get();
            $store = Store::query()
                ->select('id', 'province', 'city', 'barangay', 'address')
                ->find($user->store_id);
            
            return response()->json([
                'success' => true,
                'data' => $branches,
                'store_location' => $store,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'province' => [Rule::requiredIf($request->input('branch_type') === 'warehouse'), 'nullable', 'string', 'max:50'],
                'barangay' => 'nullable|string|max:150',
                'latitude' => 'nullable|numeric|between:-90, 90',
                'longitude' => 'nullable|numeric|between:-180, 180',
                'branch_code' => 'nullable|string|max:20|unique:branches,branch_code',
                'geofence_radius_m' => 'nullable|integer|min:0|max:5000',
                'geofence_enabled' => 'nullable|boolean',
                'is_main_branch' => 'nullable|boolean',
                'branch_type' => 'nullable|in:storefront,warehouse,wholesale',
            ]);

            $storeId = $user?->store_id;
            $branchCode = $validated['branch_code'] ?? $this->generateBranchCode($validated['name'], $storeId);
            $store = Store::query()->find($storeId);
            $storePhone = $store?->phone;
            $contactNumber = $validated['contact_number'] ?? null;
            if (!$contactNumber) {
                $contactNumber = $storePhone ?: '0000000000';
            }

            $branch = DB::transaction(function () use ($validated, $storeId, $branchCode, $contactNumber, $store) {
                Store::query()->whereKey($storeId)->lockForUpdate()->firstOrFail();
                if (($validated['is_main_branch'] ?? false)
                    && Branch::query()->where('store_id', $storeId)->where('is_main_branch', true)->exists()) {
                    throw ValidationException::withMessages([
                        'is_main_branch' => 'This store already has a main branch. Only one main branch is allowed.',
                    ]);
                }

                return Branch::create(array_merge($validated, [
                    'store_id' => $storeId,
                    'branch_code' => $branchCode,
                    'contact_number' => $contactNumber,
                    'status' => 'active',
                    'province' => $validated['province'] ?? 'Cavite',
                    'barangay' => $validated['barangay'] ?? $store?->barangay,
                    'branch_type' => $validated['branch_type'] ?? 'storefront',
                    'is_main_branch' => $validated['is_main_branch'] ?? false,
                    'geofence_enabled' => $validated['geofence_enabled'] ?? false,
                    'geofence_radius_m' => $validated['geofence_radius_m'] ?? 0,
                ]));
            });

            return response()->json([
                'success' => true,
                'message' => 'Branch is created successfully',
                'data' => [
                    'name' => $branch->name,
                    'branch_code' => $branch->branch_code,
                    'status' => $branch->status,
                ]
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Adding Branch failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function generateBranchCode(string $name, ?int $storeId): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?: 'BR', 0, 6));
        $storeSegment = $storeId ? sprintf('%02d', $storeId % 100) : '00';

        do {
            $suffix = Str::upper(Str::random(3));
            $code = "{$prefix}-{$storeSegment}-{$suffix}";
        } while (Branch::where('branch_code', $code)->exists());

        return $code;
    }

    public function show(Request $request, $id)
    {
        $branch = Branch::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $branch,
        ]);
    }

    public function performance(Request $request, int $branch)
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
        $storeId = (int) $request->user()?->store_id;
        Branch::query()->where('store_id', $storeId)->findOrFail($branch);
        $since = now()->subDays(29)->startOfDay();

        $pos = DB::table('sales_pos_orders')
            ->where('store_id', $storeId)->where('branch_id', $branch)
            ->where('status', 'completed')->where('created_at', '>=', $since)
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue')->first();
        $online = DB::table('ecommerce_orders')
            ->where('store_id', $storeId)->where('assigned_branch_id', $branch)
            ->whereIn('status', ['delivered', 'completed'])
            ->where('payment_status', '!=', 'refunded')
            ->whereRaw('COALESCE(placed_at, created_at) >= ?', [$since])
            ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue')->first();
        $stock = DB::table('branch_inventory')
            ->where('store_id', $storeId)->where('branch_id', $branch)->whereNull('deleted_at')
            ->selectRaw("COUNT(*) as items, COALESCE(SUM(quantity_available), 0) as available_units, SUM(CASE WHEN stock_status = 'low_stock' THEN 1 ELSE 0 END) as low_stock, SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock")
            ->first();

        $period = $request->validate(['period' => 'sometimes|in:week,month,year'])['period'] ?? 'month';
        $trendStart = match ($period) {
            'week' => now()->subDays(6)->startOfDay(),
            'year' => now()->subMonths(11)->startOfMonth(),
            default => now()->subDays(29)->startOfDay(),
        };
        $dateFormat = $period === 'year' ? '%Y-%m' : '%Y-%m-%d';
        $posTrend = DB::table('sales_pos_orders')->where('store_id', $storeId)->where('branch_id', $branch)
            ->where('status', 'completed')->where('created_at', '>=', $trendStart)
            ->selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as bucket, SUM(total_amount) as total")
            ->groupBy('bucket')->pluck('total', 'bucket');
        $onlineTrend = DB::table('ecommerce_orders')->where('store_id', $storeId)->where('assigned_branch_id', $branch)
            ->whereIn('status', ['delivered', 'completed'])->where('payment_status', '!=', 'refunded')
            ->whereRaw('COALESCE(placed_at, created_at) >= ?', [$trendStart])
            ->selectRaw("DATE_FORMAT(COALESCE(placed_at, created_at), '{$dateFormat}') as bucket, SUM(total_amount) as total")
            ->groupBy('bucket')->pluck('total', 'bucket');
        $salesTrend = collect(range(0, $period === 'year' ? 11 : ($period === 'week' ? 6 : 29)))
            ->map(function ($offset) use ($trendStart, $period, $posTrend, $onlineTrend) {
                $date = $period === 'year' ? $trendStart->copy()->addMonths($offset) : $trendStart->copy()->addDays($offset);
                $key = $date->format($period === 'year' ? 'Y-m' : 'Y-m-d');
                return ['date' => $key, 'pos' => (float) ($posTrend[$key] ?? 0), 'online' => (float) ($onlineTrend[$key] ?? 0)];
            })->all();
        $alerts = DB::table('branch_inventory as bi')->join('products as p', 'p.id', '=', 'bi.product_id')
            ->where('bi.store_id', $storeId)->where('bi.branch_id', $branch)->whereNull('bi.deleted_at')
            ->whereIn('bi.stock_status', ['low_stock', 'out_of_stock'])
            ->orderByRaw("CASE WHEN bi.stock_status = 'out_of_stock' THEN 0 ELSE 1 END")
            ->orderBy('bi.quantity_available')->limit(20)
            ->get(['bi.id', 'bi.product_id', 'bi.variation_id', 'bi.stock_status', 'bi.quantity_available', 'bi.reorder_point', 'p.product_name', 'p.sku']);

        return response()->json(['success' => true, 'data' => [
            'period' => 'Last 30 days',
            'pos_orders' => (int) ($pos->orders ?? 0),
            'pos_revenue' => (float) ($pos->revenue ?? 0),
            'online_orders' => (int) ($online->orders ?? 0),
            'online_revenue' => (float) ($online->revenue ?? 0),
            'stock_items' => (int) ($stock->items ?? 0),
            'available_units' => (int) ($stock->available_units ?? 0),
            'low_stock' => (int) ($stock->low_stock ?? 0),
            'out_of_stock' => (int) ($stock->out_of_stock ?? 0),
            'employees' => Employee::query()->where('store_id', $storeId)->where('branch_id', $branch)->count(),
            'sales_trend' => $salesTrend,
            'stock_alerts' => $alerts,
        ]]);
    }

    public function employees(Request $request, int $branch)
    {
        abort_unless($request->user()?->hasRole('owner'), 403);
        $storeId = (int) $request->user()?->store_id;
        Branch::query()->where('store_id', $storeId)->findOrFail($branch);
        $query = Employee::query()->with(['user:id,email', 'role:id,name,display_name'])
            ->where('store_id', $storeId)->where('branch_id', $branch);
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('fname', 'like', "%{$term}%")
                    ->orWhere('lname', 'like', "%{$term}%")
                    ->orWhere('employee_number', 'like', "%{$term}%");
            });
        }
        $employees = $query->orderBy('fname')->orderBy('lname')->paginate(15, [
            'id', 'user_id', 'role_id', 'employee_number', 'fname', 'lname', 'status', 'hire_date',
        ]);
        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function update(Request $request, CaviteLocationService $locationService, $id)
    {
        $branch = Branch::query()
            ->where('store_id', $request->user()?->store_id)
            ->findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'branch_type' => 'sometimes|nullable|in:storefront,warehouse,wholesale',
            'address' => 'nullable|string|max:255',
            'province' => 'sometimes|nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:150',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:0|max:5000',
            'geofence_enabled' => 'nullable|boolean',
        ]);

        if (isset($validated['latitude'], $validated['longitude'])) {
            try {
                if (!$locationService->contains((float) $validated['latitude'], (float) $validated['longitude'])) {
                    throw ValidationException::withMessages([
                        'latitude' => 'Choose a branch location inside Cavite province.',
                    ]);
                }
            } catch (\RuntimeException $exception) {
                abort(503, $exception->getMessage());
            }
        }

        $branch->fill($validated);
        $branch->save();

        return response()->json([
            'success' => true,
            'data' => $branch,
            'message' => 'Branch updated successfully',
        ]);
    }
}
