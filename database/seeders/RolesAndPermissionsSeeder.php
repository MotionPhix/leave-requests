<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
  public function run()
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Leave Management Permissions
    Permission::create(['name' => 'create leave']);
    Permission::create(['name' => 'approve leave']);
    Permission::create(['name' => 'reject leave']);
    Permission::create(['name' => 'view leave']);

    // User Management Permissions (Fine-grained)
    Permission::create(['name' => 'create user']);
    Permission::create(['name' => 'edit user']);
    Permission::create(['name' => 'delete user']);
    Permission::create(['name' => 'revoke user']);
    Permission::create(['name' => 'view user']);

    // Reports
    Permission::create(['name' => 'view reports']);

    // Roles setup
    $adminRole = Role::create(['name' => 'Admin']);
    $managerRole = Role::create(['name' => 'Manager']);
    $hrRole = Role::create(['name' => 'HR']);
    $employeeRole = Role::create(['name' => 'Employee']);

    // Assign permissions to roles
    $adminRole->givePermissionTo(Permission::all());

    $managerRole->givePermissionTo([
      'approve leave',
      'reject leave',
      'view leave',
      'view reports',
      'view user',
    ]);

    $hrRole->givePermissionTo([
      'approve leave',
      'reject leave',
      'view leave',
      'view user',
    ]);

    $employeeRole->givePermissionTo([
      'create leave',
      'view leave',
    ]);
  }
}
