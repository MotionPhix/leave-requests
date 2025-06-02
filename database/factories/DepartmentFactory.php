<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
  protected $model = Department::class;

  public function definition(): array
  {
    return [
      'name' => fake()->unique()->company() . ' Department',
      'code' => strtoupper(fake()->unique()->lexify('???')),
      'description' => fake()->sentence(),
      'is_active' => true,
    ];
  }

  /**
   * Configure the department as inactive
   */
  public function inactive(): static
  {
    return $this->state(fn(array $attributes) => [
      'is_active' => false,
    ]);
  }

  /**
   * Assign a manager to the department
   */
  public function withManager(): static
  {
    return $this->state(function (array $attributes) {
      return [
        'manager_id' => User::factory(),
      ];
    });
  }
}
