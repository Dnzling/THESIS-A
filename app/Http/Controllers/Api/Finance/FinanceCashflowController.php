<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Services\Finance\CashflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceCashflowController extends Controller
{
    public function accountSummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();
        $account = $service->getOrCreateOperatingAccount($storeId, Auth::id());

        $recent = FinanceCashflowTransaction::where('store_id', $storeId)
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account' => $account,
                'available_balance' => (float) $account->current_balance,
                'recent_transactions' => $recent,
            ],
        ]);
    }

    public function topUp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();
        $transaction = $service->topUp(
            $storeId,
            (float) $validated['amount'],
            Auth::id(),
            $validated['description'] ?? 'Manual top-up',
            $validated['payment_method'] ?? null,
            ['notes' => $validated['notes'] ?? null]
        );

        $balance = $service->getAvailableBalance($storeId);

        return response()->json([
            'success' => true,
            'message' => 'Cashflow top-up recorded successfully',
            'data' => [
                'transaction' => $transaction,
                'available_balance' => $balance,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);

        $query = FinanceCashflowTransaction::where('store_id', $storeId)
            ->orderByDesc('id');

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate($request->integer('per_page', 50));

        $summaryQuery = FinanceCashflowTransaction::where('store_id', $storeId);
        if ($request->filled('reference_type')) {
            $summaryQuery->where('reference_type', $request->reference_type);
        }
        if ($request->filled('date_from')) {
            $summaryQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $summaryQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $totalIncoming = (float) (clone $summaryQuery)->where('direction', 'in')->sum('amount');
        $totalOutgoing = (float) (clone $summaryQuery)->where('direction', 'out')->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => [
                    'incoming' => $totalIncoming,
                    'outgoing' => $totalOutgoing,
                    'net' => $totalIncoming - $totalOutgoing,
                ],
            ],
        ]);
    }

    public function transactionDetail(Request $request, int $id): JsonResponse
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        $transaction = FinanceCashflowTransaction::query()
            ->with(['account:id,name,type', 'creator:id,fname,lname,email'])
            ->where('store_id', $storeId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction' => $transaction,
                'source' => $this->resolveSourceDetails($transaction),
            ],
        ]);
    }

    private function resolveSourceDetails(FinanceCashflowTransaction $transaction): array
    {
        $sourceType = (string) $transaction->reference_type;
        $sourceId = (int) $transaction->reference_id;
        $source = [
            'label' => ucwords(str_replace('_', ' ', $sourceType ?: 'Cashflow entry')),
            'reference_number' => null,
            'fields' => [],
            'items' => [],
        ];

        $tables = [
            'sales_order' => ['sales_pos_orders', 'sales_pos_order_items'],
            'sales_pos_order' => ['sales_pos_orders', 'sales_pos_order_items'],
            'ecommerce_order' => ['ecommerce_orders', 'ecommerce_order_items'],
            'invoice' => ['invoices', null],
            'finance_expense' => ['finance_expenses', null],
            'finance_refund' => ['finance_refunds', null],
            'cash_advance' => ['finance_cash_advances', 'finance_liquidation_items'],
            'cash_advance_return' => ['finance_cash_advances', 'finance_liquidation_items'],
            'liquidation_reimbursement' => ['finance_cash_advances', 'finance_liquidation_items'],
        ];

        if ($sourceType === 'payroll' && $sourceId && Schema::hasTable('payrolls') && Schema::hasTable('employees')) {
            $row = DB::table('payrolls')
                ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                ->where('employees.store_id', $transaction->store_id)
                ->where('payrolls.id', $sourceId)
                ->select([
                    'payrolls.id', 'payrolls.employee_id', 'payrolls.pay_period_id', 'payrolls.base_salary',
                    'payrolls.overtime_amount', 'payrolls.deductions_total', 'payrolls.bonuses_total',
                    'payrolls.allowances_total', 'payrolls.tax_amount', 'payrolls.net_salary', 'payrolls.status',
                    'payrolls.payment_date', 'payrolls.payment_method', 'payrolls.reference_number', 'payrolls.notes',
                    'employees.fname', 'employees.lname', 'employees.employee_number',
                ])->first();

            if ($row) {
                $source['label'] = 'Payroll';
                $source['reference_number'] = $row->reference_number ?: null;
                $source['fields'] = $this->presentFields((array) $row, [
                    'fname' => 'Employee first name', 'lname' => 'Employee last name',
                    'employee_number' => 'Employee number', 'pay_period_id' => 'Pay period ID',
                    'base_salary' => 'Base salary', 'overtime_amount' => 'Overtime',
                    'allowances_total' => 'Allowances', 'bonuses_total' => 'Bonuses',
                    'deductions_total' => 'Deductions', 'tax_amount' => 'Tax', 'net_salary' => 'Net salary',
                    'status' => 'Payroll status', 'payment_date' => 'Payment date',
                    'payment_method' => 'Payroll payment method', 'reference_number' => 'Payment reference',
                    'notes' => 'Payroll notes',
                ]);
            }
        } elseif ($sourceId && isset($tables[$sourceType])) {
            [$table, $itemsTable] = $tables[$sourceType];
            if (Schema::hasTable($table)) {
                $query = DB::table($table)->where('id', $sourceId);
                if (Schema::hasColumn($table, 'store_id')) {
                    $query->where('store_id', $transaction->store_id);
                }
                $row = $query->first();

                if ($row) {
                    $attributes = (array) $row;
                    $referenceField = match ($sourceType) {
                        'sales_order', 'sales_pos_order', 'ecommerce_order', 'finance_refund' => 'order_number',
                        'invoice' => 'invoice_number',
                        'finance_expense' => 'reference_number',
                        'cash_advance', 'cash_advance_return', 'liquidation_reimbursement' => 'advance_number',
                        default => null,
                    };
                    $source['reference_number'] = $referenceField ? ($attributes[$referenceField] ?? null) : null;
                    $fieldLabels = match ($sourceType) {
                        'sales_order', 'sales_pos_order' => [
                            'order_number' => 'Order number', 'customer_name' => 'Customer',
                            'payment_method' => 'Payment method', 'payment_status' => 'Payment status',
                            'status' => 'Order status', 'subtotal' => 'Subtotal', 'discount_amount' => 'Discount',
                            'tax_amount' => 'Tax', 'shipping_fee' => 'Shipping', 'total_amount' => 'Order total',
                            'amount_tendered' => 'Amount tendered', 'change_amount' => 'Change', 'notes' => 'Order notes',
                        ],
                        'ecommerce_order' => [
                            'order_number' => 'Order number', 'shipping_name' => 'Customer',
                            'shipping_phone' => 'Customer phone', 'shipping_email' => 'Customer email',
                            'shipping_address' => 'Delivery address', 'payment_method' => 'Payment method',
                            'payment_status' => 'Payment status', 'status' => 'Order status', 'subtotal' => 'Subtotal',
                            'discount_amount' => 'Discount', 'tax_amount' => 'Tax', 'shipping_fee' => 'Shipping',
                            'total_amount' => 'Order total', 'notes' => 'Order notes',
                        ],
                        'invoice' => [
                            'invoice_number' => 'Invoice number', 'invoice_date' => 'Invoice date', 'due_date' => 'Due date',
                            'invoice_amount' => 'Invoice amount', 'tax_amount' => 'Tax', 'shipping_cost' => 'Shipping',
                            'discount_amount' => 'Discount', 'net_amount' => 'Net amount', 'status' => 'Invoice status',
                            'payment_status' => 'Payment status', 'payment_date' => 'Payment date',
                            'payment_amount' => 'Payment amount', 'payment_method' => 'Payment method', 'remarks' => 'Remarks',
                        ],
                        'finance_expense' => [
                            'category' => 'Category', 'department' => 'Department', 'expense_date' => 'Expense date',
                            'amount' => 'Expense amount', 'status' => 'Expense status', 'payment_method' => 'Payment method',
                            'payment_date' => 'Payment date', 'reference_number' => 'Reference number',
                            'reference_type' => 'Related record type', 'reference_id' => 'Related record ID',
                            'description' => 'Description', 'notes' => 'Notes',
                        ],
                        'finance_refund' => [
                            'order_number' => 'Original order', 'customer_name' => 'Customer', 'reason' => 'Reason',
                            'amount' => 'Refund amount', 'status' => 'Refund status', 'notes' => 'Notes',
                            'processed_at' => 'Processed at',
                        ],
                        'cash_advance', 'cash_advance_return', 'liquidation_reimbursement' => [
                            'advance_number' => 'Advance number', 'purpose' => 'Purpose',
                            'advance_amount' => 'Advance amount', 'liquidated_amount' => 'Liquidated amount',
                            'cash_returned' => 'Cash returned', 'reimbursement_amount' => 'Reimbursement',
                            'needed_date' => 'Needed date', 'payment_method' => 'Payment method',
                            'status' => 'Status', 'notes' => 'Notes', 'review_notes' => 'Review notes',
                        ],
                        default => [],
                    };
                    $source['fields'] = $this->presentFields($attributes, $fieldLabels);
                    $source['label'] = match ($sourceType) {
                        'sales_order', 'sales_pos_order' => 'POS sale',
                        'ecommerce_order' => 'Ecommerce order',
                        'invoice' => 'Supplier invoice',
                        'finance_expense' => 'Expense',
                        'finance_refund' => 'Customer refund',
                        'cash_advance' => 'Cash advance release',
                        'cash_advance_return' => 'Cash advance return',
                        'liquidation_reimbursement' => 'Liquidation reimbursement',
                        default => $source['label'],
                    };

                    if ($sourceType === 'invoice' && !empty($attributes['supplier_id']) && Schema::hasTable('suppliers')) {
                        $supplier = DB::table('suppliers')->where('id', $attributes['supplier_id'])->first();
                        $supplierName = $supplier->supplier_name ?? $supplier->company_name ?? null;
                        if ($supplierName) {
                            $source['fields'][] = ['label' => 'Supplier', 'value' => $supplierName];
                        }
                    }

                    if ($sourceType === 'finance_expense' && Schema::hasTable('users')) {
                        foreach (['requested_by' => 'Requested by', 'approved_by' => 'Approved by', 'paid_by' => 'Paid by'] as $column => $label) {
                            $userId = $attributes[$column] ?? null;
                            if (!$userId) continue;
                            $user = DB::table('users')->where('id', $userId)->first(['fname', 'lname', 'email']);
                            if ($user) {
                                $source['fields'][] = [
                                    'label' => $label,
                                    'value' => trim(($user->fname ?? '') . ' ' . ($user->lname ?? '')) ?: ($user->email ?? 'User'),
                                ];
                            }
                        }
                    }

                    if ($itemsTable && Schema::hasTable($itemsTable) && Schema::hasColumn($itemsTable, 'order_id')) {
                        $itemLabels = [
                            'product_name' => 'Product', 'sku' => 'SKU', 'quantity' => 'Quantity',
                            'unit_price' => 'Unit price', 'line_total' => 'Line total',
                        ];
                        $source['items'] = DB::table($itemsTable)->where('order_id', $sourceId)->get()->map(
                            fn ($item) => $this->presentFields((array) $item, $itemLabels)
                        )->all();
                    }

                    if ($itemsTable && Schema::hasTable($itemsTable) && Schema::hasColumn($itemsTable, 'cash_advance_id')) {
                        $itemLabels = [
                            'expense_date' => 'Expense date', 'category' => 'Category',
                            'description' => 'Description', 'amount' => 'Amount', 'receipt_name' => 'Receipt',
                        ];
                        $source['items'] = DB::table($itemsTable)->where('cash_advance_id', $sourceId)->get()->map(
                            fn ($item) => $this->presentFields((array) $item, $itemLabels)
                        )->values()->all();
                    }
                }
            }
        }

        return $source;
    }

    private function presentFields(array $attributes, array $labels): array
    {
        $fields = [];
        foreach ($labels as $key => $label) {
            $value = $attributes[$key] ?? null;
            if ($value !== null && $value !== '') {
                $fields[] = ['label' => $label, 'value' => $value];
            }
        }
        return $fields;
    }

    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Enforce Online Payment-only funding source for top-ups.
        if (($validated['direction'] ?? '') === 'in') {
            $validated['payment_method'] = 'paymongo_gcash';
        }

        $user = Auth::user();
        $storeId = (int) ($user?->store_id ?? 0);
        $service = new CashflowService();

        try {
            $transaction = $validated['direction'] === 'in'
                ? $service->credit(
                    $storeId,
                    (float) $validated['amount'],
                    'manual_adjustment',
                    0,
                    Auth::id(),
                    $validated['description'] ?? 'Manual budget adjustment (add)',
                    $validated['payment_method'] ?? null,
                    ['notes' => $validated['notes'] ?? null]
                )
                : $service->debit(
                    $storeId,
                    (float) $validated['amount'],
                    'manual_adjustment',
                    0,
                    Auth::id(),
                    $validated['description'] ?? 'Manual budget adjustment (deduct)',
                    $validated['payment_method'] ?? null,
                    ['notes' => $validated['notes'] ?? null]
                );
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Budget adjustment recorded successfully.',
            'data' => [
                'transaction' => $transaction,
                'available_balance' => $service->getAvailableBalance($storeId),
            ],
        ]);
    }
}
