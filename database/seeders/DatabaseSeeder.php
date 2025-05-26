<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      RolesAndPermissionsSeeder::class,
      LeaveTypeSeeder::class,
    ]);

    $user = User::factory()->create([
      'name' => 'Employee User',
      'email' => 'employee@example.com',
    ]);

    $user->assignRole('Employee');

    $user = User::factory()->create([
      'name' => 'Manager User',
      'email' => 'manager@example.com',
    ]);

    $user->assignRole('Manager');
  }
}
