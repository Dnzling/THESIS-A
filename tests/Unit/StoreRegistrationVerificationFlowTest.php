<?php

use App\Models\Admin\SubscriptionPlan;
use App\Models\Core\Role;
use App\Models\Core\User;
use App\Models\Store\Store;
use App\Models\Store\StoreVerification;
use App\Mail\OtpVerificationMail;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class, DatabaseTransactions::class);

it('registers an owner and store, then verifies the store through super admin review without refreshing migrations', function () {
    $connection = config('database.default');
    $database = (string) config("database.connections.{$connection}.database");
    expect(app()->environment())->toBe('testing')
        ->and($database)->toMatch('/_test$/i');
    $this->assertTrue(
        Schema::hasTable('subscription_plans'),
        'The dedicated test database needs the subscription_plans table before the onboarding flow can run.'
    );

    Mail::fake();
    Storage::fake('public');

    $ownerRole = Role::query()->firstOrCreate(
        ['name' => 'owner', 'store_id' => null],
        [
            'display_name' => 'Store Owner',
            'code' => 'OWN',
            'description' => 'Store owner role for onboarding flow test.',
            'is_active' => true,
        ]
    );
    $superAdminRole = Role::query()->firstOrCreate(
        ['name' => 'super_admin', 'store_id' => null],
        [
            'display_name' => 'Super Admin',
            'code' => 'SUPER',
            'description' => 'Platform administrator role for onboarding flow test.',
            'is_active' => true,
        ]
    );

    SubscriptionPlan::query()->updateOrCreate(
        ['plan_key' => 'free'],
        [
            'name' => 'Free',
            'description' => 'Free plan for onboarding flow test.',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'features' => [],
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]
    );

    $email = 'onboarding-' . uniqid() . '@example.test';
    $password = 'StrongPassword1!';
    $registration = $this->postJson('/api/auth/register', [
        'fname' => 'Store',
        'lname' => 'Owner',
        'email' => $email,
        'password' => $password,
        'account_type' => 'owner',
    ]);
    $registration->assertCreated()->assertJsonPath('requires_verification', true);
    $temporaryToken = $registration->json('user.access_token');
    expect($temporaryToken)->toBeString()->not->toBeEmpty();

    $owner = User::query()->where('email', $email)->firstOrFail();
    expect($owner->role_id)->toBe($ownerRole->id)
        ->and($owner->email_verified_at)->toBeNull()
        ->and($owner->store_id)->toBeNull();

    $this->withToken($temporaryToken)
        ->postJson('/api/auth/send-otp')
        ->assertOk()
        ->assertJsonPath('success', true);
    Mail::assertSent(OtpVerificationMail::class);

    $otp = (string) $owner->fresh()->otp_code;
    expect($otp)->toHaveLength(6);
    $this->withToken($temporaryToken)
        ->postJson('/api/auth/verify-otp', ['otp' => $otp])
        ->assertOk()
        ->assertJsonPath('success', true);

    $owner->refresh();
    expect($owner->email_verified_at)->not->toBeNull()
        ->and($owner->otp_code)->toBeNull();

    $login = $this->postJson('/api/auth/login', [
        'login' => $email,
        'password' => $password,
    ]);
    $login->assertOk();
    $ownerToken = $login->json('data.access_token');
    expect($ownerToken)->toBeString()->not->toBeEmpty();
    Auth::guard('web')->logout();

    $this->withToken($ownerToken)
        ->postJson('/api/stores/register', [
            'store_name' => 'Onboarding Flow Furniture',
            'contact_person' => 'Store Owner',
            'contact_number' => '09171234567',
            'email' => $email,
            'business_type' => 'retail',
            'province' => 'Cavite',
            'city' => 'Imus',
            'barangay' => 'Bayan Luma',
            'address' => '100 Main Street',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $owner->refresh();
    $store = Store::query()->where('email', $email)->firstOrFail();
    expect($owner->store_id)->toBe($store->id)
        ->and($store->status)->toBe('active')
        ->and($store->verified_at)->toBeNull();

    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=', true);
    $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

    $this->withToken($ownerToken)
        ->postJson("/api/stores/{$store->id}/verification/submit", [
            'gov_id_type' => 'national_id',
            'gov_id_number' => 'TEST-123456789',
            'gov_id_front_file' => UploadedFile::fake()->createWithContent('owner-id-front.png', $pngContent),
            'business_registration_number' => 'REG-TEST-001',
            'tax_certificate_number' => 'TIN-123-456-789',
            'permit_number' => 'PERMIT-TEST-001',
            'business_registration_date' => now()->subYear()->toDateString(),
            'business_registration_file' => UploadedFile::fake()->createWithContent('business-registration.pdf', $pdfContent),
            'tax_certificate_file' => UploadedFile::fake()->createWithContent('tax-certificate.png', $pngContent),
            'business_permit_file' => UploadedFile::fake()->createWithContent('business-permit.png', $pngContent),
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $verification = StoreVerification::query()->where('store_id', $store->id)->firstOrFail();
    expect($verification->submitted_at)->not->toBeNull()
        ->and($verification->reviewed_at)->toBeNull();

    $this->withToken($ownerToken)
        ->postJson("/api/store-verification/{$verification->id}/review", ['action' => 'approve'])
        ->assertForbidden();

    $reviewer = User::factory()->create([
        'fname' => 'Platform',
        'lname' => 'Admin',
        'email' => 'reviewer-' . uniqid() . '@example.test',
        'role_id' => $superAdminRole->id,
        'is_active' => true,
        'registered_by' => null,
    ]);
    $this->actingAs($reviewer, 'sanctum')
        ->getJson('/api/pending-verification')
        ->assertOk()
        ->assertJsonFragment(['id' => $verification->id]);

    $this->actingAs($reviewer, 'sanctum')
        ->postJson("/api/store-verification/{$verification->id}/review", ['action' => 'approve'])
        ->assertOk()
        ->assertJsonPath('success', true);

    $store->refresh();
    $verification->refresh();
    expect($store->status)->toBe('active')
        ->and($store->verified_at)->not->toBeNull()
        ->and($store->verified_by)->toBe($reviewer->id)
        ->and($store->isVerified())->toBeTrue()
        ->and($verification->reviewed_at)->not->toBeNull()
        ->and($verification->reviewed_by)->toBe($reviewer->id)
        ->and($verification->rejection_reason)->toBeNull();

    $this->actingAs($owner, 'sanctum')
        ->getJson("/api/stores/{$store->id}/verification/status")
        ->assertOk()
        ->assertJsonPath('data.store_status', 'approved')
        ->assertJsonPath('data.raw_store_status', 'active')
        ->assertJsonPath('data.is_verified', true);
});
