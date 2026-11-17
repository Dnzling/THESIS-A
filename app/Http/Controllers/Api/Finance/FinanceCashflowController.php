<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceCashflowTransaction;
use App\Services\Finance\CashflowService;
use Carbon\Carbon;
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
        $transactions = $this->filteredTransactionsQuery($request, $storeId)
            ->paginate($request->integer('per_page', 50));
        $this->attachListReferences($transactions->getCollection(), $storeId);

        $periodEnd = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();
        $periodStart = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : ($request->filled('date_to')
                ? Carbon::parse($request->input('date_to'))->startOfDay()
                : $periodEnd->copy()->subDays(27)->startOfDay());
        if ($periodStart->greaterThan($periodEnd)) {
            [$periodStart, $periodEnd] = [$periodEnd->copy()->startOfDay(), $periodStart->copy()->endOfDay()];
        }
        $periodDays = max(1, $periodStart->diffInDays($periodEnd) + 1);
        $previousEnd = $periodStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        $summaryQuery = FinanceCashflowTransaction::where('store_id', $storeId);
        if ($request->filled('reference_type')) {
            $summaryQuery->where('reference_type', $request->reference_type);
        }

        $currentSummary = (clone $summaryQuery)->whereBetween('created_at', [$periodStart, $periodEnd]);
        $previousSummary = (clone $summaryQuery)->whereBetween('created_at', [$previousStart, $previousEnd]);
        $totalIncoming = (float) (clone $currentSummary)->where('direction', 'in')->sum('amount');
        $totalOutgoing = (float) (clone $currentSummary)->where('direction', 'out')->sum('amount');
        $previousIncoming = (float) (clone $previousSummary)->where('direction', 'in')->sum('amount');
        $previousOutgoing = (float) (clone $previousSummary)->where('direction', 'out')->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'summary' => [
                    'incoming' => $totalIncoming,
                    'outgoing' => $totalOutgoing,
                    'net' => $totalIncoming - $totalOutgoing,
                    'previous' => [
                        'incoming' => $previousIncoming,
                        'outgoing' => $previousOutgoing,
                        'net' => $previousIncoming - $previousOutgoing,
                    ],
                    'period' => [
                        'start' => $periodStart->toDateString(),
                        'end' => $periodEnd->toDateString(),
                        'previous_start' => $previousStart->toDateString(),
                        'previous_end' => $previousEnd->toDateString(),
                    ],
                ],
            ],
        ]);
    }

    public function exportTransactions(Request $request)
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        $filename = 'cashflow-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request, $storeId) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Date & Time', 'Direction', 'Amount (PHP)', 'Recorded Balance After (PHP)', 'Source', 'Reference', 'Method']);

            $this->filteredTransactionsQuery($request, $storeId)
                ->reorder()
                ->orderBy('id')
                ->chunkById(500, function ($transactions) use ($output, $storeId) {
                    $this->attachListReferences($transactions, $storeId);
                    foreach ($transactions as $transaction) {
                        fputcsv($output, [
                            $transaction->created_at?->timezone('Asia/Manila')->format('Y-m-d H:i:s'),
                            $transaction->direction === 'in' ? 'Incoming' : 'Outgoing',
                            number_format((float) $transaction->amount, 2, '.', ''),
                            $transaction->balance_after === null ? '' : number_format((float) $transaction->balance_after, 2, '.', ''),
                            $this->formatCashflowSource($transaction->reference_type),
                            $transaction->source_reference ?? '',
                            $this->formatCashflowPaymentMethod($transaction->payment_method),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function printTransactions(Request $request)
    {
        $storeId = (int) ($request->user()?->store_id ?? 0);
        $transactions = $this->filteredTransactionsQuery($request, $storeId)->get();
        $this->attachListReferences($transactions, $storeId);

        $pdf = \PDF::loadView('finance.cashflow-print', [
            'store' => DB::table('stores')->where('id', $storeId)->first(['name']),
            'transactions' => $transactions,
            'filters' => [
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'direction' => $request->input('direction'),
                'source' => $request->input('reference_type'),
            ],
            'printedAt' => now()->timezone('Asia/Manila'),
            'printedBy' => trim(($request->user()?->fname ?? '') . ' ' . ($request->user()?->lname ?? '')) ?: ($request->user()?->email ?? 'System'),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('cashflow-transactions-' . now()->format('Ymd-His') . '.pdf');
    }

    private function filteredTransactionsQuery(Request $request, int $storeId)
    {
        $query = FinanceCashflowTransaction::where('store_id', $storeId)->orderByDesc('id');

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->input('reference_type'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('payment_method', 'like', "%{$search}%")
                    ->orWhere('reference_type', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return $query;
    }

    private function formatCashflowSource(?string $value): string
    {
        $labels = [
            'sales_order' => 'POS Sale', 'sales_pos_order' => 'POS Sale',
            'ecommerce_order' => 'Ecommerce', 'invoice' => 'Supplier Invoice',
            'finance_expense' => 'Expense', 'finance_refund' => 'Customer Refund',
            'payroll' => 'Payroll', 'cash_advance' => 'Cash Advance',
            'cash_advance_return' => 'Returned Advance Cash',
            'liquidation_reimbursement' => 'Liquidation Reimbursement',
            'manual_adjustment' => 'Manual Adjustment', 'cashflow_topup' => 'Cashflow Top-up',
            'ecommerce_return_replacement' => 'Replacement',
        ];

        return $labels[$value] ?? ($value ? ucwords(str_replace(['_', '-'], ' ', $value)) : 'Manual Entry');
    }

    private function formatCashflowPaymentMethod(?string $value): string
    {
        if (!$value) return '-';
        if (str_contains(strtolower($value), 'paymongo') || strtolower($value) === 'online_payment') return 'Online Payment';

        return match (strtolower($value)) {
            'gcash' => 'GCash', 'maya' => 'Maya', 'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card', 'bank_transfer' => 'Bank Transfer',
            default => ucwords(str_replace('_', ' ', $value)),
        };
    }

    private function attachListReferences($transactions, int $storeId): void
    {
        foreach ($transactions as $transaction) {
            $transaction->source_label = $this->formatCashflowSource($transaction->reference_type);
            $transaction->formatted_payment_method = $this->formatCashflowPaymentMethod($transaction->payment_method);
        }

        $referenceSources = [
            'sales_order' => ['sales_pos_orders', 'order_number'],
            'sales_pos_order' => ['sales_pos_orders', 'order_number'],
            'ecommerce_order' => ['ecommerce_orders', 'order_number'],
            'invoice' => ['invoices', 'invoice_number'],
            'finance_expense' => ['finance_expenses', 'reference_number'],
            'finance_refund' => ['finance_refunds', 'order_number'],
            'cash_advance' => ['finance_cash_advances', 'advance_number'],
            'cash_advance_return' => ['finance_cash_advances', 'advance_number'],
            'liquidation_reimbursement' => ['finance_cash_advances', 'advance_number'],
            'ecommerce_return_replacement' => ['ecommerce_order_returns', 'return_number'],
        ];

        foreach ($transactions->groupBy('reference_type') as $sourceType => $rows) {
            if (!$sourceType || !isset($referenceSources[$sourceType])) {
                continue;
            }

            [$table, $referenceColumn] = $referenceSources[$sourceType];
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $referenceColumn)) {
                continue;
            }

            $sourceIds = $rows->pluck('reference_id')->filter()->unique()->values();
            if ($sourceIds->isEmpty()) {
                continue;
            }

            $sourceQuery = DB::table($table)->whereIn('id', $sourceIds);
            if (Schema::hasColumn($table, 'store_id')) {
                $sourceQuery->where('store_id', $storeId);
            }

            $references = $sourceQuery->pluck($referenceColumn, 'id');
            foreach ($rows as $transaction) {
                $transaction->source_reference = $references[$transaction->reference_id] ?? null;
            }
        }

        if ($transactions->contains(fn ($transaction) => $transaction->reference_type === 'payroll')) {
            $payrollIds = $transactions->where('reference_type', 'payroll')->pluck('reference_id')->filter()->unique();
            if ($payrollIds->isNotEmpty() && Schema::hasTable('payrolls') && Schema::hasTable('employees')) {
                $references = DB::table('payrolls')
                    ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
                    ->where('employees.store_id', $storeId)
                    ->whereIn('payrolls.id', $payrollIds)
                    ->pluck('payrolls.reference_number', 'payrolls.id');

                foreach ($transactions->where('reference_type', 'payroll') as $transaction) {
                    $transaction->source_reference = $references[$transaction->reference_id] ?? null;
                }
            }
        }
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
        } elseif ($sourceType === 'ecommerce_return_replacement' && $sourceId && Schema::hasTable('ecommerce_order_returns')) {
            $return = DB::table('ecommerce_order_returns')
                ->where('store_id', $transaction->store_id)
                ->where('id', $sourceId)
                ->first();
            if ($return) {
                $source['label'] = 'Higher-value replacement';
                $source['reference_number'] = $return->return_number ?: 'Return #' . $return->id;
                $replacementItemName = null;
                $replacementBranchName = null;
                if (!empty($return->replacement_inventory_id) && Schema::hasTable('branch_inventory')) {
                    $inventory = DB::table('branch_inventory')->where('store_id', $transaction->store_id)
                        ->where('id', $return->replacement_inventory_id)->first(['product_id', 'variation_id', 'branch_id']);
                    if ($inventory) {
                        $product = DB::table('products')->where('store_id', $transaction->store_id)
                            ->where('id', $inventory->product_id)->first(['product_name']);
                        $replacementItemName = $product?->product_name;
                        if ($inventory->variation_id && Schema::hasTable('product_variations')) {
                            $variation = DB::table('product_variations')->where('store_id', $transaction->store_id)
                                ->where('product_id', $inventory->product_id)->where('id', $inventory->variation_id)
                                ->first(['variation_name']);
                            if ($variation?->variation_name) {
                                $replacementItemName = trim(($replacementItemName ?? '') . ' - ' . $variation->variation_name, ' -');
                            }
                        }
                        if ($inventory->branch_id && Schema::hasTable('branches')) {
                            $replacementBranchName = DB::table('branches')->where('store_id', $transaction->store_id)
                                ->where('id', $inventory->branch_id)->value('name');
                        }
                    }
                }
                $source['fields'] = $this->presentFields([
                    'return_number' => $return->return_number,
                    'replacement_item' => $replacementItemName,
                    'replacement_branch' => $replacementBranchName,
                    'replacement_quantity' => $return->received_quantity,
                    'replacement_product_id' => $return->replacement_product_id,
                    'replacement_variation_id' => $return->replacement_variation_id,
                    'replacement_unit_price' => $return->replacement_unit_price,
                    'replacement_price_difference' => $return->replacement_price_difference,
                    'replacement_payment_status' => $return->replacement_payment_status,
                    'replacement_payment_method' => $return->replacement_payment_method,
                    'replacement_payment_reference' => $return->replacement_payment_reference,
                    'replacement_paid_at' => $return->replacement_paid_at,
                ], [
                    'return_number' => 'Return number',
                    'replacement_item' => 'Replacement item',
                    'replacement_branch' => 'Fulfillment branch',
                    'replacement_quantity' => 'Quantity',
                    'replacement_product_id' => 'Replacement product ID',
                    'replacement_variation_id' => 'Replacement variation ID',
                    'replacement_unit_price' => 'Replacement unit price',
                    'replacement_price_difference' => 'Additional amount collected',
                    'replacement_payment_status' => 'Payment status',
                    'replacement_payment_method' => 'Payment method',
                    'replacement_payment_reference' => 'Payment reference',
                    'replacement_paid_at' => 'Collected at',
                ]);
            }
        } elseif ($sourceId && isset($tables[$sourceType]) && $tables[$sourceType][0] !== null) {
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
