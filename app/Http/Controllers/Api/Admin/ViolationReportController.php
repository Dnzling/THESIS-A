<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\SystemNotification;
use App\Models\Core\User;
use App\Models\Admin\ViolationReport;
use App\Models\Admin\ViolationReportResponse;
use App\Models\Procurement\Supplier\Supplier;
use App\Models\Procurement\Supplier\SupplierContract;
use App\Models\Procurement\SupplierPortal\SupplierPortal;
use App\Models\Store\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ViolationReportController extends Controller
{
    public function my(Request $request)
    {
        $user = $request->user();

        $query = ViolationReport::query()
            ->with(['store', 'actionBy'])
            ->where('reporter_user_id', (int) $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        if ($request->filled('contract_id')) {
            $contractId = (int) $request->input('contract_id');
            $query->where('report_details', 'like', "%(ID: {$contractId})%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 10);
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('violation_reports')) {
            $perPage = (int) $request->input('per_page', 15);
            $page = (int) $request->input('page', 1);

            return response()->json([
                'data' => [],
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1,
                'from' => null,
                'to' => null,
            ]);
        }

        $query = ViolationReport::query()
            ->with(['store', 'reporter', 'actionBy'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));
            $query->whereHas('store', function ($storeQuery) use ($term) {
                $storeQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('store_code', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        $reports = $query->paginate($perPage);
        $reports->getCollection()->transform(function ($report) {
            return $this->enrichContractContext($report);
        });

        return response()->json($reports);
    }

    public function show(ViolationReport $violationReport)
    {
        $violationReport->load(['store', 'reporter', 'actionBy', 'responses.responder']);
        $violationReport = $this->enrichContractContext($violationReport);

        return response()->json([
            'data' => $violationReport,
        ]);
    }

    public function responses(Request $request, int $id)
    {
        $report = ViolationReport::query()->findOrFail($id);
        if (!$this->canParticipate($request->user(), $report)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $items = ViolationReportResponse::query()
            ->with(['responder:id,fname,lname,email'])
            ->where('violation_report_id', $report->id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function addResponse(Request $request, int $id)
    {
        $report = ViolationReport::query()->findOrFail($id);
        $user = $request->user();
        if (!$this->canParticipate($user, $report)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:4000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|image|max:5120',
        ]);

        $message = trim((string) ($validated['message'] ?? ''));
        if ($message === '' && !$request->hasFile('attachments')) {
            return response()->json(['success' => false, 'message' => 'Message or attachment is required.'], 422);
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachments[] = $file->store('violation-reports/responses/' . $report->id, 'public');
            }
        }

        $responderType = $user->isSuperAdmin() ? 'admin' : ($report->reporter_user_id === (int) $user->id ? 'reporter' : 'counterparty');

        $response = ViolationReportResponse::query()->create([
            'violation_report_id' => $report->id,
            'responder_user_id' => (int) $user->id,
            'responder_type' => $responderType,
            'message' => $message ?: null,
            'attachments' => $attachments,
        ]);

        $response->load(['responder:id,fname,lname,email']);
        if ($responderType === 'admin') {
            $this->notifyAdminResponse($report, $response, (int) $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Response posted.',
            'data' => $response,
        ], 201);
    }

    public function suspend(Request $request, ViolationReport $violationReport)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return $this->applyAction($violationReport, 'suspended', $validated['reason'], $request->user()->id);
    }

    public function ban(Request $request, ViolationReport $violationReport)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        return $this->applyAction($violationReport, 'banned', $validated['reason'], $request->user()->id);
    }

    private function applyAction(ViolationReport $report, string $storeStatus, string $reason, int $actionBy)
    {
        return DB::transaction(function () use ($report, $storeStatus, $reason, $actionBy) {
            $store = Store::query()->find($report->store_id);
            $contract = $this->resolveContractFromReport($report);
            $supplier = $contract?->supplier_id ? Supplier::query()->find($contract->supplier_id) : null;

            // Decide which party is actioned:
            // - supplier reports -> action store
            // - store users report -> action supplier
            // - other reporter types default to action store
            $targetsSupplier = in_array((string) $report->reporter_type, ['store_user', 'employee'], true)
                && $supplier;

            if ($targetsSupplier) {
                $supplierStatus = $storeStatus === 'banned' ? 'blacklisted' : 'inactive';
                $supplier->update(['status' => $supplierStatus]);
                $this->notifySupplierUsers($report, $supplier, $storeStatus, $reason);
            } elseif ($store) {
                $store->update(['status' => $storeStatus]);
                $this->notifyStoreUsers($report, $store, $storeStatus, $reason);
            }

            // Contract is the operational gate between store and supplier,
            // so admin suspension/ban also forces this reported contract to stop.
            if ($contract && !in_array((string) $contract->status, ['terminated', 'completed'], true)) {
                $contract->update([
                    'status' => 'terminated',
                    'terms_conditions' => trim((string) $contract->terms_conditions)
                        . "\n\n[Admin {$storeStatus}] {$reason}",
                ]);
            }

            $report->update([
                'status' => 'actioned',
                'action_type' => $storeStatus,
                'action_reason' => $reason,
                'action_by' => $actionBy,
                'actioned_at' => now(),
            ]);

            $report->load(['store', 'reporter', 'actionBy']);

            return response()->json([
                'message' => 'Action applied successfully.',
                'data' => $report,
            ]);
        });
    }

    private function enrichContractContext(ViolationReport $report): ViolationReport
    {
        $contract = $this->resolveContractFromReport($report);

        $report->setAttribute('contract_id', $contract?->id);
        $report->setAttribute('contract_number', $contract?->contract_number);
        $report->setAttribute('contract_title', $contract?->contract_title);
        $report->setAttribute('contract_status', $contract?->status);
        $report->setAttribute('supplier', $contract?->supplier);

        return $report;
    }

    private function resolveContractFromReport(ViolationReport $report): ?SupplierContract
    {
        $details = (string) ($report->report_details ?? '');
        if (!preg_match('/\(ID:\s*(\d+)\)/', $details, $matches)) {
            return null;
        }

        return SupplierContract::query()
            ->with(['supplier:id,supplier_name,supplier_code,email,phone', 'store:id,name,store_code'])
            ->find((int) $matches[1]);
    }

    private function canParticipate($user, ViolationReport $report): bool
    {
        if (!$user) {
            return false;
        }
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }
        if ((int) $report->reporter_user_id === (int) $user->id) {
            return true;
        }

        $contract = $this->resolveContractFromReport($report);
        if (!$contract) {
            return (int) ($user->store_id ?? 0) === (int) $report->store_id;
        }

        if ((int) ($user->store_id ?? 0) === (int) $contract->store_id) {
            return true;
        }

        $directPortal = SupplierPortal::query()
            ->where('user_id', (int) $user->id)
            ->where('supplier_id', (int) $contract->supplier_id)
            ->exists();
        if ($directPortal) {
            return true;
        }

        $portal = SupplierPortal::query()->with('supplier:id,email')->where('user_id', (int) $user->id)->first();
        $portalEmail = strtolower(trim((string) ($portal?->supplier?->email ?: $user->email)));
        if ($portalEmail === '') {
            return false;
        }
        $contractSupplierEmail = strtolower(trim((string) ($contract->supplier?->email ?? '')));

        return $contractSupplierEmail !== '' && $portalEmail === $contractSupplierEmail;
    }

    private function notifyStoreUsers(ViolationReport $report, Store $store, string $actionType, string $reason): void
    {
        $users = User::query()
            ->select(['id', 'store_id'])
            ->where('store_id', (int) $store->id)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            SystemNotification::query()->create([
                'store_id' => (int) $store->id,
                'branch_id' => null,
                'user_id' => (int) $user->id,
                'module' => 'admin',
                'entity_type' => 'violation_report',
                'entity_id' => (int) $report->id,
                'action' => $actionType,
                'title' => 'Account status updated by admin',
                'message' => "Your store account has been marked as {$actionType}. Reason: {$reason}",
                'data' => [
                    'report_id' => (int) $report->id,
                    'action_type' => $actionType,
                ],
                'link' => '/admin/violation-reports/' . $report->id,
                'severity' => $actionType === 'banned' ? 'danger' : 'warning',
                'is_read' => false,
            ]);
        }
    }

    private function notifySupplierUsers(ViolationReport $report, Supplier $supplier, string $actionType, string $reason): void
    {
        $emails = Supplier::query()
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $supplier->email)])
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            return;
        }

        $portals = SupplierPortal::query()
            ->with('supplier:id,email')
            ->whereIn('user_id', function ($q) {
                $q->select('id')->from('users');
            })
            ->get()
            ->filter(function ($portal) use ($emails) {
                $email = strtolower((string) ($portal->supplier?->email ?? ''));
                return $email !== '' && $emails->contains(fn($e) => strtolower((string) $e) === $email);
            });

        foreach ($portals as $portal) {
            if (!$portal->user_id) {
                continue;
            }
            SystemNotification::query()->create([
                'store_id' => (int) $report->store_id,
                'branch_id' => null,
                'user_id' => (int) $portal->user_id,
                'module' => 'admin',
                'entity_type' => 'violation_report',
                'entity_id' => (int) $report->id,
                'action' => $actionType,
                'title' => 'Supplier account status updated by admin',
                'message' => "Your supplier account has been marked as {$actionType}. Reason: {$reason}",
                'data' => [
                    'report_id' => (int) $report->id,
                    'action_type' => $actionType,
                ],
                'link' => '/admin/violation-reports/' . $report->id,
                'severity' => $actionType === 'banned' ? 'danger' : 'warning',
                'is_read' => false,
            ]);
        }
    }

    private function notifyAdminResponse(ViolationReport $report, ViolationReportResponse $response, int $adminUserId): void
    {
        $contract = $this->resolveContractFromReport($report);
        $targetUserIds = collect([(int) $report->reporter_user_id]);

        if ($contract) {
            $storeUserIds = User::query()
                ->where('store_id', (int) $contract->store_id)
                ->where('is_active', true)
                ->pluck('id');

            $supplier = Supplier::query()->find((int) $contract->supplier_id);
            $supplierUserIds = collect();
            if ($supplier && $supplier->email) {
                $supplierIds = Supplier::query()
                    ->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $supplier->email))])
                    ->pluck('id');

                $supplierUserIds = SupplierPortal::query()
                    ->whereIn('supplier_id', $supplierIds)
                    ->whereNotNull('user_id')
                    ->pluck('user_id');
            }

            $targetUserIds = $targetUserIds->merge($storeUserIds)->merge($supplierUserIds);
        }

        $targetUserIds = $targetUserIds
            ->filter(fn($id) => (int) $id > 0 && (int) $id !== $adminUserId)
            ->unique()
            ->values();

        foreach ($targetUserIds as $userId) {
            SystemNotification::query()->create([
                'store_id' => (int) $report->store_id,
                'branch_id' => null,
                'user_id' => (int) $userId,
                'module' => 'admin',
                'entity_type' => 'violation_report',
                'entity_id' => (int) $report->id,
                'action' => 'admin_response',
                'title' => 'Admin responded to your report',
                'message' => (string) ($response->message ?: 'Please review the latest admin update on this report.'),
                'data' => [
                    'report_id' => (int) $report->id,
                    'response_id' => (int) $response->id,
                ],
                'link' => '/admin/violation-reports/' . $report->id,
                'severity' => 'info',
                'is_read' => false,
            ]);
        }
    }
}
