<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Annual Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave', 'Study Leave'
            ]),
            'description' => fake()->optional()->sentence(),
            'max_days_per_year' => fake()->numberBetween(5, 30),
            'requires_documentation' => fake()->boolean(30),
            'gender_specific' => false,
            'gender' => 'any',
            'minimum_notice_days' => fake()->numberBetween(0, 14),
            'allow_negative_balance' => false,
            'pay_percentage' => 100,
            'frequency_years' => 1,
        ];
    }
}
