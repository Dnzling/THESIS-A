<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceVoucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SalesVoucherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);

        if ($storeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No store assigned.',
            ], 422);
        }

        $query = EcommerceVoucher::query()->where('store_id', $storeId);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where('code', 'like', "%{$search}%");
        }

        $status = (string) $request->input('status', '');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'expired') {
            $query->whereNotNull('ends_at')->where('ends_at', '<', now());
        }

        $rows = $query
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->paginate((int) $request->input('per_page', 20));

        $base = EcommerceVoucher::query()->where('store_id', $storeId);
        $summary = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'expired' => (clone $base)->whereNotNull('ends_at')->where('ends_at', '<', now())->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $rows,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $storeId = (int) ($user->store_id ?? 0);

        if ($storeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No store assigned.',
            ], 422);
        }

        $validated = $request->validate([
            'voucher_name' => ['required', 'string', 'max:120'],
            'voucher_slots' => ['required', 'integer', 'min:0'],
            'code' => ['nullable', 'string', 'max:40'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rawCode = trim((string) ($validated['code'] ?? ''));
        $code = $rawCode !== ''
            ? strtoupper($rawCode)
            : $this->generateVoucherCode((string) $validated['voucher_name'], $storeId);

        $codeExists = EcommerceVoucher::query()
            ->where('store_id', $storeId)
            ->whereRaw('UPPER(code) = ?', [$code])
            ->exists();

        if ($codeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher code already exists for this store.',
            ], 422);
        }

        $voucher = EcommerceVoucher::create([
            'store_id' => $storeId,
            'voucher_name' => trim((string) $validated['voucher_name']),
            'voucher_slots' => (int) ($validated['voucher_slots'] ?? 0),
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : 0,
            'max_discount_amount' => array_key_exists('max_discount_amount', $validated) ? $validated['max_discount_amount'] : null,
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false), // draft by default
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully.',
            'data' => $voucher,
        ], 201);
    }

    public function show(Request $request, EcommerceVoucher $voucher): JsonResponse
    {
        $storeId = (int) ($request->user()->store_id ?? 0);
        if ((int) $voucher->store_id !== $storeId) {
            abort(404);
        }

        $draftQuery = EcommerceVoucher::query()
            ->where('store_id', $storeId)
            ->where('is_active', false);

        $draftStats = [
            'total_drafts' => (clone $draftQuery)->count(),
            'total_draft_slots' => (int) (clone $draftQuery)->sum('voucher_slots'),
            'drafts_started' => (clone $draftQuery)->whereNotNull('starts_at')->where('starts_at', '<=', now())->count(),
            'drafts_with_end_date' => (clone $draftQuery)->whereNotNull('ends_at')->count(),
            'drafts_future_start' => (clone $draftQuery)->whereNotNull('starts_at')->where('starts_at', '>', now())->count(),
        ];

        $drafts = (clone $draftQuery)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $voucher,
            'draft_stats' => $draftStats,
            'drafts' => $drafts,
        ]);
    }

    public function update(Request $request, EcommerceVoucher $voucher): JsonResponse
    {
        $storeId = (int) ($request->user()->store_id ?? 0);
        if ((int) $voucher->store_id !== $storeId) {
            abort(404);
        }

        $validated = $request->validate([
            'voucher_name' => ['required', 'string', 'max:120'],
            'voucher_slots' => ['required', 'integer', 'min:0'],
            'code' => ['nullable', 'string', 'max:40'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rawCode = trim((string) ($validated['code'] ?? ''));
        $code = $rawCode !== ''
            ? strtoupper($rawCode)
            : $this->generateVoucherCode((string) $validated['voucher_name'], $storeId);

        $codeExists = EcommerceVoucher::query()
            ->where('store_id', $storeId)
            ->where('id', '!=', $voucher->id)
            ->whereRaw('UPPER(code) = ?', [$code])
            ->exists();

        if ($codeExists) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher code already exists for this store.',
            ], 422);
        }

        $voucher->update([
            'voucher_name' => trim((string) $validated['voucher_name']),
            'voucher_slots' => (int) ($validated['voucher_slots'] ?? 0),
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_order_amount' => isset($validated['min_order_amount']) ? (float) $validated['min_order_amount'] : 0,
            'max_discount_amount' => array_key_exists('max_discount_amount', $validated) ? $validated['max_discount_amount'] : null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully.',
            'data' => $voucher->fresh(),
        ]);
    }

    private function generateVoucherCode(string $name, int $storeId): string
    {
        $prefix = strtoupper(Str::substr(Str::replace('-', '', Str::slug($name)), 0, 8));
        if ($prefix === '') {
            $prefix = 'VOUCHER';
        }

        for ($i = 0; $i < 10; $i++) {
            $suffix = strtoupper(Str::random(4));
            $candidate = Str::limit("{$prefix}-{$suffix}", 40, '');
            $exists = EcommerceVoucher::query()
                ->where('store_id', $storeId)
                ->whereRaw('UPPER(code) = ?', [$candidate])
                ->exists();
            if (!$exists) {
                return $candidate;
            }
        }

        return Str::limit('VOUCHER-' . strtoupper(Str::random(6)), 40, '');
    }
}
