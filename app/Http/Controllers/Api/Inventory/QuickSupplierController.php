<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Procurement\Supplier\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class QuickSupplierController extends Controller
{
    public function show(Request $request, int $id, bool $includeProducts = true): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $search = trim((string) $request->query('search', ''));

        $supplier = Supplier::query()
            ->where('store_id', $storeId)
            ->findOrFail($id);

        $products = $includeProducts ? $supplier->products()
            ->with(['category:id,category_name', 'subcategory:id,category_name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($productQuery) use ($search) {
                    $productQuery->where('products.product_name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%")
                        ->orWhere('products.brand', 'like', "%{$search}%");
                });
            })
            ->orderBy('products.product_name')
            ->paginate($request->integer('per_page', 10)) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'supplier' => $supplier,
                'products' => $products,
            ],
        ]);
    }

    public function edit(Request $request, int $id): JsonResponse
    {
        return $this->show($request, $id, false);
    }

    public function store(Request $request, ?int $id = null): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $supplier = $id === null
            ? null
            : Supplier::query()->where('store_id', $storeId)->findOrFail($id);

        $validated = $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->where(fn ($query) => $query
                    ->where('store_id', $storeId)
                    ->whereNull('deleted_at'))
                    ->ignore($supplier?->id),
            ],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'barangay' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'supplier_type' => ['required', Rule::in(['manufacturer', 'wholesaler', 'distributor', 'importer', 'local_artisan'])],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $logoPath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store("stores/{$storeId}/suppliers/logos", 'public');
            }

            $attributes = [
                'supplier_name' => trim($validated['supplier_name']),
                'company_name' => trim($validated['supplier_name']),
                'contact_person' => trim($validated['contact_person']),
                'phone' => trim($validated['phone']),
                'email' => strtolower(trim($validated['email'])),
                'province' => trim($validated['province']),
                'city' => trim($validated['city']),
                'barangay' => trim($validated['barangay']),
                'address' => trim($validated['address']),
                'country' => 'Philippines',
                'supplier_type' => $validated['supplier_type'],
            ];

            $oldLogoPath = $supplier?->logo_path;
            if ($logoPath !== null) {
                $attributes['logo_path'] = $logoPath;
            } elseif ($supplier && $request->boolean('remove_logo') && $supplier->logo_path) {
                $attributes['logo_path'] = null;
            }

            if ($supplier) {
                $supplier->update($attributes);
                if ((($request->boolean('remove_logo') && $logoPath === null) || $logoPath !== null)
                    && $oldLogoPath && $oldLogoPath !== $logoPath) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
            } else {
                $supplier = Supplier::create(array_merge($attributes, [
                    'store_id' => $storeId,
                    'supplier_code' => $this->generateSupplierCode(),
                    'payment_terms' => 'cash_on_delivery',
                    'status' => 'active',
                    'rating' => 5.00,
                ]));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $id === null ? 'Supplier added successfully.' : 'Supplier updated successfully.',
                'data' => $supplier,
            ], $id === null ? 201 : 200);
        } catch (\Throwable $exception) {
            DB::rollBack();

            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            if ($exception instanceof ValidationException) {
                throw $exception;
            }

            report($exception);

            return response()->json([
                'success' => false,
                'message' => $id === null ? 'Failed to add supplier.' : 'Failed to update supplier.',
            ], 500);
        }
    }

    private function generateSupplierCode(): string
    {
        $year = now()->format('Y');
        $lastCode = Supplier::withTrashed()
            ->where('supplier_code', 'like', "SUP-{$year}-%")
            ->orderByDesc('supplier_code')
            ->lockForUpdate()
            ->value('supplier_code');

        $lastNumber = 0;
        if (is_string($lastCode) && preg_match('/^SUP-\d{4}-(\d+)$/', $lastCode, $matches)) {
            $lastNumber = (int) $matches[1];
        }

        return sprintf('SUP-%s-%03d', $year, $lastNumber + 1);
    }
}
