<?php

use App\Http\Controllers\Api\Core\SystemNotificationController;
use App\Models\Core\User;
use App\Services\Core\PermissionService;

uses(Tests\TestCase::class);

it('includes manage permission when filtering notifications for a view-only destination', function () {
    $user = new User(['store_id' => 1]);
    $user->id = 42;

    $permissions = Mockery::mock(PermissionService::class);
    $permissions->shouldReceive('getUserPermissions')->once()->with($user)
        ->andReturn(['sales.ecommerce-orders.manage']);
    app()->instance(PermissionService::class, $permissions);

    $query = (new ReflectionMethod(SystemNotificationController::class, 'visibleNotificationsFor'))
        ->invoke(new SystemNotificationController(), $user);

    expect($query->getBindings())->toContain(42, 'sales.ecommerce-orders.view');
    expect($query->toSql())->toContain('required_permission');
});
