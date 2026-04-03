<?php

namespace App\Support;

use App\Models\Hr\Employee;
use Illuminate\Support\Facades\Auth;

class EmployeeContext
{
    /**
     * Resolve current actor to employee id, with user-id fallback for legacy rows.
     */
    public static function currentEmployeeId(?int $userId = null): ?int
    {
        $resolvedUserId = $userId ?: Auth::id();
        if (!$resolvedUserId) {
            return null;
        }

        $employeeId = Employee::query()
            ->where('user_id', $resolvedUserId)
            ->value('id');

        return $employeeId ? (int) $employeeId : (int) $resolvedUserId;
    }
}

