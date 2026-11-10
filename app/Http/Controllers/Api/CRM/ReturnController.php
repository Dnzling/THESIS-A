<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\EcommerceOrderReturn;
use App\Models\CRM\ReturnInvestigationTicket;
use App\Models\Hr\Employee;
use App\Models\Logistics\ReturnPickup;
use App\Models\Inventory\BranchInventory;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Finance\FinanceRefund;
use App\Models\Store\Branch;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesOrderItem;
use App\Services\Logistics\ReturnChargeService;
use App\Services\Payment\EcommerceRefundRoutingService;
use App\Services\Finance\ReturnRefundAmountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReturnController extends Controller
{
    public function investigationAssignees(Request $request): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId <= 0) {
            return response()->json(['success' => false, 'message' => 'A store context is required.'], 422);
        }

        $crmViewerIds = $this->userIdsWithAnyPermission($storeId, ['crm.returns.manage']);
        $employees = Employee::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->whereIn('user_id', $crmViewerIds)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with('user:id,fname,lname,email')
            ->orderBy('employee_number')
            ->get(['id', 'user_id', 'employee_number', 'department', 'branch_id']);

        return response()->json([
            'success' => true,
            'data' => $employees->map(fn (Employee $employee) => [
                'value' => (int) $employee->id,
                'user_id' => (int) $employee->user_id,
                'name' => trim(($employee->user?->fname ?? '') . ' ' . ($employee->user?->lname ?? '')) ?: 'Unnamed employee',
                'employee_number' => $employee->employee_number,
                'department' => $employee->department,
                'email' => $employee->user?->email,
            ])->values(),
        ]);
    }

    public function createInvestigationTicket(Request $request, int $id): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        $return = EcommerceOrderReturn::query()->find($id);
        if (!$return) {
            return response()->json(['success' => false, 'message' => 'Return request not found.'], 404);
        }
        if ($storeId <= 0 || (int) $return->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to return request.'], 403);
        }

        $validated = $request->validate([
            'assigned_employee_ids' => ['required', 'array', 'min:1'],
            'assigned_employee_ids.*' => ['required', 'integer', 'distinct'],
            'expected_investigation_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ((string) $return->status !== 'pending_verification') {
            return response()->json([
                'success' => false,
                'message' => 'An investigation ticket can only be created while the return is pending verification.',
            ], 422);
        }
        if ($return->investigationTicket()->exists()) {
            return response()->json(['success' => false, 'message' => 'An investigation ticket already exists for this return.'], 409);
        }

        $crmViewerIds = $this->userIdsWithAnyPermission($storeId, ['crm.returns.manage']);
        $employees = Employee::query()
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->whereIn('user_id', $crmViewerIds)
            ->whereIn('id', $validated['assigned_employee_ids'])
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->with('user:id,fname,lname,email')
            ->get(['id', 'user_id', 'employee_number', 'department', 'branch_id']);

        if ($employees->count() !== count($validated['assigned_employee_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected employees are inactive, outside this store, or do not have crm.returns.manage permission.',
            ], 422);
        }

        $ticket = DB::transaction(function () use ($return, $storeId, $validated, $employees): ReturnInvestigationTicket {
            $lockedReturn = EcommerceOrderReturn::query()->whereKey($return->id)->lockForUpdate()->firstOrFail();
            if ((string) $lockedReturn->status !== 'pending_verification') {
                abort(422, 'An investigation ticket can only be created while the return is pending verification.');
            }
            if ($lockedReturn->investigationTicket()->exists()) {
                abort(409, 'An investigation ticket already exists for this return.');
            }

            $ticket = ReturnInvestigationTicket::query()->create([
                'return_id' => (int) $lockedReturn->id,
                'store_id' => $storeId,
                'created_by' => auth()->id(),
                'expected_investigation_date' => $validated['expected_investigation_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'open',
            ]);

            $ticket->assignees()->attach($employees->mapWithKeys(fn (Employee $employee) => [
                (int) $employee->id => ['user_id' => (int) $employee->user_id],
            ])->all());

            return $ticket;
        });

        $ticket->load(['assignees.user:id,fname,lname,email', 'creator:id,fname,lname']);
        $return->load(['order:id,order_number', 'salesOrder:id,order_number']);
        $sourceOrder = $return->sourceOrder();
        $returnNumber = $return->return_number ?: 'Return #' . $return->id;
        $link = '/crm/returns/' . (int) $return->id;
        $notificationData = [
            'ticket_id' => (int) $ticket->id,
            'return_id' => (int) $return->id,
            'return_number' => $return->return_number,
            'expected_investigation_date' => $ticket->expected_investigation_date?->format('Y-m-d'),
        ];
        $assigneeUserIds = $employees->pluck('user_id')->map(fn ($userId) => (int) $userId)->unique()->values()->all();
        $permissionUserIds = collect($this->userIdsWithAnyPermission($storeId, ['crm.returns.manage']))
            ->diff($assigneeUserIds)
            ->values()
            ->all();
        $permissionUserIds = DB::table('users')
            ->whereIn('id', $permissionUserIds)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($userId) => (int) $userId)
            ->all();

        $this->notifyMany($assigneeUserIds, [
            'store_id' => $storeId,
            'module' => 'crm',
            'entity_type' => 'crm_return_investigation',
            'entity_id' => (int) $ticket->id,
            'action' => 'investigation_assigned',
            'title' => 'Return investigation assigned',
            'message' => "You have been assigned to investigate {$returnNumber}. Expected date: " . $ticket->expected_investigation_date?->format('M j, Y') . '.',
            'data' => $notificationData,
            'link' => $link,
            'severity' => 'info',
        ]);

        $this->notifyMany($permissionUserIds, [
            'store_id' => $storeId,
            'module' => 'crm',
            'entity_type' => 'crm_return_investigation',
            'entity_id' => (int) $ticket->id,
            'action' => 'investigation_created',
            'title' => 'Return investigation ticket created',
            'message' => "An investigation ticket was opened for {$returnNumber}. Expected date: " . $ticket->expected_investigation_date?->format('M j, Y') . '.',
            'data' => $notificationData,
            'link' => $link,
            'severity' => 'info',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Return investigation ticket created and the CRM team was notified.',
            'data' => [
                'ticket' => $ticket,
                'return' => $return->fresh()->load([
                    'investigationTicket.assignees.user:id,fname,lname,email',
                    'investigationTicket.creator:id,fname,lname',
                ]),
            ],
        ], 201);
    }

    public function completeInvestigationTicket(Request $request, int $id): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        $return = EcommerceOrderReturn::query()
            ->with('investigationTicket.assignees')
            ->find($id);

        if (!$return) {
            return response()->json(['success' => false, 'message' => 'Return request not found.'], 404);
        }
        if ($storeId <= 0 || (int) $return->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to return request.'], 403);
        }

        $ticket = $return->investigationTicket;
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Create an investigation ticket first.'], 422);
        }
        if ((string) $return->status !== 'pending_verification') {
            return response()->json(['success' => false, 'message' => 'Only pending returns can be investigated.'], 422);
        }
        if ((string) $ticket->status === 'completed') {
            return response()->json(['success' => false, 'message' => 'This investigation has already been completed.'], 422);
        }
        if (!$ticket->assignees->contains(fn (Employee $employee) => (int) $employee->user_id === (int) auth()->id())) {
            return response()->json(['success' => false, 'message' => 'Only an assigned investigator can complete this ticket.'], 403);
        }

        $validated = $request->validate([
            'findings' => ['required', 'string', 'max:5000'],
            'recommended_resolution' => ['required', 'in:refund,replacement,reject'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $ticketUpdates = [
            'findings' => $validated['findings'],
            'recommended_resolution' => $validated['recommended_resolution'],
            'completed_by' => auth()->id(),
            'completed_at' => now(),
            'status' => 'completed',
        ];
        if ($request->hasFile('attachment')) {
            $ticketUpdates['findings_attachment_path'] = $request->file('attachment')
                ->store("crm/return-investigations/{$ticket->id}", 'public');
        }
        $ticket->update($ticketUpdates);

        $returnNumber = $return->return_number ?: 'Return #' . $return->id;
        $crmViewerIds = $this->userIdsWithAnyPermission($storeId, ['crm.returns.view']);
        $this->notifyMany($crmViewerIds, [
            'store_id' => $storeId,
            'module' => 'crm',
            'entity_type' => 'crm_return_investigation',
            'entity_id' => (int) $ticket->id,
            'action' => 'investigation_findings_submitted',
            'title' => 'Return investigation findings submitted',
            'message' => "Findings for {$returnNumber} were submitted and are ready for review.",
            'link' => '/crm/returns/' . (int) $return->id,
            'severity' => 'info',
            'data' => [
                'required_permission' => 'crm.returns.view',
                'return_id' => (int) $return->id,
                'return_number' => $returnNumber,
                'recommended_resolution' => $ticket->recommended_resolution,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investigation findings submitted. The return is ready for manager review.',
            'data' => $return->fresh()->load([
                'investigationTicket.assignees.user:id,fname,lname,email',
                'investigationTicket.creator:id,fname,lname',
                'investigationTicket.completer:id,fname,lname',
            ]),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);

        $query = EcommerceOrderReturn::query()
            ->with([
                'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_address,created_at',
                'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
                'orderItem.product:id,product_name,sku',
                'salesOrder:id,store_id,branch_id,order_number,status,customer_name,customer_phone,delivery_email,delivery_address,created_at,paid_at',
                'salesOrderItem:id,order_id,product_id,variation_id,branch_inventory_id,product_name,sku,quantity,unit_price',
                'salesOrderItem.product:id,product_name,sku',
                'salesOrderItem.variation:id,variation_name,variation_sku',
                'salesOrderItem.branchInventory:id,branch_id,variation_id',
                'salesOrderItem.branchInventory.variation:id,variation_name,variation_sku,color,size,material,texture,finish,base_price',
                'user:id,fname,lname,email',
                'pickup:id,store_id,return_id,status,scheduled_at,driver_user_id,picked_up_at,created_at',
                'pickup.driver:id,fname,lname,email',
                'investigationTicket.assignees:id,user_id,employee_number,department,branch_id',
                'investigationTicket.assignees.user:id,fname,lname,email',
            ])
            ->when($storeId > 0, fn ($q) => $q->where('store_id', $storeId));

        $workflow = $request->string('workflow')->toString();
        if ($workflow === 'assigned_to_me') {
            $query->whereHas('investigationTicket', function ($ticketQuery) use ($request) {
                $ticketQuery
                    ->whereIn('status', ['open', 'in_progress'])
                    ->whereHas('assignees', fn ($assigneeQuery) => $assigneeQuery->where('employees.user_id', (int) $request->user()->id));
            });
        } elseif ($workflow === 'needs_investigation') {
            $query->where('status', 'pending_verification')
                ->where(function ($stageQuery) {
                    $stageQuery->whereDoesntHave('investigationTicket')
                        ->orWhereHas('investigationTicket', fn ($ticketQuery) => $ticketQuery->whereIn('status', ['open', 'in_progress']));
                });
        } elseif ($workflow === 'awaiting_manager_decision') {
            $query->where('status', 'pending_verification')
                ->whereHas('investigationTicket', fn ($ticketQuery) => $ticketQuery->where('status', 'completed'));
        } elseif ($workflow === 'approved') {
            $query->where('status', 'approved')
                ->whereDoesntHave('pickup', fn ($pickupQuery) => $pickupQuery->whereIn('status', ['picked_up', 'out_for_delivery', 'completed', 'delivered']));
        } elseif ($workflow === 'awaiting_inspection') {
            $query->where('status', 'approved')
                ->whereHas('pickup', fn ($pickupQuery) => $pickupQuery->whereIn('status', ['picked_up', 'out_for_delivery', 'completed', 'delivered']));
        } elseif ($workflow === 'awaiting_refund') {
            $query->where('status', 'refund_pending');
        } elseif ($workflow === 'completed') {
            $query->whereIn('status', ['refunded', 'replaced']);
        }

        $investigationStatus = $request->string('investigation_status')->toString();
        if ($investigationStatus === 'unassigned') {
            $query->whereDoesntHave('investigationTicket');
        } elseif (in_array($investigationStatus, ['open', 'in_progress', 'completed', 'cancelled'], true)) {
            $query->whereHas('investigationTicket', fn ($ticketQuery) => $ticketQuery->where('status', $investigationStatus));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->string('start_date')->toString() . ' 00:00:00',
                $request->string('end_date')->toString() . ' 23:59:59',
            ]);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->whereRaw("CONCAT_WS(' ', fname, lname) like ?", ["%{$search}%"])
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('orderItem.product', function ($pq) use ($search) {
                        $pq->where('product_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('orderItem', function ($iq) use ($search) {
                        $iq->where('product_name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('salesOrder', fn ($sq) => $sq->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%"))
                    ->orWhereHas('salesOrderItem', fn ($iq) => $iq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('salesOrderItem.product', fn ($pq) => $pq->where('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortOrder = strtolower($request->string('sort_order', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['created_at', 'status', 'requested_quantity', 'id'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) $request->input('per_page', 15);
        $returns = $query->paginate(max(1, min(100, $perPage)));
        $returns->getCollection()->each(fn (EcommerceOrderReturn $return) => $this->normalizePosReturnForDisplay($return));

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'total' => $returns->total(),
                'per_page' => $returns->perPage(),
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
            ],
        ]);
    }

    public function posOrders(Request $request): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0, 422, 'A store context is required.');

        $activeReturnStatuses = ['pending_verification', 'approved', 'received', 'refund_pending', 'refunded', 'replaced'];
        $returnedItemIds = EcommerceOrderReturn::query()
            ->where('store_id', $storeId)
            ->whereNotNull('sales_order_item_id')
            ->whereIn('status', $activeReturnStatuses)
            ->pluck('sales_order_item_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $orders = SalesOrder::query()
            ->with([
                'branch:id,name',
                'delivery:id,sales_order_id,delivered_at',
                'items.product:id,product_name,sku',
                'items.variation:id,variation_name,variation_sku',
                'items.branchInventory:id,branch_id,variation_id',
                'items.branchInventory.variation:id,variation_name,variation_sku',
            ])
            ->where('store_id', $storeId)
            ->where(function ($query): void {
                $query->where('status', 'completed')
                    ->orWhereHas('delivery', fn ($delivery) => $delivery->where('status', 'delivered'));
            })
            ->where(function ($query): void {
                $query->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
                    ->orWhereHas('payment', fn ($payment) => $payment->whereIn('status', ['paid', 'succeeded', 'completed']));
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($nested) use ($search): void {
                    $nested->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->filter(function (SalesOrder $order): bool {
                $purchasedAt = $order->delivery?->delivered_at ?? $order->paid_at ?? $order->created_at;
                return $purchasedAt && Carbon::parse($purchasedAt)->gte(now()->subDays(7));
            })
            ->map(function (SalesOrder $order) use ($returnedItemIds): array {
                $items = $order->items
                    ->reject(fn (SalesOrderItem $item) => in_array((int) $item->id, $returnedItemIds, true))
                    ->map(fn (SalesOrderItem $item): array => [
                        'id' => (int) $item->id,
                        'product_id' => (int) $item->product_id,
                        'product_name' => $item->product?->product_name ?: $item->product_name,
                        'sku' => $item->variation?->variation_sku ?: $item->product?->sku ?: $item->sku,
                        'variant_name' => $item->variation?->variation_name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                    ])
                    ->values();

                return [
                    'id' => (int) $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'branch_id' => $order->branch_id,
                    'branch_name' => $order->branch?->name,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_email' => $order->delivery_email,
                    'delivery_address' => $order->delivery_address,
                    'purchased_at' => ($order->delivery?->delivered_at ?? $order->paid_at ?? $order->created_at)?->toISOString(),
                    'items' => $items,
                ];
            })
            ->filter(fn (array $order) => count($order['items']) > 0)
            ->values();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function createPosReturn(Request $request): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        abort_unless($storeId > 0, 422, 'A store context is required.');

        $validated = $request->validate([
            'sales_order_item_id' => ['required', 'integer', 'exists:sales_pos_order_items,id'],
            'return_contact_name' => ['required', 'string', 'max:255'],
            'return_contact_phone' => ['required', 'string', 'max:50'],
            'return_pickup_address' => ['required', 'string', 'max:2000'],
            'requested_quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:1000'],
            'details' => ['nullable', 'string', 'max:2000'],
            'evidence_images' => ['required', 'array', 'min:1', 'max:5'],
            'evidence_images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $item = SalesOrderItem::query()
            ->with(['order.delivery', 'product', 'variation', 'branchInventory'])
            ->whereKey($validated['sales_order_item_id'])
            ->whereHas('order', fn ($query) => $query->where('store_id', $storeId)
                ->where(fn ($eligible) => $eligible->where('status', 'completed')
                    ->orWhereHas('delivery', fn ($delivery) => $delivery->where('store_id', $storeId)->where('status', 'delivered')))
                ->where(fn ($paid) => $paid->whereIn('payment_status', ['paid', 'succeeded', 'completed'])
                    ->orWhereHas('payment', fn ($payment) => $payment->whereIn('status', ['paid', 'succeeded', 'completed']))))
            ->firstOrFail();
        $order = $item->order;
        $purchaseDate = $order->delivery?->delivered_at ?? $order->paid_at ?? $order->created_at;
        if (!$purchaseDate || Carbon::parse($purchaseDate)->lt(now()->subDays(7))) {
            return response()->json(['success' => false, 'message' => 'The 7-day return window for this POS sale has ended.'], 422);
        }
        if ((int) $validated['requested_quantity'] > (int) $item->quantity) {
            return response()->json(['success' => false, 'message' => 'Return quantity cannot exceed the quantity sold.'], 422);
        }

        $hasActiveReturn = EcommerceOrderReturn::query()
            ->where('store_id', $storeId)
            ->where('sales_order_item_id', $item->id)
            ->whereIn('status', ['pending_verification', 'approved', 'received', 'refund_pending', 'refunded', 'replaced'])
            ->exists();
        if ($hasActiveReturn) {
            return response()->json(['success' => false, 'message' => 'A return request already exists for this POS sale item.'], 422);
        }

        $branchId = (int) ($item->branchInventory?->branch_id ?? $order->branch_id ?? 0);
        if ($branchId <= 0 || !Branch::query()->where('id', $branchId)->where('store_id', $storeId)->exists()) {
            return response()->json(['success' => false, 'message' => 'The original POS sale branch could not be determined.'], 422);
        }

        $evidenceUrls = [];
        $storedPaths = [];
        try {
            foreach ($request->file('evidence_images', []) as $file) {
                $path = $file->store("crm/returns/pos/{$storeId}/orders/{$order->id}", 'public');
                $storedPaths[] = $path;
                $evidenceUrls[] = Storage::disk('public')->url($path);
            }

            $return = DB::transaction(fn () => EcommerceOrderReturn::query()->create([
                'source_type' => 'pos',
                'sales_order_id' => (int) $order->id,
                'sales_order_item_id' => (int) $item->id,
                'return_contact_name' => trim($validated['return_contact_name']),
                'return_contact_phone' => trim($validated['return_contact_phone']),
                'return_pickup_address' => trim($validated['return_pickup_address']),
                'order_id' => null,
                'order_item_id' => null,
                'store_id' => $storeId,
                'user_id' => null,
                'requested_quantity' => (int) $validated['requested_quantity'],
                'reason' => trim($validated['reason']),
                'details' => $validated['details'] ?? null,
                'evidence_urls' => $evidenceUrls,
                'status' => 'pending_verification',
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        $recipientIds = $this->userIdsWithAnyPermission($storeId, ['crm.returns.manage']);
        $this->notifyMany($recipientIds, [
            'store_id' => $storeId,
            'module' => 'crm',
            'entity_type' => 'ecommerce_order_return',
            'entity_id' => (int) $return->id,
            'action' => 'requested',
            'title' => 'POS return request created',
            'message' => "POS sale {$order->order_number} has a return request pending investigation.",
            'data' => ['required_permission' => 'crm.returns.view', 'source_type' => 'pos'],
            'link' => '/crm/returns/' . (int) $return->id,
            'severity' => 'warn',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'POS return request created and sent for investigation.',
            'data' => $return->load(['salesOrder', 'salesOrderItem.product']),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $return = EcommerceOrderReturn::query()->find($id);
        if (!$return) {
            return response()->json([
                'success' => false,
                'message' => 'Return request not found.',
            ], 404);
        }

        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $return->load([
            'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_email,shipping_address,created_at',
            'orderItem:id,order_id,product_id,branch_inventory_id,product_name,sku,quantity,unit_price',
            'orderItem.product:id,product_name,sku,base_price,unit_of_measurement',
            'orderItem.branchInventory:id,variation_id',
            'orderItem.branchInventory.variation:id,variation_name,variation_sku,color,size,material,texture,finish,base_price',
            'user:id,fname,lname,email',
            'reviewer:id,fname,lname,email',
            'inspector:id,fname,lname,email',
            'pickup:id,store_id,return_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,destination_branch_id,picked_up_at,delivered_at,created_at',
            'pickup.driver:id,fname,lname,email',
            'pickup.destinationBranch:id,name,address,city,province,contact_number',
            'investigationTicket.assignees:id,user_id,employee_number,department,branch_id',
            'investigationTicket.assignees.user:id,fname,lname,email',
            'investigationTicket.creator:id,fname,lname',
            'investigationTicket.completer:id,fname,lname',
            'salesOrder:id,store_id,branch_id,order_number,status,customer_name,customer_phone,delivery_email,delivery_address,created_at,paid_at',
            'salesOrderItem:id,order_id,product_id,variation_id,branch_inventory_id,product_name,sku,quantity,unit_price',
            'salesOrderItem.product:id,product_name,sku,base_price,unit_of_measurement',
            'salesOrderItem.variation:id,variation_name,variation_sku,color,size,material,texture,finish,base_price',
            'salesOrderItem.branchInventory:id,branch_id,variation_id',
            'salesOrderItem.branchInventory.variation:id,variation_name,variation_sku,color,size,material,texture,finish,base_price',
        ]);

        $this->normalizePosReturnForDisplay($return);

        return response()->json([
            'success' => true,
            'data' => $return,
        ]);
    }

    public function updateStatus(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'return_type' => ['nullable', 'required_if:status,approved', 'in:refund,replacement'],
            'charge_responsibility' => ['nullable', 'required_if:status,approved', 'in:store,customer'],
            'refund_exception_reason' => ['nullable', 'required_if:return_type,refund', 'string', 'max:255'],
            'pickup_scheduled_at' => ['required_if:status,approved', 'nullable', 'date', 'after_or_equal:today'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = $validated['status'];
        $currentStatus = (string) $return->status;
        $originBranchId = null;

        if ($newStatus === 'approved') {
            $originBranchId = $this->resolveReturnSourceBranchId($return);
            if (!$originBranchId) {
                return response()->json([
                    'success' => false,
                    'message' => 'The original fulfillment branch could not be determined. Check the order item stock record before approving this return.',
                ], 422);
            }
        }

        if ($currentStatus === 'pending_verification') {
            $return->loadMissing('investigationTicket');
            if (!$return->investigationTicket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Create and complete an investigation ticket before deciding this return.',
                ], 422);
            }
            if ((string) $return->investigationTicket->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'The assigned investigator must submit findings before this return can be approved or rejected.',
                ], 422);
            }
        }

        if ($newStatus === 'approved'
            && $currentStatus === 'approved'
            && $return->return_type
            && $return->return_type !== $validated['return_type']
            && in_array((string) $return->pickup?->status, ['picked_up', 'out_for_delivery', 'completed', 'delivered'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Return type can no longer be changed after the item has been picked up.',
            ], 422);
        }

        if ($newStatus === 'approved'
            && $currentStatus === 'approved'
            && $return->return_type === 'replacement'
            && $return->return_type !== $validated['return_type']
            && $return->replacement_inventory_id) {
            return response()->json([
                'success' => false,
                'message' => 'A replacement unit is already reserved. Release that reservation before changing the resolution.',
            ], 422);
        }

        if ($newStatus === 'rejected' && empty(trim((string) ($validated['review_notes'] ?? '')))) {
            return response()->json([
                'success' => false,
                'message' => 'Review notes are required when rejecting a return.',
            ], 422);
        }

        if ($newStatus === 'rejected'
            && $currentStatus === 'approved'
            && in_array((string) $return->pickup?->status, ['picked_up', 'out_for_delivery', 'completed', 'delivered'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'An approved return can no longer be rejected after the item has been picked up.',
            ], 422);
        }

        $allowedTransitions = [
            'pending_verification' => ['approved', 'rejected'],
            'approved' => ['rejected'],
            'rejected' => [],
            'received' => [],
            'refunded' => [],
        ];

        $currentAllowed = $allowedTransitions[$currentStatus] ?? [];
        if ($newStatus !== $currentStatus && !in_array($newStatus, $currentAllowed, true)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status transition from '{$currentStatus}' to '{$newStatus}'.",
            ], 422);
        }

        $pickupId = null;
        $pickupScheduledAt = $newStatus === 'approved' ? Carbon::parse($validated['pickup_scheduled_at'])->startOfDay() : null;

        DB::transaction(function () use ($return, $newStatus, $validated, $originBranchId, $pickupScheduledAt, &$pickupId): void {
            $return->status = $newStatus;
            if (array_key_exists('review_notes', $validated)) {
                $return->review_notes = $validated['review_notes'];
            }
            if ($newStatus === 'approved') {
                $return->return_type = $validated['return_type'];
                $return->replacement_status = $validated['return_type'] === 'replacement' ? 'awaiting_stock' : null;
                $return->charge_responsibility = $validated['charge_responsibility'];
                $return->refund_exception_reason = $validated['return_type'] === 'refund'
                    ? trim((string) $validated['refund_exception_reason'])
                    : null;
                $charge = app(ReturnChargeService::class)->calculate($return, $validated['charge_responsibility']);
                $return->return_fee = $charge['fee'];
                $return->return_fee_breakdown = $charge['breakdown'];
            }

            $return->reviewed_by = auth()->id();
            $return->reviewed_at = Carbon::now();
            $return->save();

            // Approved returns enter Logistics as ready for dispatch.
            if ($newStatus === 'approved') {
                $return->loadMissing(['order', 'orderItem', 'salesOrder', 'salesOrderItem']);
                $sourceOrder = $return->sourceOrder();
                $sourceItem = $return->sourceOrderItem();
                $pickup = ReturnPickup::query()->firstOrCreate(
                    ['return_id' => (int) $return->id],
                    [
                        'store_id' => (int) $return->store_id,
                        'destination_branch_id' => $originBranchId,
                        'status' => 'ready_for_dispatch',
                        'scheduled_at' => $pickupScheduledAt,
                        'pickup_name' => $return->return_contact_name ?: ($sourceOrder?->shipping_name ?? $sourceOrder?->customer_name),
                        'pickup_phone' => $return->return_contact_phone ?: ($sourceOrder?->shipping_phone ?? $sourceOrder?->customer_phone),
                        'pickup_address' => $return->return_pickup_address ?: ($sourceOrder?->shipping_address ?? $sourceOrder?->delivery_address),
                        'pickup_latitude' => $sourceOrder?->customer_latitude ?? $sourceOrder?->delivery_latitude,
                        'pickup_longitude' => $sourceOrder?->customer_longitude ?? $sourceOrder?->delivery_longitude,
                        'notes' => 'Created from approved return.',
                        'distance_km' => data_get($return->return_fee_breakdown, 'distance_km'),
                        'estimated_fee' => (float) $return->return_fee,
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );
                $pickup->scheduled_at = $pickupScheduledAt;
                $pickup->updated_by = auth()->id();
                $pickup->save();
                $pickupId = (int) $pickup->id;
                if ((int) $pickup->destination_branch_id !== (int) $originBranchId) {
                    $pickup->destination_branch_id = $originBranchId;
                    $pickup->save();
                }

                if ($return->return_type === 'refund') {
                    $refundRouting = app(EcommerceRefundRoutingService::class);
                    $refundMethod = $return->source_type !== 'pos' && $sourceOrder && $refundRouting->canRefundToOriginal($sourceOrder)
                        ? $refundRouting->originalMethod($sourceOrder)
                        : null;
                    FinanceRefund::query()->updateOrCreate(
                        ['store_id' => (int) $return->store_id, 'order_type' => 'ecommerce_return', 'order_id' => (int) $return->id],
                        [
                            'branch_id' => $originBranchId,
                            'order_number' => $sourceOrder?->order_number,
                            'customer_name' => $return->return_contact_name ?: ($sourceOrder?->shipping_name ?? $sourceOrder?->customer_name),
                            'reason' => 'Approved customer return ' . ($return->return_number ?: ('#' . $return->id)),
                            'amount' => app(ReturnRefundAmountService::class)->calculate($return, (int) ($return->requested_quantity ?: 1))['amount'],
                            'status' => 'pending_inspection',
                            'requested_by' => auth()->id(),
                            'refund_method' => $refundMethod,
                            'notes' => $refundMethod
                                ? 'Approved by CRM. Refund to the original payment method after inspection.'
                                : 'Approved by CRM. Awaiting return delivery, inspection, and customer payout details.',
                        ]
                    );
                }

            } elseif ($newStatus === 'rejected') {
                FinanceRefund::query()
                    ->where('store_id', (int) $return->store_id)
                    ->where('order_type', 'ecommerce_return')
                    ->where('order_id', (int) $return->id)
                    ->where('status', 'pending_inspection')
                    ->update([
                        'status' => 'rejected',
                        'notes' => $validated['review_notes'] ?? 'Return rejected by Sales.',
                        'processed_by' => auth()->id(),
                        'processed_at' => now(),
                    ]);
            }
        });

        if ($newStatus === 'approved' && $currentStatus !== 'approved' && $pickupId) {
            if ($return->return_type === 'replacement') {
                try {
                    $this->notifyUsersByPermissions(
                        (int) $return->store_id,
                        ['inventory.stock_returns.view'],
                        [
                            'store_id' => (int) $return->store_id,
                            'module' => 'inventory',
                            'entity_type' => 'ecommerce_order_return',
                            'entity_id' => (int) $return->id,
                            'action' => 'replacement_stock_reservation_needed',
                            'title' => 'Replacement approved: reserve stock',
                            'message' => 'CRM approved a replacement. Reserve the matching item at the branch that fulfilled the original order.',
                            'severity' => 'warn',
                            'link' => '/inventory/stock-returns/replacements/' . (int) $return->id,
                            'data' => [
                                'required_permission' => 'inventory.stock_returns.view',
                                'return_id' => (int) $return->id,
                                'return_number' => $return->return_number,
                                'replacement_status' => 'awaiting_stock',
                            ],
                        ],
                        [(int) $request->user()->id]
                    );
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            try {
                $return->loadMissing(['order:id,order_number', 'salesOrder:id,order_number']);
                $orderNumber = $return->sourceOrder()?->order_number ?: ('Return #' . (int) $return->id);
                $this->notifyUsersByPermissions(
                    (int) $return->store_id,
                    ['logistics.return-pickups.view'],
                    [
                        'store_id' => (int) $return->store_id,
                        'module' => 'logistics',
                        'entity_type' => 'return_pickup',
                        'entity_id' => $pickupId,
                        'action' => 'return_pickup_created',
                        'title' => 'Return Pickup Ready for Dispatch',
                        'message' => "Return for order {$orderNumber} is scheduled for pickup on " . $pickupScheduledAt?->format('M d, Y') . '.',
                        'severity' => 'warn',
                        'link' => '/logistics/return-pickups/' . $pickupId,
                        'data' => [
                            'required_permission' => 'logistics.return-pickups.view',
                            'return_id' => (int) $return->id,
                            'return_number' => $return->return_number,
                            'order_number' => $orderNumber,
                        ],
                    ],
                    [(int) $request->user()->id]
                );
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($return->user_id && $return->source_type !== 'pos' && $currentStatus !== $newStatus) {
            $this->notify((int) $return->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $return->id,
                'action' => 'return_' . $newStatus,
                'title' => $newStatus === 'approved' ? 'Return request approved' : 'Return request rejected',
                'message' => $newStatus === 'approved'
                    ? 'Your ' . $return->return_type . ' return was approved. Pickup is scheduled for ' . $pickupScheduledAt?->format('M d, Y') . '.'
                    : 'Your return request was rejected. Please review the store notes.',
                'severity' => $newStatus === 'approved' ? 'success' : 'warn',
                'store_id' => (int) $return->store_id,
                'link' => '/orders/' . (int) $return->order_id,
                'data' => ['status' => $newStatus],
            ]);
        }

        $return->load([
            'order:id,order_number,status,store_id,user_id,shipping_name,shipping_phone,shipping_address,created_at',
            'orderItem:id,order_id,product_id,product_name,sku,quantity,unit_price',
            'orderItem.product:id,product_name,sku',
            'user:id,fname,lname,email',
            'reviewer:id,fname,lname,email',
            'inspector:id,fname,lname,email',
            'pickup:id,store_id,return_id,destination_branch_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,picked_up_at,created_at',
            'pickup.destinationBranch:id,name,branch_type,address,city,province,latitude,longitude',
            'pickup.driver:id,fname,lname,email',
            'investigationTicket.assignees:id,user_id,employee_number,department,branch_id',
            'investigationTicket.assignees.user:id,fname,lname,email',
            'investigationTicket.creator:id,fname,lname',
            'investigationTicket.completer:id,fname,lname',
            'salesOrder:id,store_id,branch_id,order_number,status,customer_name,customer_phone,delivery_email,delivery_address,created_at,paid_at',
            'salesOrderItem:id,order_id,product_id,variation_id,branch_inventory_id,product_name,sku,quantity,unit_price',
            'salesOrderItem.product:id,product_name,sku',
            'salesOrderItem.variation:id,variation_name,variation_sku',
            'salesOrderItem.branchInventory:id,branch_id,variation_id',
        ]);
        $this->normalizePosReturnForDisplay($return);

        return response()->json([
            'success' => true,
            'message' => 'Return status updated.',
            'data' => $return,
        ]);
    }

    public function createPickup(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId > 0 && (int) $return->store_id !== $storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to return request.',
            ], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
            'pickup_name' => ['nullable', 'string', 'max:255'],
            'pickup_phone' => ['nullable', 'string', 'max:255'],
            'pickup_address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ((string) $return->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Pickup can only be scheduled for approved returns.',
            ], 422);
        }
        $originBranchId = $this->resolveReturnSourceBranchId($return);
        if (!$originBranchId) {
            return response()->json([
                'success' => false,
                'message' => 'The original fulfillment branch could not be determined for this return.',
            ], 422);
        }
        $pickup = null;
        DB::transaction(function () use (&$pickup, $return, $validated, $originBranchId): void {
            $pickup = ReturnPickup::query()->firstOrCreate(
                ['return_id' => (int) $return->id],
                [
                    'store_id' => (int) $return->store_id,
                    'destination_branch_id' => $originBranchId,
                    'status' => 'ready_for_dispatch',
                    'scheduled_at' => null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            $pickup->destination_branch_id = $originBranchId;
            $pickup->scheduled_at = Carbon::parse($validated['scheduled_at'])->startOfDay();
            $pickup->pickup_name = $validated['pickup_name'] ?? $pickup->pickup_name;
            $pickup->pickup_phone = $validated['pickup_phone'] ?? $pickup->pickup_phone;
            $pickup->pickup_address = $validated['pickup_address'] ?? $pickup->pickup_address;
            $pickup->notes = $validated['notes'] ?? $pickup->notes;
            $pickup->updated_by = auth()->id();
            $pickup->save();
        });

        $return->load([
            'pickup:id,store_id,return_id,destination_branch_id,status,scheduled_at,pickup_name,pickup_phone,pickup_address,driver_user_id,picked_up_at,created_at',
            'pickup.destinationBranch:id,name,branch_type,address,city,province,latitude,longitude',
            'pickup.driver:id,fname,lname,email',
            'salesOrder:id,store_id,branch_id,order_number,status,customer_name,customer_phone,delivery_email,delivery_address,created_at,paid_at',
            'salesOrderItem:id,order_id,product_id,variation_id,branch_inventory_id,product_name,sku,quantity,unit_price',
            'salesOrderItem.product:id,product_name,sku',
        ]);
        $this->normalizePosReturnForDisplay($return);

        // Notify customer that pickup is scheduled.
        if ($pickup && $return->user_id) {
            $return->loadMissing(['order:id,order_number', 'salesOrder:id,order_number', 'user:id,email']);
            $orderNumber = $return->sourceOrder()?->order_number ?? ('Return #' . (int) $return->id);
            $this->notify((int) $return->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'return_pickup',
                'entity_id' => (int) $pickup->id,
                'action' => 'return_pickup_scheduled',
                'title' => 'Return pickup scheduled',
                'message' => "Your return pickup for {$orderNumber} has been scheduled on " . $pickup->scheduled_at?->format('M d, Y') . '.',
                'severity' => 'info',
                'store_id' => (int) $return->store_id,
                'link' => '/orders/' . (int) $return->order_id,
                'data' => ['status' => 'scheduled'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pickup scheduled.',
            'data' => [
                'return' => $return,
                'pickup' => $return->pickup,
            ],
        ]);
    }

    public function receive(Request $request, EcommerceOrderReturn $return): JsonResponse
    {
        abort_unless($request->user()->hasAnyPermission([
            'crm.returns.manage', 'inventory.receiving.manage', 'warehouse.receiving.view',
        ]) || $request->user()->hasRole('owner'), 403);
        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId <= 0 || (int) $return->store_id !== $storeId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'in:good,bad'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $maxQty = (int) ($return->requested_quantity ?? 1);
        $receivedQty = (int) $validated['received_quantity'];
        if ($receivedQty > $maxQty) {
            return response()->json(['success' => false, 'message' => "Received quantity must be between 1 and {$maxQty}."], 422);
        }

        if ((string) $return->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Only an approved return can be inspected.'], 422);
        }
        $return->loadMissing('pickup');
        if ((string) $return->pickup?->status !== 'delivered') {
            return response()->json(['success' => false, 'message' => 'The returned item must arrive at its destination branch before inspection.'], 422);
        }
        if (!in_array((string) $return->return_type, ['refund', 'replacement'], true)) {
            return response()->json(['success' => false, 'message' => 'Sales must select Refund or Replacement before Inventory inspection.'], 422);
        }

        $user = $request->user();
        $employeeId = (int) ($user?->employee?->id ?? 0);
        $employeeBranchId = (int) ($user?->employee?->branch_id ?? $user?->branch_id ?? 0);
        $branchId = (int) ($return->pickup?->destination_branch_id ?? 0);
        if ($employeeId <= 0 || $employeeBranchId <= 0) {
            return response()->json(['success' => false, 'message' => 'User must be linked to an employee + branch to post inventory receive.'], 422);
        }
        if ($branchId <= 0) {
            return response()->json(['success' => false, 'message' => 'The return pickup has no destination branch. Assign one before inspection.'], 422);
        }
        if ($employeeBranchId !== $branchId && ! $user->hasRole('owner')) {
            $return->loadMissing('pickup.destinationBranch:id,name');
            $destinationName = $return->pickup?->destinationBranch?->name ?? 'the selected branch';
            return response()->json(['success' => false, 'message' => "Receive this return at {$destinationName}, or ask the store owner to complete the inspection."], 422);
        }

        if ($return->source_type === 'pos') {
            $return->loadMissing(['salesOrder', 'salesOrderItem.branchInventory']);
        } else {
            $return->loadMissing(['order', 'orderItem.branchInventory']);
        }
        $sourceOrder = $return->sourceOrder();
        $sourceItem = $return->sourceOrderItem();
        $productId = (int) ($sourceItem?->product_id ?? 0);
        $variationId = $sourceItem?->variation_id ?? $sourceItem?->branchInventory?->variation_id;
        if ($return->return_type === 'replacement' && $sourceItem?->branch_inventory_id && !$sourceItem?->branchInventory) {
            return response()->json(['success' => false, 'message' => 'The original item SKU is unavailable. Restore its inventory record before processing a replacement.'], 422);
        }
        if ($productId <= 0) {
            return response()->json(['success' => false, 'message' => 'Return item product is missing.'], 422);
        }

        $refund = null;
        DB::transaction(function () use ($return, $validated, $receivedQty, $productId, $variationId, $branchId, $employeeId, $user, $sourceOrder, $sourceItem, &$refund): void {
            $lockedReturn = EcommerceOrderReturn::query()->lockForUpdate()->findOrFail($return->id);
            if ($lockedReturn->status !== 'approved') {
                throw \Illuminate\Validation\ValidationException::withMessages(['return' => 'This return has already been received.']);
            }
            $inventory = BranchInventory::query()
                ->where('store_id', (int) $return->store_id)
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->where('variation_id', $variationId)
                ->lockForUpdate()
                ->first();

            if (! $inventory) {
                $inventory = BranchInventory::query()->create([
                    'store_id' => (int) $return->store_id,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'quantity_damaged' => 0,
                    'quantity_incoming' => 0,
                    'reorder_point' => 0,
                    'reorder_quantity' => 0,
                    'stock_status' => 'out_of_stock',
                ]);
            }

            $quantityBefore = (int) $inventory->quantity_on_hand;
            $availableBefore = (int) $inventory->quantity_available;

            // Replacement returns remain quarantined until a separate disposition.
            if ($return->return_type === 'replacement') {
                $inventory->quantity_quarantined = (int) $inventory->quantity_quarantined + $receivedQty;
            } elseif ($validated['condition'] === 'good') {
                $inventory->quantity_on_hand = $quantityBefore + $receivedQty;
                $inventory->quantity_available = (int) $inventory->quantity_available + $receivedQty;
            }

            $receivedQuantityAfter = (int) $inventory->quantity_on_hand;
            InventoryTransaction::query()->create([
                'transaction_number' => 'INVTX-RET-' . (int) $return->id . '-' . now()->format('YmdHisv'),
                'store_id' => (int) $return->store_id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variation_id' => $variationId,
                'transaction_type' => $return->return_type === 'replacement' || $validated['condition'] === 'good' ? 'customer_return' : 'writeoff',
                'quantity_before' => $quantityBefore,
                'quantity_change' => $return->return_type === 'replacement' ? 0 : ($validated['condition'] === 'good' ? $receivedQty : 0),
                'quantity_after' => $receivedQuantityAfter,
                'reference_type' => 'ecommerce_order_return',
                'reference_id' => (int) $return->id,
                'notes' => $validated['notes'] ?? ($return->return_type === 'replacement' ? 'Returned unit received into quarantine; not sellable.' : ($validated['condition'] === 'good' ? 'Returned item approved for resale.' : 'Returned item discarded after quality inspection.')),
                'unit_cost' => $sourceItem?->unit_price,
                'total_value' => (float) ($sourceItem?->unit_price ?? 0) * $receivedQty,
                'created_by' => $employeeId,
                'transaction_date' => now(),
            ]);

            $available = (int) $inventory->quantity_available;
            $reorderPoint = (int) ($inventory->reorder_point ?? 0);
            $inventory->stock_status = $available <= 0 ? 'out_of_stock' : ($available <= $reorderPoint ? 'low_stock' : 'in_stock');
            $inventory->suppressAutomaticTransactionLog();
            $inventory->save();

            if ($return->return_type === 'refund') {
                $refundRouting = app(EcommerceRefundRoutingService::class);
                $refundMethod = $return->source_type !== 'pos' && $sourceOrder && $refundRouting->canRefundToOriginal($sourceOrder)
                    ? $refundRouting->originalMethod($sourceOrder)
                    : null;
                $amountCalculation = app(ReturnRefundAmountService::class)->calculate($return, $receivedQty);
                $amount = $amountCalculation['amount'];
                $refund = FinanceRefund::query()->updateOrCreate(
                    [
                        'store_id' => (int) $return->store_id,
                        'order_type' => 'ecommerce_return',
                        'order_id' => (int) $return->id,
                    ],
                    [
                        'branch_id' => $branchId,
                        'order_number' => $sourceOrder?->order_number,
                        'customer_name' => $return->return_contact_name ?: ($sourceOrder?->shipping_name ?? $sourceOrder?->customer_name),
                        'reason' => 'Approved customer return #' . (int) $return->id,
                        'amount' => $amount,
                        'status' => 'pending',
                        'requested_by' => (int) ($user?->id ?? 0) ?: null,
                        'refund_method' => $refundMethod,
                        'notes' => trim(($refundMethod
                            ? 'Inventory inspection complete. Ready for refund to the original payment method.'
                            : 'Inventory inspection complete. Awaiting customer payout details for Finance.')
                            . "\nCalculated from {$amountCalculation['quantity']} inspected unit(s) at PHP "
                            . number_format($amountCalculation['unit_price'], 2) . ' each, less PHP '
                            . number_format($amountCalculation['return_fee'], 2) . ' return fee. Paid order balance before this refund: PHP '
                            . number_format($amountCalculation['available_paid_balance'], 2) . '.'),
                    ]
                );
                $return->status = 'refund_pending';
            } else {
                $return->status = $return->replacement_status === 'delivered' ? 'replaced' : 'received';
                if (!in_array($return->replacement_status, ['reserved', 'assigned', 'out_for_delivery', 'delivered'], true)) {
                    $return->replacement_status = 'awaiting_stock';
                }
            }
            if ($return->return_type === 'replacement'
                && $lockedReturn->replacement_inventory_id
                && !in_array($lockedReturn->replacement_status, ['out_for_delivery', 'delivered'], true)) {
                $replacementInventory = BranchInventory::query()->where('store_id', (int) $return->store_id)
                    ->lockForUpdate()->findOrFail((int) $lockedReturn->replacement_inventory_id);
                $reservationReduction = max(0, (int) $lockedReturn->requested_quantity - $receivedQty);
                if ($reservationReduction > 0) {
                    if ((int) $replacementInventory->quantity_reserved < $reservationReduction) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'replacement' => 'The replacement reservation changed unexpectedly. Reconcile reserved stock before completing this inspection.',
                        ]);
                    }
                    $replacementInventory->quantity_reserved -= $reservationReduction;
                    $replacementInventory->save();
                }
            }

            $lockedReturn->status = $return->return_type === 'replacement'
                && $lockedReturn->replacement_status === 'delivered'
                ? 'replaced'
                : ($return->return_type === 'refund' ? 'refund_pending' : 'received');
            if ($return->return_type === 'replacement'
                && !in_array($lockedReturn->replacement_status, ['reserved', 'assigned', 'out_for_delivery', 'delivered'], true)) {
                $lockedReturn->replacement_status = 'awaiting_stock';
            }
            $lockedReturn->product_condition = $validated['condition'];
            $lockedReturn->inventory_disposition = $return->return_type === 'replacement' ? null : ($validated['condition'] === 'good' ? 'resell' : 'discard');
            $lockedReturn->received_quantity = $receivedQty;
            $lockedReturn->inspected_by = auth()->id();
            $lockedReturn->inspected_at = now();
            $lockedReturn->inspection_notes = $validated['notes'] ?? null;
            if ($lockedReturn->status === 'replaced') {
                $lockedReturn->resolved_at = $lockedReturn->resolved_at ?: now();
            }
            $lockedReturn->save();
        });

        $return->refresh();

        if ($return->user_id && $return->source_type !== 'pos') {
            $inspectionStatus = (string) $return->status;
            $this->notify((int) $return->user_id, [
                'module' => 'ecommerce',
                'entity_type' => 'ecommerce_order_return',
                'entity_id' => (int) $return->id,
                'action' => $return->return_type === 'refund'
                    ? 'refund_pending'
                    : ($return->status === 'replaced' ? 'replacement_completed' : 'return_received'),
                'title' => $return->return_type === 'refund'
                    ? 'Return sent to Finance'
                    : ($return->status === 'replaced' ? 'Replacement exchange completed' : 'Return received for replacement'),
                'message' => $return->return_type === 'refund'
                    ? 'Inventory inspected your item. Your refund request was sent to Finance.'
                    : ($return->status === 'replaced'
                        ? 'The returned item was inspected at the branch. Your replacement was already delivered.'
                        : 'Inventory inspected your item. The replacement delivery is being prepared.'),
                'severity' => 'success',
                'store_id' => (int) $return->store_id,
                'link' => '/orders/' . (int) $return->order_id,
                'data' => ['status' => $inspectionStatus],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $return->return_type === 'refund'
                ? 'Inspection complete. Refund request sent to Finance.'
                : 'Inspection complete. Returned item quarantined; replacement awaits stock reservation.',
            'data' => $return->fresh()->load(['inspector:id,fname,lname,email']),
        ]);
    }

    private function normalizePosReturnForDisplay(EcommerceOrderReturn $return): void
    {
        $return->normalizeSourceRelationsForDisplay();
    }

    private function resolveReturnSourceBranchId(EcommerceOrderReturn $return): ?int
    {
        if ($return->source_type === 'pos') {
            $return->loadMissing(['salesOrder', 'salesOrderItem.branchInventory']);
        } else {
            $return->loadMissing(['order', 'orderItem.branchInventory']);
        }
        $sourceOrder = $return->sourceOrder();
        $sourceItem = $return->sourceOrderItem();

        $branchId = null;
        $inventoryId = (int) ($sourceItem?->branch_inventory_id ?? 0);
        if ($inventoryId > 0) {
            $branchId = BranchInventory::withTrashed()
                ->where('store_id', (int) $return->store_id)
                ->whereKey($inventoryId)
                ->value('branch_id');
        }

        $branchId = (int) ($branchId ?: $sourceOrder?->assigned_branch_id ?: $sourceOrder?->branch_id);
        if ($branchId <= 0) {
            return null;
        }

        return Branch::query()
            ->whereKey($branchId)
            ->where('store_id', (int) $return->store_id)
            ->value('id');
    }

}
