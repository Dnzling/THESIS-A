<?php

use App\Models\Hr\Shift;
use App\Services\Hr\AttendanceClockInService;
use Carbon\Carbon;

it('marks clock-ins within grace as present', function () {
    $service = new AttendanceClockInService();
    $shift = new Shift(['grace_period_minutes' => 10]);
    $resolved = ['is_rest_day' => false, 'shift' => $shift, 'start_time' => '09:00:00'];

    expect($service->calculateStatus($resolved, Carbon::parse('2026-09-25 09:10:00', 'Asia/Manila'), '2026-09-25', 'Asia/Manila'))
        ->toBe(['status' => 'present', 'late_minutes' => 0]);
});

it('marks clock-ins beyond grace as late using the store attendance setting', function () {
    $service = new AttendanceClockInService();
    $shift = new Shift(['grace_period_minutes' => 15]);
    $resolved = ['is_rest_day' => false, 'shift' => $shift, 'start_time' => '09:00:00'];

    expect($service->calculateStatus(
        $resolved,
        Carbon::parse('2026-09-25 09:18:00', 'Asia/Manila'),
        '2026-09-25',
        'Asia/Manila',
        ['gracePeriod' => 5],
    ))->toBe(['status' => 'late', 'late_minutes' => 13]);
});

it('flags clock-ins without a resolved shift for schedule review', function () {
    $service = new AttendanceClockInService();

    expect($service->calculateStatus(
        ['is_rest_day' => false, 'shift' => null, 'start_time' => null],
        Carbon::parse('2026-09-25 09:00:00', 'Asia/Manila'),
        '2026-09-25',
        'Asia/Manila',
    ))->toBe(['status' => 'unscheduled', 'late_minutes' => 0]);
});
