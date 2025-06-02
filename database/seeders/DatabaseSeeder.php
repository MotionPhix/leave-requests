<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      RolesAndPermissionsSeeder::class,
      DepartmentSeeder::class,
      UserSeeder::class,
      HolidaySeeder::class,
      LeaveTypeSeeder::class,
    ]);

    /*$gender = fake()->randomElement(['male', 'female']);

    $user = User::factory()->create([
      'name' => fake('MW')->name($gender),
      'email' => 'employee@example.com',
      'gender' => $gender,
    ]);

    $user->assignRole('Employee');

    $user = User::factory()->create([
      'name' => fake('MW')->name($gender),
      'email' => 'manager@example.com',
      'gender' => $gender,
    ]);

    $user->assignRole('Manager');*/
  }
}
