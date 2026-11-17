<?php

use App\Http\Controllers\Api\Hr\PayrollController;
use App\Models\Hr\DeductionType;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeDeduction;
use App\Models\Hr\PayPeriod;
use App\Models\Store\Store;

it('uses the store late deduction policy and preserves the previous default', function () {
    $controller = new PayrollController();
    $policy = new ReflectionMethod($controller, 'lateDeductionPolicy');
    $employee = new Employee();

    $employee->setRelation('store', new Store(['settings' => []]));
    expect($policy->invoke($controller, $employee))->toBe(['enabled' => true, 'rate' => 50.0]);

    $employee->setRelation('store', new Store(['settings' => [
        'hr_payroll_configuration' => ['lateDeductionEnabled' => false, 'lateDeductionRate' => 75],
    ]]));
    expect($policy->invoke($controller, $employee))->toBe(['enabled' => false, 'rate' => 75.0]);
});

it('does not add the legacy fixed late deduction to payroll', function () {
    $controller = new PayrollController();
    $calculate = new ReflectionMethod($controller, 'calculateDeductionAmountForPeriod');
    $deduction = new EmployeeDeduction();
    $deduction->setRelation('deductionType', new DeductionType(['code' => 'LATE']));

    expect($calculate->invoke($controller, $deduction, 1000.0, 1000.0, new PayPeriod(), 1))->toBe(0.0);
});
