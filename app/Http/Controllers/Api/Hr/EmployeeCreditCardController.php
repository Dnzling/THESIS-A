<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeCreditCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployeeCreditCardController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $user = Auth::user();

        if ($user?->store_id && (int) $user->store_id !== (int) $employee->store_id) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not belong to your store',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'card_number' => 'nullable|string|max:50',
            'card_type' => 'nullable|string|max:50',
            'expiration_month' => 'nullable|digits:2',
            'expiration_year' => 'nullable|digits:4',
            'security_code' => 'nullable|string|max:10',
            'status' => 'nullable|in:active,inactive,pending',
            'metadata' => 'nullable|array',
            'assigned_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $cardType = $request->card_type ?? 'payroll';
        $existingCard = EmployeeCreditCard::where('employee_id', $employee->id)
            ->where('card_type', $cardType)
            ->first();

        $cardNumber = $request->card_number;
        if (!$cardNumber) {
            if ($existingCard?->card_number) {
                $cardNumber = $existingCard->card_number;
            } else {
                $cardNumber = $this->generateUniqueCardNumber($employee->id);
            }
        }

        $duplicateCard = EmployeeCreditCard::where('card_number', $cardNumber)
            ->when($existingCard?->id, fn ($query) => $query->where('id', '!=', $existingCard->id))
            ->exists();

        if ($duplicateCard) {
            return response()->json([
                'success' => false,
                'message' => 'Card number is already assigned to another employee.',
            ], 422);
        }

        $card = EmployeeCreditCard::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'card_type' => $cardType,
            ],
            [
                'card_number' => $cardNumber,
                'expiration_month' => $request->expiration_month,
                'expiration_year' => $request->expiration_year,
                'security_code' => $request->security_code,
                'status' => $request->status ?? 'active',
                'metadata' => $request->metadata ?? [],
                'assigned_at' => $request->assigned_at ?? now(),
                'created_by' => $existingCard?->created_by ?? $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        $this->clearEmployeeDetailsCache($employee->id);

        return response()->json([
            'success' => true,
            'message' => $existingCard ? 'Credit card updated successfully' : 'Credit card assigned successfully',
            'data' => $card,
        ], $existingCard ? 200 : 201);
    }

    private function clearEmployeeDetailsCache(int $employeeId): void
    {
        $today = now()->format('Y-m-d');
        $year = now()->year;

        Cache::forget("employee_details_{$employeeId}_{$year}_{$today}");
        Cache::forget("employee_details_{$employeeId}__{$today}");
    }

    private function generateUniqueCardNumber(int $employeeId): string
    {
        do {
            $candidate = strtoupper('PAY-' . $employeeId . '-' . now()->format('YmdHis') . '-' . Str::random(4));
        } while (EmployeeCreditCard::where('card_number', $candidate)->exists());

        return $candidate;
    }
}
