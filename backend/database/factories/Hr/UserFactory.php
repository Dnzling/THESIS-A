<?php

namespace Database\Factories\Hr;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Core\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fname' => $this->faker->firstName(),
            'lname' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'), // Default password
            'remember_token' => Str::random(10),
            'role_id' => 4, // Default employee role - adjust as needed
            'store_id' => $this->faker->numberBetween(1, 5),
            'branch_id' => $this->faker->numberBetween(1, 3),
            'is_active' => $this->faker->randomElement(['active', 'inactive']),
            'otp_code' => $this->faker->randomElement([null, Str::random(6)]),
            'otp_expires_at' => $this->faker->randomElement([null, now()->addMinutes(10)]),
            'registered_by' => $this->faker->randomElement([null, 1, 2, 3]),
            'deleted_by' => null,
            'deleted_at' => null,
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
            'role_id' => 2, // Store admin role
        ]);
    }

    /**
     * Indicate that the user is an HR manager.
     */
    public function hrManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 3, // HR manager role
        ]);
    }

    /**
     * Indicate that the user is a regular employee.
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 4, // Employee role
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => 'active',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => 'inactive',
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