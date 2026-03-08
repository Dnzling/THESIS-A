<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Resources\Hr\PayPeriodResource;
use App\Models\Hr\PayPeriod;
use App\Models\Hr\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Config\Exception\ValidationException;
use App\Http\Controllers\Controller;

class PayPeriodController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Start building the query
            $query = PayPeriod::query()
                ->where('store_id', $user->store_id)
                ->whereNull('deleted_at')
                ->select()->with(['store:id,name', 'createdBy:id,fname,lname']);

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->where('end_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->where('start_date', '<=', $request->end_date);
            }

            // Order by
            $orderBy = $request->order_by ?? 'end_date';
            $orderDirection = $request->order_direction ?? 'desc';

            // Simple validation
            $allowedOrderColumns = ['id', 'name', 'start_date', 'end_date', 'cutoff_date', 'status', 'created_at', 'updated_at'];
            if (!in_array($orderBy, $allowedOrderColumns)) {
                $orderBy = 'end_date';
            }

            $orderDirection = in_array(strtolower($orderDirection), ['asc', 'desc']) ? $orderDirection : 'desc';

            $query->orderBy($orderBy, $orderDirection);

            // Just get all records - no pagination!
            $periods = $query->get();

            return response()->json([
                'success' => true,
                'data' => PayPeriodResource::collection($periods)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pay periods: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();

            $period = PayPeriod::with(['createdBy:id,fname,lname'])
                ->where('store_id', $user->store_id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id'           => $period->id,
                    'name'         => $period->name,
                    'start_date'   => $period->start_date,
                    'end_date'     => $period->end_date,
                    'cutoff_date'  => $period->cutoff_date,
                    'pay_date'     => $period->pay_date ?? $period->cutoff_date,
                    'status'       => $period->status,
                    'notes'        => $period->notes,
                    'created_by'   => $period->createdBy
                        ? $period->createdBy->fname . ' ' . $period->createdBy->lname
                        : 'System',
                    'created_at'   => $period->created_at,
                    'updated_at'   => $period->updated_at,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pay period not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pay period: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Check if user has store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'cutoff_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
                'notes' => 'nullable|string',
            ]);

            // Check for overlapping periods (only for this store)
            $overlapping = PayPeriod::where('store_id', $user->store_id)
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q2) use ($validated) {
                            $q2->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                        });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pay period overlaps with existing period for this store'
                ], 409);
            }

            $period = PayPeriod::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'cutoff_date' => $validated['cutoff_date'],
                'notes' => $validated['notes'] ?? null,
                'store_id' => $user->store_id,
                'created_by' => $user->id,
                'status' => 'draft',
            ]);

            // Load relationships for response
            $period->load(['store:id,name', 'createdBy:id,fname,lname']);

            return response()->json([
                'success' => true,
                'message' => 'Pay period created successfully',
                'data' => $period
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                // 'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pay period: ' . $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $period = PayPeriod::findOrFail($id);

            // Cannot update locked or completed periods
            if (in_array($period->status, ['locked', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update locked or completed pay period'
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:draft,processing,locked,completed',
            ]);

            $period->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pay period updated successfully',
                'data' => $period
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pay period not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pay period: ' . $e->getMessage()
            ], 500);
        }
    }

    public function close($id)
    {
        try {
            $period = PayPeriod::findOrFail($id);

            if ($period->status !== 'processing') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only processing periods can be closed'
                ], 400);
            }

            $period->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'message' => 'Pay period closed successfully',
                'data' => $period
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pay period not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close pay period: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $period = PayPeriod::findOrFail($id);

            // Cannot delete if has payrolls
            if ($period->payrolls()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete pay period with payroll records'
                ], 400);
            }

            $period->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pay period deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pay period not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pay period: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payroll details for a specific period
     */
    public function getPayrollPerPeriod(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Check store access
            if (!$user->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any store'
                ], 403);
            }

            // Load the pay period with createdBy relationship
            $payPeriod = PayPeriod::with(['createdBy:id,fname,lname'])
                ->whereHas('payrolls', function ($query) use ($user) {
                    $query->whereHas('employee', function ($q) use ($user) {
                        $q->where('store_id', $user->store_id);
                    });
                })
                ->findOrFail($id);

            // Get payrolls for this period
            $payrolls = Payroll::where('pay_period_id', $payPeriod->id)
                ->whereHas('employee', function ($query) use ($user) {
                    $query->where('store_id', $user->store_id);
                })
                ->with(['employee:id,fname,lname,employee_number,department'])
                ->get();

            // Calculate period statistics
            $statistics = $this->calculatePeriodStatistics($payrolls, $payPeriod);

            // Get employee list with their payroll details
            $employees = $payrolls->map(function ($payroll) {
                return [
                    'id' => $payroll->employee->id,
                    'name' => $payroll->employee->fname . ' ' . $payroll->employee->lname,
                    'employee_number' => $payroll->employee->employee_number,
                    'department' => $payroll->employee->department,
                    'payroll' => [
                        'id' => $payroll->id,
                        'base_salary' => (float) $payroll->base_salary,
                        'overtime_amount' => (float) $payroll->overtime_amount,
                        'deductions_total' => (float) $payroll->deductions_total,
                        'bonuses_total' => (float) $payroll->bonuses_total,
                        'allowances_total' => (float) $payroll->allowances_total,
                        'tax_amount' => (float) $payroll->tax_amount,
                        'net_salary' => (float) $payroll->net_salary,
                        'status' => $payroll->status,
                        'formatted' => [
                            'net_salary' => '₱' . number_format($payroll->net_salary, 2),
                            'gross_pay' => '₱' . number_format(
                                $payroll->base_salary +
                                    $payroll->overtime_amount +
                                    $payroll->bonuses_total +
                                    $payroll->allowances_total,
                                2
                            )
                        ]
                    ]
                ];
            });

            // Prepare the response
            $response = [
                'success' => true,
                'data' => [
                    'period' => [
                        'id' => $payPeriod->id,
                        'name' => $payPeriod->name,
                        'start_date' => $payPeriod->start_date,
                        'end_date' => $payPeriod->end_date,
                        'cutoff_date' => $payPeriod->cutoff_date,
                        'pay_date' => $payPeriod->pay_date ?? $payPeriod->cutoff_date,
                        'status' => $payPeriod->status,
                        'notes' => $payPeriod->notes,
                        'created_by' => $payPeriod->createdBy ?
                            $payPeriod->createdBy->fname . ' ' . $payPeriod->createdBy->lname : 'System',
                        'created_at' => $payPeriod->created_at,
                        'updated_at' => $payPeriod->updated_at,
                        'formatted' => [
                            'start_date_formatted' => Carbon::parse($payPeriod->start_date)->format('M d, Y'),
                            'end_date_formatted' => Carbon::parse($payPeriod->end_date)->format('M d, Y'),
                            'pay_date_formatted' => $payPeriod->pay_date ?
                                Carbon::parse($payPeriod->pay_date)->format('M d, Y') :
                                Carbon::parse($payPeriod->cutoff_date)->format('M d, Y'),
                            'created_at_formatted' => $payPeriod->created_at ?
                                Carbon::parse($payPeriod->created_at)->format('M d, Y h:i A') : null,
                            'status_badge' => $this->getStatusBadge($payPeriod->status)
                        ]
                    ],
                    'statistics' => $statistics,
                    'employees' => $employees,
                    'recent_activity' => $this->getRecentActivity($payPeriod)
                ]
            ];

            // Add breakdown by department if requested
            if ($request->has('include_department_breakdown')) {
                $response['data']['department_breakdown'] = $this->getDepartmentBreakdown($payrolls);
            }

            // Add breakdown by status if requested
            if ($request->has('include_status_breakdown')) {
                $response['data']['status_breakdown'] = $this->getStatusBreakdown($payrolls);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payroll period details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get all pay periods with summary
     */
    public function getAllPayPeriods(Request $request)
    {
        try {
            $user = Auth::user();

            $query = PayPeriod::with(['createdBy:id,fname,lname'])
                ->whereHas('payrolls.employee', function ($q) use ($user) {
                    $q->where('store_id', $user->store_id);
                })
                ->withCount(['payrolls as employees_count' => function ($q) use ($user) {
                    $q->whereHas('employee', function ($query) use ($user) {
                        $query->where('store_id', $user->store_id);
                    });
                }]);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('year')) {
                $query->whereYear('start_date', $request->year);
            }

            if ($request->has('month')) {
                $query->whereMonth('start_date', $request->month);
            }

            // Get periods with payroll totals
            $payPeriods = $query->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($period) use ($user) {
                    // Calculate total net worth for this period
                    $totalNetWorth = Payroll::where('pay_period_id', $period->id)
                        ->whereHas('employee', function ($q) use ($user) {
                            $q->where('store_id', $user->store_id);
                        })
                        ->sum('net_salary');

                    $totalGrossWorth = Payroll::where('pay_period_id', $period->id)
                        ->whereHas('employee', function ($q) use ($user) {
                            $q->where('store_id', $user->store_id);
                        })
                        ->select(DB::raw('SUM(base_salary + overtime_amount + bonuses_total + allowances_total) as total'))
                        ->first()
                        ->total ?? 0;

                    return [
                        'id' => $period->id,
                        'name' => $period->name,
                        'start_date' => $period->start_date,
                        'end_date' => $period->end_date,
                        'pay_date' => $period->pay_date ?? $period->cutoff_date,
                        'status' => $period->status,
                        'employees_count' => $period->employees_count,
                        'total_net_worth' => (float) $totalNetWorth,
                        'total_gross_worth' => (float) $totalGrossWorth,
                        'created_by' => $period->createdBy ?
                            $period->createdBy->fname . ' ' . $period->createdBy->lname : 'System',
                        'created_at' => $period->created_at,
                        'formatted' => [
                            'period_range' => Carbon::parse($period->start_date)->format('M d') . ' - ' .
                                Carbon::parse($period->end_date)->format('M d, Y'),
                            'total_net_worth_formatted' => '₱' . number_format($totalNetWorth, 2),
                            'total_gross_worth_formatted' => '₱' . number_format($totalGrossWorth, 2),
                            'status_badge' => $this->getStatusBadge($period->status),
                            'created_at_formatted' => $period->created_at ?
                                Carbon::parse($period->created_at)->format('M d, Y') : null
                        ]
                    ];
                });

            // Calculate overall statistics
            $overallStats = [
                'total_periods' => $payPeriods->count(),
                'total_payroll_processed' => $payPeriods->sum('total_net_worth'),
                'average_period_payroll' => $payPeriods->avg('total_net_worth'),
                'periods_by_status' => $payPeriods->groupBy('status')
                    ->map(function ($group) {
                        return [
                            'count' => $group->count(),
                            'total' => $group->sum('total_net_worth')
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $payPeriods,
                'statistics' => $overallStats,
                'filters' => [
                    'status' => $request->status,
                    'year' => $request->year,
                    'month' => $request->month
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pay periods',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Calculate period statistics
     */
    private function calculatePeriodStatistics($payrolls, $payPeriod)
    {
        $totalGross = $payrolls->sum(function ($payroll) {
            return $payroll->base_salary +
                $payroll->overtime_amount +
                $payroll->bonuses_total +
                $payroll->allowances_total;
        });

        $totalDeductions = $payrolls->sum(function ($payroll) {
            return $payroll->deductions_total + $payroll->tax_amount;
        });

        $totalNet = $payrolls->sum('net_salary');
        $totalOvertime = $payrolls->sum('overtime_amount');
        $totalBonuses = $payrolls->sum('bonuses_total');
        $totalAllowances = $payrolls->sum('allowances_total');
        $totalTax = $payrolls->sum('tax_amount');

        // Calculate average per employee
        $employeeCount = $payrolls->count();
        $averageNet = $employeeCount > 0 ? $totalNet / $employeeCount : 0;

        // Count by status
        $statusCounts = [
            'draft' => $payrolls->where('status', 'draft')->count(),
            'calculated' => $payrolls->where('status', 'calculated')->count(),
            'approved' => $payrolls->where('status', 'approved')->count(),
            'paid' => $payrolls->where('status', 'paid')->count(),
            'cancelled' => $payrolls->where('status', 'cancelled')->count()
        ];

        // Processing progress
        $processedCount = $payrolls->whereIn('status', ['approved', 'paid'])->count();
        $progressPercentage = $employeeCount > 0 ?
            round(($processedCount / $employeeCount) * 100, 2) : 0;

        return [
            'total_employees' => $employeeCount,
            'total_gross_pay' => round($totalGross, 2),
            'total_deductions' => round($totalDeductions, 2),
            'total_net_pay' => round($totalNet, 2),
            'average_net_per_employee' => round($averageNet, 2),
            'total_overtime' => round($totalOvertime, 2),
            'total_bonuses' => round($totalBonuses, 2),
            'total_allowances' => round($totalAllowances, 2),
            'total_tax' => round($totalTax, 2),
            'status_breakdown' => $statusCounts,
            'processing_progress' => [
                'processed' => $processedCount,
                'total' => $employeeCount,
                'percentage' => $progressPercentage,
                'remaining' => $employeeCount - $processedCount
            ],
            'formatted' => [
                'total_gross_formatted' => '₱' . number_format($totalGross, 2),
                'total_net_formatted' => '₱' . number_format($totalNet, 2),
                'average_net_formatted' => '₱' . number_format($averageNet, 2),
                'total_deductions_formatted' => '₱' . number_format($totalDeductions, 2)
            ]
        ];
    }

    /**
     * Get department breakdown
     */
    private function getDepartmentBreakdown($payrolls)
    {
        return $payrolls->groupBy('employee.department')
            ->map(function ($deptPayrolls, $department) {
                $totalNet = $deptPayrolls->sum('net_salary');
                $employeeCount = $deptPayrolls->count();

                return [
                    'department' => $department ?: 'Unassigned',
                    'employee_count' => $employeeCount,
                    'total_net_pay' => round($totalNet, 2),
                    'average_per_employee' => $employeeCount > 0 ?
                        round($totalNet / $employeeCount, 2) : 0,
                    'percentage_of_total' => $payrolls->sum('net_salary') > 0 ?
                        round(($totalNet / $payrolls->sum('net_salary')) * 100, 2) : 0,
                    'formatted' => [
                        'total_net_formatted' => '₱' . number_format($totalNet, 2)
                    ]
                ];
            })->values();
    }

    /**
     * Get status breakdown
     */
    private function getStatusBreakdown($payrolls)
    {
        return [
            'draft' => [
                'count' => $payrolls->where('status', 'draft')->count(),
                'total' => round($payrolls->where('status', 'draft')->sum('net_salary'), 2)
            ],
            'calculated' => [
                'count' => $payrolls->where('status', 'calculated')->count(),
                'total' => round($payrolls->where('status', 'calculated')->sum('net_salary'), 2)
            ],
            'approved' => [
                'count' => $payrolls->where('status', 'approved')->count(),
                'total' => round($payrolls->where('status', 'approved')->sum('net_salary'), 2)
            ],
            'paid' => [
                'count' => $payrolls->where('status', 'paid')->count(),
                'total' => round($payrolls->where('status', 'paid')->sum('net_salary'), 2)
            ],
            'cancelled' => [
                'count' => $payrolls->where('status', 'cancelled')->count(),
                'total' => round($payrolls->where('status', 'cancelled')->sum('net_salary'), 2)
            ]
        ];
    }

    /**
     * Get recent activity for the period
     */
    private function getRecentActivity($payPeriod)
    {
        $activities = [];

        // Latest payroll updates
        $recentPayrolls = Payroll::where('pay_period_id', $payPeriod->id)
            ->with('employee:id,fname,lname')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentPayrolls as $payroll) {
            $activities[] = [
                'type' => 'payroll_update',
                'description' => $payroll->employee->fname . ' ' . $payroll->employee->lname .
                    ' payroll ' . $payroll->status,
                'user' => 'System',
                'timestamp' => $payroll->updated_at,
                'formatted' => [
                    'time_ago' => Carbon::parse($payroll->updated_at)->diffForHumans()
                ]
            ];
        }

        // Period status changes
        if ($payPeriod->created_at) {
            $activities[] = [
                'type' => 'period_created',
                'description' => 'Pay period created',
                'user' => $payPeriod->createdBy ?
                    $payPeriod->createdBy->fname . ' ' . $payPeriod->createdBy->lname : 'System',
                'timestamp' => $payPeriod->created_at,
                'formatted' => [
                    'time_ago' => Carbon::parse($payPeriod->created_at)->diffForHumans()
                ]
            ];
        }

        // Sort by timestamp descending
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get status badge color
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'draft' => 'secondary',
            'processing' => 'warning',
            'locked' => 'info',
            'completed' => 'success'
        ];

        return $badges[strtolower($status)] ?? 'secondary';
    }

    /**
     * Export period payroll summary
     */
    public function exportPayrollPeriod(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $payPeriod = PayPeriod::findOrFail($id);

            $payrolls = Payroll::where('pay_period_id', $payPeriod->id)
                ->whereHas('employee', function ($q) use ($user) {
                    $q->where('store_id', $user->store_id);
                })
                ->with('employee')
                ->get();

            // Generate CSV
            $filename = 'payroll_' . str_replace(' ', '_', $payPeriod->name) . '_' . now()->format('Ymd') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($payrolls, $payPeriod) {
                $file = fopen('php://output', 'w');

                // Add headers
                fputcsv($file, [
                    'Pay Period:',
                    $payPeriod->name,
                    'Date Range:',
                    Carbon::parse($payPeriod->start_date)->format('M d, Y') . ' - ' . Carbon::parse($payPeriod->end_date)->format('M d, Y'),
                    'Pay Date:',
                    $payPeriod->pay_date ? Carbon::parse($payPeriod->pay_date)->format('M d, Y') : 'N/A'
                ]);

                fputcsv($file, []); // Empty line

                // Column headers
                fputcsv($file, [
                    'Employee #',
                    'Employee Name',
                    'Department',
                    'Base Salary',
                    'Overtime',
                    'Bonuses',
                    'Allowances',
                    'Gross Pay',
                    'Deductions',
                    'Tax',
                    'Net Pay',
                    'Status'
                ]);

                // Add data rows
                foreach ($payrolls as $payroll) {
                    $grossPay = $payroll->base_salary +
                        $payroll->overtime_amount +
                        $payroll->bonuses_total +
                        $payroll->allowances_total;

                    fputcsv($file, [
                        $payroll->employee->employee_number,
                        $payroll->employee->fname . ' ' . $payroll->employee->lname,
                        $payroll->employee->department,
                        number_format($payroll->base_salary, 2),
                        number_format($payroll->overtime_amount, 2),
                        number_format($payroll->bonuses_total, 2),
                        number_format($payroll->allowances_total, 2),
                        number_format($grossPay, 2),
                        number_format($payroll->deductions_total, 2),
                        number_format($payroll->tax_amount, 2),
                        number_format($payroll->net_salary, 2),
                        $payroll->status
                    ]);
                }

                // Add summary
                fputcsv($file, []); // Empty line
                fputcsv($file, ['SUMMARY']);
                fputcsv($file, ['Total Employees:', $payrolls->count()]);
                fputcsv($file, ['Total Gross Pay:', number_format($payrolls->sum('base_salary') +
                    $payrolls->sum('overtime_amount') +
                    $payrolls->sum('bonuses_total') +
                    $payrolls->sum('allowances_total'), 2)]);
                fputcsv($file, ['Total Deductions:', number_format($payrolls->sum('deductions_total') +
                    $payrolls->sum('tax_amount'), 2)]);
                fputcsv($file, ['Total Net Pay:', number_format($payrolls->sum('net_salary'), 2)]);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export payroll period'
            ], 500);
        }
    }

   
}
