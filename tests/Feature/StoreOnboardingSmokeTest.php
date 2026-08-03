<?php

use App\Models\Core\Role;
use App\Models\Admin\SubscriptionPlan;
use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->assertTrue(Schema::hasTable('subscription_plans'));
});

function createAuthUser(): User
{
    $storeAdminRole = Role::query()->firstOrCreate(
        ['name' => 'store_admin'],
        [
            'display_name' => 'Store Admin',
            'code' => 'STORE_ADMIN',
            'description' => 'Store admin role for smoke tests.',
            'is_active' => true,
        ]
    );

    return User::factory()->create([
        'fname' => 'Test',
        'lname' => 'Owner',
        'email' => 'owner+' . uniqid() . '@example.com',
        'role_id' => $storeAdminRole->id,
        'is_active' => true,
        'registered_by' => null,
    ]);
}

function createSubscriptionPlan(string $planKey, string $name, float $monthlyPrice = 0.0): SubscriptionPlan
{
    return SubscriptionPlan::query()->updateOrCreate(
        ['plan_key' => $planKey],
        [
            'name' => $name,
            'description' => $name . ' plan',
            'monthly_price' => $monthlyPrice,
            'yearly_price' => $monthlyPrice * 10,
            'features' => ['feature-a', 'feature-b'],
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]
    );
}

test('store registration smoke test can create a store then activate the free trial plan', function () {
    createSubscriptionPlan('simple', 'Simple', 0);

    $user = createAuthUser();
    $this->actingAs($user, 'sanctum');

    $registerResponse = $this->postJson('/api/stores/register', [
        'store_name' => 'Oak & Loft',
        'contact_person' => 'Test Owner',
        'business_type' => 'retail',
        'province' => 'Cavite',
        'city' => 'Dasmariñas',
        'address' => '123 Main St',
    ]);

    $registerResponse->assertCreated();
    $storeId = $registerResponse->json('store.store_id');
    expect($storeId)->toBeInt();

    $store = Store::query()->findOrFail($storeId);
    expect($store->subscription_tier)->toBe('free');

    $subscriptionResponse = $this->putJson("/api/stores/{$store->id}/subscription", [
        'subscription_tier' => 'simple',
        'setup_mode' => 'free',
    ]);

    $subscriptionResponse->assertOk();
    $subscriptionResponse->assertJsonPath('success', true);

    $store->refresh();
    expect($store->subscription_tier)->toBe('simple');
    expect($store->subscription_ends_at?->toDateString())->toBe(now()->addDays(7)->toDateString());
});

test('store registration smoke test can create a store then keep the selected paid plan', function () {
    createSubscriptionPlan('premium', 'Premium', 1999);

    $user = createAuthUser();
    $this->actingAs($user, 'sanctum');

    $registerResponse = $this->postJson('/api/stores/register', [
        'store_name' => 'Casa Verde',
        'contact_person' => 'Test Owner',
        'business_type' => 'enterprise',
        'province' => 'Cavite',
        'city' => 'Imus',
        'address' => '456 Commerce Ave',
    ]);

    $registerResponse->assertCreated();
    $storeId = $registerResponse->json('store.store_id');
    expect($storeId)->toBeInt();

    $subscriptionResponse = $this->putJson("/api/stores/{$storeId}/subscription", [
        'subscription_tier' => 'premium',
        'setup_mode' => 'paid',
    ]);

    $subscriptionResponse->assertOk();
    $subscriptionResponse->assertJsonPath('success', true);

    $store = Store::query()->findOrFail($storeId);
    expect($store->subscription_tier)->toBe('premium');
    expect($store->subscription_ends_at)->toBeNull();
});
