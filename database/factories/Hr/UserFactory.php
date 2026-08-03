<?php

namespace Database\Factories\Hr;

use App\Models\Core\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Core\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\Core\User>
     */
    protected $model = \App\Models\Core\User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roleId = Role::query()->where('name', 'employee')->value('id')
            ?? Role::query()->firstOrCreate(
                ['name' => 'employee'],
                [
                    'display_name' => 'Employee',
                    'code' => 'EMPLOYEE',
                    'description' => 'Default employee role for factory-generated users.',
                    'is_active' => true,
                ]
            )->id;

        return [
            'fname' => $this->faker->firstName(),
            'lname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'), // Default password
            'remember_token' => Str::random(10),
            'role_id' => $roleId,
            'store_id' => null,
            'branch_id' => null,
            'is_active' => true,
            'otp_code' => $this->faker->randomElement([null, Str::random(6)]),
            'otp_expires_at' => $this->faker->randomElement([null, now()->addMinutes(10)]),
            'registered_by' => $this->faker->randomElement([null, 1, 2, 3]),
            'last_login_at' => $this->faker->randomElement([null, now()->subDays($this->faker->numberBetween(1, 30))]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user is a store admin.
     */
    public function storeAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->where('name', 'store_admin')->value('id')
                ?? Role::query()->firstOrCreate(
                    ['name' => 'store_admin'],
                    [
                        'display_name' => 'Store Admin',
                        'code' => 'STORE_ADMIN',
                        'description' => 'Store administrator role for factory-generated users.',
                        'is_active' => true,
                    ]
                )->id,
        ]);
    }

    /**
     * Indicate that the user is an HR manager.
     */
    public function hrManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->where('name', 'hr_manager')->value('id')
                ?? Role::query()->firstOrCreate(
                    ['name' => 'hr_manager'],
                    [
                        'display_name' => 'HR Manager',
                        'code' => 'HR_MANAGER',
                        'description' => 'HR manager role for factory-generated users.',
                        'is_active' => true,
                    ]
                )->id,
        ]);
    }

    /**
     * Indicate that the user is a regular employee.
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->where('name', 'employee')->value('id')
                ?? Role::query()->firstOrCreate(
                    ['name' => 'employee'],
                    [
                        'display_name' => 'Employee',
                        'code' => 'EMPLOYEE',
                        'description' => 'Default employee role for factory-generated users.',
                        'is_active' => true,
                    ]
                )->id,
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user's email is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has OTP.
     */
    public function withOtp(): static
    {
        return $this->state(fn (array $attributes) => [
            'otp_code' => Str::random(6),
            'otp_expires_at' => now()->addMinutes(10),
        ]);
    }
}
