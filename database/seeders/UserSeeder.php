<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
  public function run(): void
  {
    // Create admin user
    $admin = User::factory()->create([
      'name' => 'System Admin',
      'email' => 'admin@example.com',
      'position' => 'System Administrator',
      'employment_status' => 'active',
      'employment_type' => 'full-time',
      'department' => null
    ]);
    $admin->assignRole('Admin');

    // Create HR Manager
    $hrDepartment = Department::where('code', 'HR')->first();
    $hrManager = User::factory()->create([
      'name' => 'HR Manager',
      'email' => 'hr@example.com',
      'department' => $hrDepartment->id,
      'position' => 'HR Manager',
      'employment_status' => 'active',
      'employment_type' => 'full-time',
      'reporting_to' => $admin->id
    ]);
    $hrManager->assignRole('HR');
    $hrDepartment->manager_id = $hrManager->id;
    $hrDepartment->save();

    // Create department managers and employees
    Department::where('code', '!=', 'HR')->each(function ($department) use ($admin) {
      // Create department manager
      $manager = User::factory()->create([
        'department' => $department->id,
        'position' => 'Department Manager',
        'employment_status' => 'active',
        'employment_type' => 'full-time',
        'reporting_to' => $admin->id
      ]);
      $manager->assignRole('Manager');

      // Update department with manager
      $department->manager_id = $manager->id;
      $department->save();

      // Create employees for the department
      User::factory()
        ->count(3) // Reduced count to prevent performance issues
        ->active()
        ->create([
          'department' => $department->id,
          'reporting_to' => $manager->id,
          'employment_type' => 'full-time'
        ])
        ->each(fn($user) => $user->assignRole('Employee'));
    });
  }
}
