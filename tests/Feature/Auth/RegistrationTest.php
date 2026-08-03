<?php

use App\Models\Core\User;
use App\Models\Store\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('store registration can complete free trial onboarding with real store details', function () {
    Mail::fake();

    $registerResponse = $this->postJson('/api/auth/register', [
        'fname' => 'Denz',
        'lname' => 'Declarado',
        'email' => 'denz+' . uniqid() . '@example.com',
        'password' => 'password123',
        'role_id' => 2,
        'birthday' => '1995-01-01',
    ]);

    $registerResponse->assertStatus(201);
    $registerResponse->assertJsonPath('success', true);

    $userId = $registerResponse->json('user.id')
        ?? User::query()->where('email', $registerResponse->json('user.email'))->value('id');

    expect($userId)->not->toBeNull();

    $user = User::query()->findOrFail($userId);
    $otp = (string) $user->otp_code;
    expect($otp)->toHaveLength(6);

    $verifyResponse = $this->withToken($registerResponse->json('user.access_token'))
        ->postJson('/api/auth/verify-otp', [
            'otp' => $otp,
        ]);

    $verifyResponse->assertOk();
    $verifyResponse->assertJsonPath('success', true);

    $token = $registerResponse->json('user.access_token');
    $user = $user->fresh();

    $onboardingResponse = $this->withToken($token)
        ->postJson('/api/auth/trial-onboarding', [
            'plan' => 'simple',
            'store_name' => 'Casa Verde Furniture',
            'store_type' => 'retail',
            'employee_range' => '1-5',
            'branch_range' => '1',
            'modules' => ['inventory', 'sales', 'procurement', 'finance', 'hr'],
            'primary_goal' => 'inventory-accuracy',
            'first_team' => 'owner',
        ]);

    $onboardingResponse->assertOk();
    $onboardingResponse->assertJsonPath('success', true);

    $store = Store::query()->where('name', 'Casa Verde Furniture')->first();
    expect($store)->not->toBeNull();
    expect($store?->subscription_tier)->toBe('free');
    expect($store?->type)->toBe('retail');
    expect($store?->trial_ends_at?->toDateString())->not->toBeNull();
    expect($store?->name)->not->toContain('Trial Store');
});
