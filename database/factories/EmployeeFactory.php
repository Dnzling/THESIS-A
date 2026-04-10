<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hr\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hireDate = $this->faker->dateTimeBetween('-5 years', 'now');
        $dateOfBirth = $this->faker->dateTimeBetween('-60 years', '-20 years');
        
        return [
            'user_id' => null, // Will be set in seeder
            'store_id' => $this->faker->numberBetween(1, 5),
            'employee_number' => 'EMP' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'date_of_birth' => $dateOfBirth->format('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'hire_date' => $hireDate->format('Y-m-d'),
            'department' => $this->faker->randomElement([
                'Sales', 'Marketing', 'IT', 'Human Resources', 
                'Finance', 'Operations', 'Customer Service', 
                'Logistics', 'Administration', 'Production'
            ]),
            'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract', 'intern']),
            'salary' => $this->faker->randomFloat(2, 30000, 150000),
            'bank_account' => $this->faker->bankAccountNumber,
            'tax_id' => 'TAX' . $this->faker->unique()->numberBetween(100000, 999999),
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_phone' => $this->faker->phoneNumber,
            'emergency_contact_relationship' => $this->faker->randomElement([
                'Spouse', 'Parent', 'Sibling', 'Friend', 'Relative'
            ]),
            'id_document_path' => $this->faker->optional()->filePath(),
            'contract_path' => $this->faker->optional()->filePath(),
            'status' => $this->faker->randomElement(['active', 'on_leave', 'suspended', 'terminated']),
            'termination_date' => $this->faker->optional(0.1)->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'termination_reason' => $this->faker->optional(0.1)->text(200),
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the employee is on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_leave',
        ]);
    }

    /**
     * Indicate that the employee is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        $terminationDate = $this->faker->dateTimeBetween('-1 year', 'now');
        
        return $this->state(fn (array $attributes) => [
            'status' => 'terminated',
            'termination_date' => $terminationDate->format('Y-m-d'),
            'termination_reason' => $this->faker->randomElement([
                'Resigned', 'Contract ended', 'Dismissed', 
                'Mutual agreement', 'Retirement'
            ]),
        ]);
    }

    /**
     * Indicate that the employee is full-time.
     */
    public function fullTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'full_time',
            'salary' => $this->faker->randomFloat(2, 50000, 150000),
        ]);
    }

    /**
     * Indicate that the employee is part-time.
     */
    public function partTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'part_time',
            'salary' => $this->faker->randomFloat(2, 20000, 50000),
        ]);
    }

    /**
     * Indicate that the employee is a contract worker.
     */
    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'contract',
            'salary' => $this->faker->randomFloat(2, 40000, 100000),
        ]);
    }

    /**
     * Indicate that the employee is an intern.
     */
    public function intern(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_type' => 'intern',
            'salary' => $this->faker->randomFloat(2, 10000, 25000),
        ]);
    }

    /**
     * Indicate a specific department.
     */
    public function department(string $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => $department,
        ]);
    }

    /**
     * Indicate a specific store.
     */
    public function forStore(int $storeId): static
    {
        return $this->state(fn (array $attributes) => [
            'store_id' => $storeId,
        ]);
    }

    /**
     * Indicate a specific salary range.
     */
    public function salaryRange(float $min, float $max): static
    {
        return $this->state(fn (array $attributes) => [
            'salary' => $this->faker->randomFloat(2, $min, $max),
        ]);
    }
}