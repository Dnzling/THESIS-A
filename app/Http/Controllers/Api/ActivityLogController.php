<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\ActivityLog;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isStoreOwner = $user->hasRole('owner');
        $storeId = (int) ($user->store_id ?? 0);
        if (!$isSuperAdmin && $storeId <= 0) {
            $employee = Employee::query()->where('user_id', $user->id)->first(['store_id', 'branch_id']);
            $storeId = (int) ($employee?->store_id ?? 0);
            if ($storeId <= 0 && $employee?->branch_id) {
                $storeId = (int) Branch::query()->whereKey($employee->branch_id)->value('store_id');
            }
        }

        if (!$isSuperAdmin && !$isStoreOwner && !$user->hasPermissionTo('hr.activity-logs.view', $storeId ?: null)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$isSuperAdmin && $storeId <= 0) {
            return response()->json(['success' => false, 'message' => 'No store is assigned to this account.'], 422);
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'action' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = ActivityLog::with([
            'user:id,fname,lname,email',
            'store:id,name',
            'branch:id,name',
            'department:id,name',
        ]);

        if (!$isSuperAdmin) {
            $query->where('store_id', $storeId);
        } elseif (!empty($validated['store_id'])) {
            $query->where('store_id', (int) $validated['store_id']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        if (!empty($validated['action'])) {
            $query->where('action', $validated['action']);
        }

        if (!empty($validated['entity_type'])) {
            $query->where('entity_type', $validated['entity_type']);
        }

        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        if (!empty($validated['search'])) {
            $search = trim($validated['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('action', 'like', "%{$search}%")
                    ->orWhere('entity_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('store', fn ($storeQuery) => $storeQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $builder->orWhere('entity_id', (int) $search);
                }
            });
        }

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
