<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CRM\CrmLead;
use App\Models\Inventory\BranchInventory;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Models\Sales\SalesPayment;
use App\Models\Sales\WholesaleQuote;
use App\Services\Sales\SalesOrderSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WholesaleQuoteController extends Controller
{
    public function __construct(private readonly SalesOrderSettlementService $settlementService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $rows = WholesaleQuote::query()->where('store_id', $storeId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($nested) use ($search) {
                    $nested->where('quote_number', 'like', "%{$search}%")
                        ->orWhereHas('crmLead', fn ($lead) => $lead->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->with('crmLead:id,full_name,email,phone')
            ->latest()->paginate(15);
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function options(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $leads = CrmLead::query()->where('store_id', $storeId)
            ->orderBy('full_name')->get(['id', 'full_name', 'email', 'phone', 'stage']);
        $products = BranchInventory::query()->where('store_id', $storeId)
            ->whereHas('product', fn ($query) => $query->where('product_type', 'finished_good')->where('is_active', true))
            ->with(['product:id,product_name,sku,base_price', 'branch:id,name'])
            ->get(['id', 'store_id', 'branch_id', 'product_id', 'variation_id', 'quantity_available'])
            ->map(fn ($stock) => [
                'id' => $stock->id, 'branch_id' => $stock->branch_id,
                'branch_name' => $stock->branch?->name,
                'product_name' => $stock->product?->product_name,
                'sku' => $stock->product?->sku,
                'available' => (int) $stock->quantity_available,
                'base_price' => (float) ($stock->product?->base_price ?? 0),
            ]);
        return response()->json(['success' => true, 'data' => ['leads' => $leads, 'products' => $products]]);
    }

    public function store(Request $request): JsonResponse
    {
        $storeId = (int) $request->user()->store_id;
        $validated = $request->validate([
            'crm_lead_id' => ['required', 'integer', Rule::exists('sales_crm_leads', 'id')->where('store_id', $storeId)],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')->where('store_id', $storeId)],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],
            'payment_terms' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.branch_inventory_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $items = [];
        foreach ($validated['items'] as $row) {
            $stock = BranchInventory::query()->where('store_id', $storeId)
                ->where('branch_id', $validated['branch_id'])
                ->whereKey($row['branch_inventory_id'])
                ->with('product:id,product_name,sku,product_type,is_active')->first();
            if (!$stock || $stock->product?->product_type !== 'finished_good' || !$stock->product?->is_active) {
                return response()->json(['success' => false, 'message' => 'Select active finished goods from the chosen branch.'], 422);
            }
            $quantity = (int) $row['quantity'];
            $price = round((float) $row['unit_price'], 2);
            $items[] = [
                'branch_inventory_id' => $stock->id, 'product_id' => $stock->product_id,
                'variation_id' => $stock->variation_id,
                'product_name' => $stock->product->product_name, 'sku' => $stock->product->sku,
                'quantity' => $quantity, 'unit_price' => $price,
                'line_total' => round($quantity * $price, 2),
            ];
        }
        $quote = WholesaleQuote::create([
            'store_id' => $storeId, 'branch_id' => $validated['branch_id'],
            'crm_lead_id' => $validated['crm_lead_id'],
            'quote_number' => 'WQ-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
            'status' => 'draft', 'items' => $items,
            'subtotal' => round(array_sum(array_column($items, 'line_total')), 2),
            'valid_until' => $validated['valid_until'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'notes' => $validated['notes'] ?? null, 'created_by' => $request->user()->id,
        ]);
        return response()->json(['success' => true, 'data' => $quote], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)
            ->with('crmLead:id,full_name,email,phone')->findOrFail($id);
        if ($quote->sales_order_id) {
            $quote->setAttribute('order_payment_status', SalesOrder::query()
                ->where('store_id', $quote->store_id)->whereKey($quote->sales_order_id)
                ->value('payment_status'));
        }
        return response()->json(['success' => true, 'data' => $quote]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['sent', 'accepted', 'declined'])]]);
        $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)->findOrFail($id);
        $allowed = ['draft' => ['sent'], 'sent' => ['accepted', 'declined']];
        if (!in_array($validated['status'], $allowed[$quote->status] ?? [], true)) {
            return response()->json(['success' => false, 'message' => 'This quotation cannot move to that status.'], 422);
        }
        $quote->update(['status' => $validated['status']]);
        if ($validated['status'] === 'sent') {
            CrmLead::query()->where('store_id', $quote->store_id)->whereKey($quote->crm_lead_id)->update(['stage' => 'proposal']);
        }
        return response()->json(['success' => true, 'data' => $quote->fresh()]);
    }

    public function convert(Request $request, int $id): JsonResponse
    {
        $result = DB::transaction(function () use ($request, $id) {
            $quote = WholesaleQuote::query()->where('store_id', $request->user()->store_id)
                ->lockForUpdate()->findOrFail($id);
            if ($quote->status !== 'accepted' || $quote->sales_order_id) {
                return ['error' => 'Only an accepted quotation can be converted once.'];
            }
            if ($quote->valid_until && $quote->valid_until->startOfDay()->lt(today())) {
                return ['error' => 'This quotation has expired.'];
            }
            foreach ($quote->items as $item) {
                $stock = BranchInventory::query()->where('store_id', $quote->store_id)
                    ->where('branch_id', $quote->branch_id)->lockForUpdate()->find($item['branch_inventory_id']);
                if (!$stock || (int) $stock->quantity_available < (int) $item['quantity']) {
                    return ['error' => "Not enough stock for {$item['product_name']}."];
                }
            }
            $lead = CrmLead::query()->where('store_id', $quote->store_id)->findOrFail($quote->crm_lead_id);
            $order = SalesOrder::create([
                'store_id' => $quote->store_id, 'branch_id' => $quote->branch_id,
                'order_number' => 'WHO-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4))),
                'status' => 'pending_payment', 'payment_method' => 'cash', 'payment_status' => 'pending',
                'customer_name' => $lead->full_name, 'customer_phone' => $lead->phone,
                'subtotal' => $quote->subtotal, 'total_amount' => $quote->subtotal,
                'notes' => 'Wholesale quote ' . $quote->quote_number . ($quote->payment_terms ? ' | Terms: ' . $quote->payment_terms : '') . ($quote->notes ? ' | ' . $quote->notes : ''),
                'created_by' => $request->user()->id,
            ]);
            foreach ($quote->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->id, 'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'], 'branch_inventory_id' => $item['branch_inventory_id'],
                    'product_name' => $item['product_name'], 'sku' => $item['sku'],
                    'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'],
                    'line_discount' => 0, 'line_tax' => 0, 'line_total' => $item['line_total'],
                ]);
            }
            $quote->update(['status' => 'converted', 'sales_order_id' => $order->id]);
            $lead->update(['stage' => 'won']);
            return ['quote' => $quote->fresh(), 'order' => $order];
        });
        if (isset($result['error'])) {
            return response()->json(['success' => false, 'message' => $result['error']], 422);
        }
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'card'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);
        $storeId = (int) $request->user()->store_id;
        $quote = WholesaleQuote::query()->where('store_id', $storeId)
            ->where('status', 'converted')->findOrFail($id);
        try {
            DB::transaction(function () use ($request, $validated, $quote, $storeId) {
                $order = SalesOrder::query()->where('store_id', $storeId)
                    ->whereKey($quote->sales_order_id)->lockForUpdate()->firstOrFail();
                if ($order->payment_status === 'paid') {
                    throw new \RuntimeException('Payment has already been recorded.');
                }
                $reference = ($validated['payment_reference'] ?? null) ?: 'WHO-PAY-' . strtoupper(bin2hex(random_bytes(5)));
                $payment = SalesPayment::create([
                    'store_id' => $storeId, 'branch_id' => $quote->branch_id,
                    'sales_order_id' => $order->id, 'crm_lead_id' => $quote->crm_lead_id,
                    'payment_provider' => 'manual', 'payment_method' => $validated['payment_method'],
                    'currency' => 'PHP', 'amount' => $order->total_amount,
                    'status' => 'paid', 'provider_reference' => $reference,
                    'paid_at' => now(), 'created_by' => $request->user()->id,
                ]);
                $order->update(['payment_method' => $validated['payment_method']]);
                $this->settlementService->settlePaid($order, $validated['payment_method'], $reference, $payment);
            });
        } catch (\RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
        return response()->json(['success' => true, 'message' => 'Payment recorded and stock updated.']);
    }
}
