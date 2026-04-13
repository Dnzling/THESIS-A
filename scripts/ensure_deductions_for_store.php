<?php
// Run with: php scripts/ensure_deductions_for_store.php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\DeductionType;
use Carbon\Carbon;

$storeId = 1;
$employees = Employee::where('store_id', $storeId)->get();
if ($employees->isEmpty()) {
    echo "No employees found for store_id={$storeId}\n";
    exit(0);
}

// Get deduction types configured for store
$deductionTypes = DeductionType::where('store_id', $storeId)->get();
if ($deductionTypes->isEmpty()) {
    echo "No deduction types configured for store_id={$storeId}\n";
}

foreach ($employees as $employee) {
    echo "Employee {$employee->id}: {$employee->fname} {$employee->lname}\n";
    $active = EmployeeDeduction::where('employee_id', $employee->id)->active()->with('deductionType')->get();
    if ($active->isEmpty()) {
        echo "  No active deductions — creating defaults...\n";
        foreach ($deductionTypes as $dt) {
            EmployeeDeduction::create([
                'employee_id' => $employee->id,
                'deduction_type_id' => $dt->id,
                'amount' => $dt->default_amount ?? 0,
                'effective_date' => Carbon::now()->toDateString(),
                'end_date' => null,
                'is_active' => 1,
            ]);
            echo "    Added deduction: {$dt->name} amount: " . ($dt->default_amount ?? 0) . "\n";
        }
    } else {
        foreach ($active as $d) {
            echo "  Deduction: {$d->deductionType->name} amount: {$d->amount}\n";
        }
    }
}

echo "Done.\n";
