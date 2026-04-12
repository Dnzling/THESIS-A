<?php
// backend/app/Http/Controllers/Procurement/Supplier/SupplierContractController.php

namespace App\Http\Controllers\Api\Procurement\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Admin\ViolationReport;
use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class SupplierContractController extends Controller
{
    private function isSupplierPortalRequest(Request $request): bool
    {
        if ($request->is('api/supplier-portal/*')) {
            return true;
        }

        // Supplier-portal screens sometimes reuse procurement endpoints; allow an explicit flag.
        if ($request->boolean('from_supplier_portal') || $request->query('from_supplier_portal')) {
            return true;
        }

        // Fallback: if the authenticated user has a supplier portal profile and the request carries a store_id,
        // treat it as supplier-portal originated.
        $userId = (int) auth()->id();
        if ($userId > 0 && (int) $request->input('store_id', 0) > 0) {
            return SupplierPortal::query()->where('user_id', $userId)->exists();
        }

        return false;
    }

    private function contractLinkForStore(int $contractId): string
    {
        return '/procurement/supplier-contracts/' . $contractId;
    }

    private function contractLinkForSupplier(int $contractId): string
    {
        return '/supplier-portal/contracts/' . $contractId;
    }

    private function notifyStoreUsers(int $storeId, int $contractId, string $title, string $message, string $action = 'info', string $severity = 'info'): void
    {
        $users = User::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->get(['id']);

        foreach ($users as $user) {
            SystemNotification::query()->create([
                'store_id' => $storeId,
                'branch_id' => null,
                'user_id' => (int) $user->id,
                'module' => 'procurement',
                'entity_type' => 'supplier_contract',
                'entity_id' => $contractId,
                'action' => $action,
                'title' => $title,
                'message' => $message,
                'data' => ['contract_id' => $contractId],
                'link' => $this->contractLinkForStore($contractId),
                'severity' => $severity,
                'is_read' => false,
            ]);
        }
    }

    private function notifySupplierUsers(SupplierContract $contract, string $title, string $message, string $action = 'info', string $severity = 'info'): void
    {
        $supplier = Supplier::query()->find((int) $contract->supplier_id);
        if (!$supplier || !$supplier->email) {
            return;
        }

        $email = strtolower(trim((string) $supplier->email));
        if ($email === '') {
            return;
        }

        $supplierIds = Supplier::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->pluck('id');

        if ($supplierIds->isEmpty()) {
            return;
        }

        $portalUsers = SupplierPortal::query()
            ->whereIn('supplier_id', $supplierIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        foreach ($portalUsers as $userId) {
            SystemNotification::query()->create([
                'store_id' => (int) $contract->store_id,
                'branch_id' => null,
                'user_id' => (int) $userId,
                'module' => 'supplier_portal',
                'entity_type' => 'supplier_contract',
                'entity_id' => (int) $contract->id,
                'action' => $action,
                'title' => $title,
                'message' => $message,
                'data' => ['contract_id' => (int) $contract->id],
                'link' => $this->contractLinkForSupplier((int) $contract->id),
                'severity' => $severity,
                'is_read' => false,
            ]);
        }
    }

    private function canAccessAsSupplier($user, SupplierContract $contract): bool
    {
        // Primary check: supplier portal directly points to the same supplier row.
        $direct = SupplierPortal::query()
            ->where('user_id', (int) $user->id)
            ->where('supplier_id', (int) $contract->supplier_id)
            ->exists();

        if ($direct) {
            return true;
        }

        // Fallback check: supplier accounts can be linked to multiple store-specific supplier rows by email.
        $portal = SupplierPortal::query()->with('supplier')->where('user_id', (int) $user->id)->first();
        $supplierEmail = strtolower(trim((string) ($portal?->supplier?->email ?: $user->email)));
        if ($supplierEmail === '') {
            return false;
        }

        return Supplier::query()
            ->where('id', (int) $contract->supplier_id)
            ->whereRaw('LOWER(email) = ?', [$supplierEmail])
            ->exists();
    }

    private function syncCompletedStatuses(): void
    {
        SupplierContract::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => 'completed']);
    }

    /**
     * List all supplier contracts
     * GET /api/procurement/contracts
     */
    public function index(Request $request): JsonResponse
    {
        $this->syncCompletedStatuses();

        $query = SupplierContract::with(['supplier', 'createdBy'])
            ->where('store_id', auth()->user()->store_id);

        // Filters
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('contract_type')) {
            $query->where('contract_type', $request->contract_type);
        }

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('expiring_soon')) {
            $days = $request->get('days', 30);
            $query->expiringSoon($days);
        }

        $contracts = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Show single contract
     * GET /api/procurement/contracts/{id}
     */
    public function show(int $id): JsonResponse
    {
        $this->syncCompletedStatuses();

        $contract = SupplierContract::with(['supplier', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $contract,
        ]);
    }

    /**
     * Create new contract
     * POST /api/procurement/contracts
     */
    public function store(Request $request): JsonResponse
    {
        $isSupplierPortal = $this->isSupplierPortalRequest($request);

        $storeId = (int) (auth()->user()?->store_id ?? 0);
        if ($storeId <= 0) {
            $storeId = (int) $request->input('store_id', 0);
        }

        if ($storeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Store is required when creating a contract from supplier portal.',
            ], 422);
        }

        $employeeId = Employee::query()
            ->where('user_id', (int) auth()->id())
            ->value('id');
        if (!$employeeId) {
            $employeeId = Employee::query()
                ->where('store_id', $storeId)
                ->orderBy('id')
                ->value('id');
        }

        $validated = $request->validate([
            // When supplier portal creates a contract, lock supplier_id to the portal profile (ignore client input).
            'supplier_id' => ($isSupplierPortal ? 'nullable' : 'required') . '|exists:suppliers,id',
            'store_id' => 'nullable|integer|exists:stores,id',
            'contract_title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'contract_type' => 'required|in:supply,service',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'is_tax_exempt' => 'nullable|boolean',
            'tax_note' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'contract_file_path' => 'nullable|string',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'status' => 'nullable|in:draft,pending,active,completed,rejected',
        ]);

        // Generate contract number using datetime for uniqueness
        $contractNumber = 'CON-' . date('YmdHis') . '-' . str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        if ($isSupplierPortal) {
            $portal = SupplierPortal::query()
                ->where('user_id', (int) auth()->id())
                ->first(['supplier_id']);

            if (!$portal || (int) ($portal->supplier_id ?? 0) <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: supplier portal profile is missing.',
                ], 403);
            }

            $validated['supplier_id'] = (int) $portal->supplier_id;
        } else {
            $supplierBelongsToStore = \App\Models\Procurement\Supplier\Supplier::query()
                ->where('id', (int) $validated['supplier_id'])
                ->where('store_id', $storeId)
                ->exists();

            if (!$supplierBelongsToStore) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected supplier does not belong to your store.',
                ], 422);
            }
        }

        $store = \App\Models\Store\Store::query()->find($storeId);
        $storeStatus = strtolower(trim((string) ($store?->status ?? '')));
        if (!$store || !in_array($storeStatus, ['active', 'verified', 'approved', 'approve'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Store is not eligible to create a contract right now. Current status: ' . ($storeStatus !== '' ? $storeStatus : 'unknown'),
            ], 422);
        }

        $supplier = \App\Models\Procurement\Supplier\Supplier::query()->find((int) $validated['supplier_id']);
        if (!$supplier || !in_array((string) $supplier->status, ['active'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier is not eligible for new contracts right now.',
            ], 422);
        }

        $hasActiveContract = SupplierContract::query()
            ->where('store_id', $storeId)
            ->where('supplier_id', (int) $validated['supplier_id'])
            ->where('status', 'active')
            ->exists();

        if ($hasActiveContract) {
            return response()->json([
                'success' => false,
                'message' => 'An active contract already exists with this supplier.',
            ], 422);
        }

        $validated['contract_number'] = $contractNumber;
        $validated['store_id'] = $storeId;
        $validated['status'] = $validated['status'] ?? ($isSupplierPortal ? 'pending' : 'draft');
        $validated['created_by'] = $employeeId ? (int) $employeeId : null;

        if ($request->hasFile('contract_file')) {
            $validated['contract_file_path'] = $request->file('contract_file')->store('supplier-contracts', 'public');
        } else {
            // Generate a PDF (or printable HTML fallback) from our standard template.
            $validated['contract_file_path'] = $this->generateContractPdf($validated, $contractNumber, $storeId, $employeeId);
        }

        $contract = SupplierContract::create($validated);

        if ($isSupplierPortal) {
            $supplierName = (string) ($supplier?->supplier_name ?? $supplier?->company_name ?? $supplier?->contact_person ?? 'A supplier');
            $this->notifyStoreUsers(
                (int) $storeId,
                (int) $contract->id,
                'New contract request from ' . $supplierName,
                'Contract ' . $contractNumber . ' was submitted and is waiting for your approval.',
                'contract_submitted',
                'info'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Supplier contract created successfully',
            'data' => $contract->load('supplier'),
        ], 201);
    }

    /**
     * Update contract
     * PUT /api/procurement/contracts/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        $validated = $request->validate([
            'contract_title' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_type' => 'nullable|in:supply,service',
            'minimum_order_value' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_tax_exempt' => 'nullable|boolean',
            'tax_note' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'contract_file_path' => 'nullable|string',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'status' => 'nullable|in:draft,pending,active,completed,terminated,rejected',
        ]);

        if ($request->hasFile('contract_file')) {
            $validated['contract_file_path'] = $request->file('contract_file')->store('supplier-contracts', 'public');
        }

        $contract->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Contract updated successfully',
            'data' => $contract->fresh(),
        ]);
    }

    private function generateContractPdf(array $validated, string $contractNumber, int $storeId, ?int $employeeId): string
    {
        $store = \App\Models\Store\Store::query()->find($storeId);
        $supplier = \App\Models\Procurement\Supplier\Supplier::query()->find((int) ($validated['supplier_id'] ?? 0));
        $employee = $employeeId ? Employee::query()->find($employeeId) : null;
        $user = auth()->user();
        $fallbackCreator = trim((string) (($user?->fname ?? '') . ' ' . ($user?->lname ?? '')));

        $viewData = [
            'platformName' => config('app.name', 'FurniSync'),
            'contractNumber' => $contractNumber,
            'contractTitle' => $validated['contract_title'] ?? 'Supplier Contract',
            'contractType' => $validated['contract_type'] ?? 'supply',
            'startDate' => $validated['start_date'] ?? null,
            'endDate' => $validated['end_date'] ?? null,
            'discountPercentage' => $validated['discount_percentage'] ?? 0,
            'taxRate' => $validated['tax_rate'] ?? 0,
            'minimumOrderValue' => $validated['minimum_order_value'] ?? null,
            'termsConditions' => $validated['terms_conditions'] ?? '',
            'storeName' => $store?->name ?? 'Store',
            'supplierName' => $supplier?->supplier_name ?? ($supplier?->company_name ?? 'Supplier'),
            'createdBy' => trim((string) ($employee?->fname . ' ' . $employee?->lname)) ?: ($fallbackCreator ?: 'System User'),
            'createdAt' => now(),
        ];

        if (app()->bound('dompdf.wrapper')) {
            $pdf = app('dompdf.wrapper')->loadView('procurement.supplier-contract-pdf', $viewData)->setPaper('a4', 'portrait');
            $path = 'supplier-contracts/generated/' . $contractNumber . '.pdf';
            Storage::disk('public')->put($path, $pdf->output());
            return $path;
        }

        // Fallback when dompdf package is not installed: save printable HTML copy instead of failing contract creation.
        $html = view('procurement.supplier-contract-pdf', $viewData)->render();
        $path = 'supplier-contracts/generated/' . $contractNumber . '.html';
        Storage::disk('public')->put($path, $html);

        return $path;
    }

    /**
     * Activate contract
     * POST /api/procurement/contracts/{id}/activate
     */
    public function activate(int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        if (!in_array((string) $contract->status, ['draft', 'pending'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft or pending contracts can be activated',
            ], 422);
        }

        $contract->update(['status' => 'active']);

        $this->notifySupplierUsers(
            $contract,
            'Contract approved',
            'Your submitted contract has been approved and is now active.',
            'contract_approved',
            'success'
        );

        return response()->json([
            'success' => true,
            'message' => 'Contract activated successfully',
            'data' => $contract,
        ]);
    }

    /**
     * Reject pending contract.
     * POST /api/procurement/supplier-contracts/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:255',
            'details' => 'nullable|string|max:2000',
        ]);

        $contract = SupplierContract::findOrFail($id);
        if ((string) $contract->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending contracts can be rejected.',
            ], 422);
        }

        $reason = trim((string) $validated['reason']);
        $details = trim((string) ($validated['details'] ?? ''));
        $fullReason = $details !== '' ? ($reason . "\n\n" . $details) : $reason;

        $contract->update([
            'status' => 'rejected',
            'rejection_reason' => $fullReason,
            'rejected_by_user_id' => (int) auth()->id(),
            'rejected_at' => now(),
        ]);

        $this->notifySupplierUsers(
            $contract,
            'Contract rejected',
            'Your contract submission was rejected. Reason: ' . $reason,
            'contract_rejected',
            'danger'
        );

        return response()->json([
            'success' => true,
            'message' => 'Contract rejected successfully.',
            'data' => $contract->fresh(['supplier', 'createdBy']),
        ]);
    }

    /**
     * Terminate contract
     * POST /api/procurement/contracts/{id}/terminate
     */
    public function terminate(Request $request, int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $contract->update([
            'status' => 'terminated',
            'terms_conditions' => ($contract->terms_conditions ?? '') . "\n\nTermination reason: " . $validated['reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract terminated successfully',
        ]);
    }

    /**
     * Request contract termination (counterparty review).
     * POST /api/procurement/supplier-contracts/{id}/terminate-request
     */
    public function requestTermination(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:255',
            'details' => 'nullable|string|max:2000',
        ]);

        $contract = SupplierContract::query()->findOrFail($id);
        $user = auth()->user();

        $isStoreSide = (int) ($user?->store_id ?? 0) === (int) $contract->store_id;
        $isSupplierSide = $this->canAccessAsSupplier($user, $contract);

        if (!$isStoreSide && !$isSupplierSide) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to request termination for this contract.',
            ], 403);
        }

        if ($contract->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Only active contracts can receive termination requests.',
            ], 422);
        }

        if ($contract->termination_request_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'There is already a pending termination request for this contract.',
            ], 422);
        }

        $contract->update([
            'termination_request_status' => 'pending',
            'termination_requested_by_user_id' => (int) $user->id,
            'termination_requested_by_type' => $isSupplierSide ? 'supplier' : 'store_user',
            'termination_request_reason' => trim((string) $validated['reason']) . ($validated['details'] ? ("\n\n" . trim((string) $validated['details'])) : ''),
            'termination_requested_at' => now(),
            'termination_response_by_user_id' => null,
            'termination_response_notes' => null,
            'termination_responded_at' => null,
        ]);

        if ($isSupplierSide) {
            $this->notifyStoreUsers(
                (int) $contract->store_id,
                (int) $contract->id,
                'Termination requested',
                'A supplier requested contract termination. Please review and respond.',
                'termination_requested',
                'warning'
            );
        } else {
            $this->notifySupplierUsers(
                $contract,
                'Termination requested',
                'The store requested contract termination. Please review and respond.',
                'termination_requested',
                'warning'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Termination request submitted to the counterparty.',
            'data' => $contract->fresh(['supplier', 'createdBy']),
        ]);
    }

    /**
     * Respond to termination request.
     * POST /api/procurement/supplier-contracts/{id}/terminate-request/respond
     */
    public function respondTerminationRequest(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => 'required|in:accepted,rejected',
            'notes' => 'nullable|required_if:decision,rejected|string|min:10|max:2000',
        ]);

        $contract = SupplierContract::query()->findOrFail($id);
        $user = auth()->user();

        $isStoreSide = (int) ($user?->store_id ?? 0) === (int) $contract->store_id;
        $isSupplierSide = $this->canAccessAsSupplier($user, $contract);

        if (!$isStoreSide && !$isSupplierSide) {
            return response()->json(['success' => false, 'message' => 'You are not allowed to respond to this request.'], 403);
        }

        if ($contract->termination_request_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'No pending termination request to respond to.'], 422);
        }

        $requesterType = (string) ($contract->termination_requested_by_type ?? '');
        if (($requesterType === 'supplier' && $isSupplierSide) || ($requesterType !== 'supplier' && $isStoreSide)) {
            return response()->json(['success' => false, 'message' => 'Requester cannot respond to their own termination request.'], 422);
        }

        $decision = (string) $validated['decision'];
        $update = [
            'termination_request_status' => $decision,
            'termination_response_by_user_id' => (int) $user->id,
            'termination_response_notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            'termination_responded_at' => now(),
        ];

        if ($decision === 'accepted') {
            $update['status'] = 'terminated';
        }

        $contract->update($update);

        $requesterType = (string) ($contract->termination_requested_by_type ?? '');
        $notifyTitle = $decision === 'accepted' ? 'Termination request accepted' : 'Termination request rejected';
        $notifyMessage = $decision === 'accepted'
            ? 'Your termination request was accepted. The contract is now terminated.'
            : 'Your termination request was rejected. You may escalate to admin if needed.';

        if ($requesterType === 'supplier') {
            $this->notifySupplierUsers($contract, $notifyTitle, $notifyMessage, 'termination_response', $decision === 'accepted' ? 'success' : 'warning');
        } else {
            $this->notifyStoreUsers((int) $contract->store_id, (int) $contract->id, $notifyTitle, $notifyMessage, 'termination_response', $decision === 'accepted' ? 'success' : 'warning');
        }

        return response()->json([
            'success' => true,
            'message' => $decision === 'accepted' ? 'Termination request accepted. Contract terminated.' : 'Termination request rejected.',
            'data' => $contract->fresh(['supplier', 'createdBy']),
        ]);
    }

    /**
     * Get expiring contracts
     * GET /api/procurement/contracts/expiring
     */
    public function expiring(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $contracts = SupplierContract::with('supplier')
            ->where('store_id', auth()->user()->store_id)
            ->expiringSoon($days)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * Delete contract
     * DELETE /api/procurement/contracts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $contract = SupplierContract::findOrFail($id);

        if ($contract->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete active contract',
            ], 422);
        }

        $contract->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contract deleted successfully',
        ]);
    }

    /**
     * Report contract issue/violation from either store or supplier side.
     * POST /api/procurement/supplier-contracts/{id}/report
     */
    public function report(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:255',
            'details' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|image|max:5120',
        ]);

        $contract = SupplierContract::query()->findOrFail($id);
        $user = auth()->user();

        $isStoreSide = (int) ($user?->store_id ?? 0) === (int) $contract->store_id;
        $isSupplierSide = $this->canAccessAsSupplier($user, $contract);

        if (!$isStoreSide && !$isSupplierSide) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to report this contract.',
            ], 403);
        }

        $evidenceUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $evidenceUrls[] = $file->store('violation-reports/contracts/' . $contract->id, 'public');
            }
        }

        $report = ViolationReport::query()->create([
            'store_id' => (int) $contract->store_id,
            'reporter_user_id' => (int) $user->id,
            'reporter_type' => $isSupplierSide ? 'supplier' : 'store_user',
            'report_reason' => trim((string) $validated['reason']),
            'report_details' => trim("Contract #{$contract->contract_number} (ID: {$contract->id})\n" . (string) ($validated['details'] ?? '')),
            'evidence_urls' => $evidenceUrls,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully.',
            'data' => $report,
        ], 201);
    }
}
