<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
  protected $model = User::class;

  public function definition(): array
  {
    $gender = fake()->randomElement(['male', 'female']);
    $employmentTypes = ['full-time', 'part-time', 'contract'];
    $employmentStatus = ['active', 'probation', 'terminated', 'resigned'];

    return [
      'name' => fake('en_MW')->name($gender),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => bcrypt('password'),
      'remember_token' => Str::random(10),
      'gender' => $gender,
      'position' => fake()->jobTitle(),
      'employee_id' => 'EMP' . fake()->unique()->numberBetween(1000, 9999),
      'join_date' => fake()->dateTimeBetween('-5 years', 'now'),
      'work_phone' => fake()->phoneNumber(),
      'office_location' => fake()->city(),
      'employment_type' => fake()->randomElement($employmentTypes),
      'employment_status' => fake()->randomElement($employmentStatus),
    ];
  }

  public function unverified(): static
  {
    return $this->state(fn(array $attributes) => [
      'email_verified_at' => null,
    ]);
  }

  public function active(): static
  {
    return $this->state(fn(array $attributes) => [
      'employment_status' => 'active'
    ]);
  }

  public function manager(): static
  {
    return $this->state(fn(array $attributes) => [
      'position' => 'Department Manager',
      'employment_status' => 'active',
      'employment_type' => 'full-time'
    ]);
  }
}
