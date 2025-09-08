<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions grouped by functional areas
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();
    }

    private function createPermissions(): void
    {
        $permissionGroups = [
            // Workspace Management (Super Admin & Workspace Owner)
            'workspace' => [
                'workspace.view',
                'workspace.create',
                'workspace.edit',
                'workspace.delete',
                'workspace.manage-members',
                'workspace.invite-users',
                'workspace.remove-users',
            ],

            // User Management
            'users' => [
                'users.view',
                'users.view-any',
                'users.create',
                'users.edit',
                'users.edit-any',
                'users.delete',
                'users.activate',
                'users.deactivate',
                'users.assign-roles',
                'users.view-profile',
                'users.edit-profile',
            ],

            // Leave Requests Management
            'leave-requests' => [
                'leave-requests.view-own',
                'leave-requests.view-team',
                'leave-requests.view-any',
                'leave-requests.create',
                'leave-requests.edit-own',
                'leave-requests.edit-team',
                'leave-requests.delete-own',
                'leave-requests.cancel-own',
                'leave-requests.approve',
                'leave-requests.reject',
                'leave-requests.force-approve',
                'leave-requests.view-history',
            ],

            // Leave Types Management
            'leave-types' => [
                'leave-types.view',
                'leave-types.create',
                'leave-types.edit',
                'leave-types.delete',
                'leave-types.activate',
                'leave-types.deactivate',
            ],

            // Department Management
            'departments' => [
                'departments.view',
                'departments.create',
                'departments.edit',
                'departments.delete',
                'departments.assign-manager',
                'departments.view-members',
            ],

            // Holiday Management
            'holidays' => [
                'holidays.view',
                'holidays.create',
                'holidays.edit',
                'holidays.delete',
                'holidays.publish',
            ],

            // Reporting & Analytics
            'reports' => [
                'reports.view-own',
                'reports.view-team',
                'reports.view-department',
                'reports.view-company',
                'reports.export',
                'reports.analytics',
            ],

            // Role & Permission Management
            'roles' => [
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.assign',
            ],

            // Settings & Configuration
            'settings' => [
                'settings.view',
                'settings.edit',
                'settings.system',
                'settings.employee-id',
                'settings.notifications',
            ],

            // Notifications
            'notifications' => [
                'notifications.view-own',
                'notifications.manage-preferences',
                'notifications.send-custom',
            ],
        ];

        // Create all permissions
        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission]);
            }
        }
    }

    private function createRoles(): void
    {
        // Super Admin Role (System-wide access, not tenant-specific)
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Workspace Owner Role (Full access within their workspace)
        $workspaceOwnerRole = Role::firstOrCreate(['name' => 'Workspace Owner']);
        $workspaceOwnerRole->givePermissionTo([
            // Workspace management
            'workspace.view',
            'workspace.edit',
            'workspace.manage-members',
            'workspace.invite-users',
            'workspace.remove-users',
            
            // Full user management within workspace
            'users.view-any',
            'users.create',
            'users.edit-any',
            'users.delete',
            'users.activate',
            'users.deactivate',
            'users.assign-roles',
            
            // Full leave management
            'leave-requests.view-any',
            'leave-requests.approve',
            'leave-requests.reject',
            'leave-requests.force-approve',
            'leave-requests.view-history',
            
            // Leave types management
            'leave-types.view',
            'leave-types.create',
            'leave-types.edit',
            'leave-types.delete',
            'leave-types.activate',
            'leave-types.deactivate',
            
            // Department management
            'departments.view',
            'departments.create',
            'departments.edit',
            'departments.delete',
            'departments.assign-manager',
            'departments.view-members',
            
            // Holiday management
            'holidays.view',
            'holidays.create',
            'holidays.edit',
            'holidays.delete',
            'holidays.publish',
            
            // Full reporting
            'reports.view-company',
            'reports.export',
            'reports.analytics',
            
            // Role management (within workspace)
            'roles.view',
            'roles.assign',
            
            // Settings
            'settings.view',
            'settings.edit',
            'settings.employee-id',
            'settings.notifications',
            
            // Notifications
            'notifications.send-custom',
        ]);

        // HR Manager Role
        $hrRole = Role::firstOrCreate(['name' => 'HR Manager']);
        $hrRole->givePermissionTo([
            // User management (broad access)
            'users.view-any',
            'users.create',
            'users.edit-any',
            'users.activate',
            'users.deactivate',
            
            // Leave management (company-wide)
            'leave-requests.view-any',
            'leave-requests.approve',
            'leave-requests.reject',
            'leave-requests.view-history',
            
            // Leave types management
            'leave-types.view',
            'leave-types.create',
            'leave-types.edit',
            'leave-types.activate',
            'leave-types.deactivate',
            
            // Department oversight
            'departments.view',
            'departments.view-members',
            
            // Holiday management
            'holidays.view',
            'holidays.create',
            'holidays.edit',
            'holidays.publish',
            
            // Company-wide reporting
            'reports.view-company',
            'reports.export',
            'reports.analytics',
            
            // Settings
            'settings.view',
            'settings.edit',
            'settings.employee-id',
            
            // Notifications
            'notifications.send-custom',
        ]);

        // Department Manager Role
        $managerRole = Role::firstOrCreate(['name' => 'Department Manager']);
        $managerRole->givePermissionTo([
            // Team user management
            'users.view',
            'users.view-any',
            'users.edit',
            
            // Team leave management
            'leave-requests.view-team',
            'leave-requests.approve',
            'leave-requests.reject',
            
            // Basic leave types access
            'leave-types.view',
            
            // Department management
            'departments.view',
            'departments.view-members',
            
            // Holidays
            'holidays.view',
            
            // Department reporting
            'reports.view-team',
            'reports.view-department',
            'reports.export',
        ]);

        // Team Lead Role
        $teamLeadRole = Role::firstOrCreate(['name' => 'Team Lead']);
        $teamLeadRole->givePermissionTo([
            // Limited team oversight
            'users.view',
            'users.view-any',
            
            // Team leave oversight
            'leave-requests.view-team',
            'leave-requests.approve',
            
            // Basic access
            'leave-types.view',
            'holidays.view',
            
            // Team reporting
            'reports.view-team',
        ]);

        // Employee Role
        $employeeRole = Role::firstOrCreate(['name' => 'Employee']);
        $employeeRole->givePermissionTo([
            // Self-service user management
            'users.view-profile',
            'users.edit-profile',
            
            // Own leave requests
            'leave-requests.view-own',
            'leave-requests.create',
            'leave-requests.edit-own',
            'leave-requests.cancel-own',
            
            // View reference data
            'leave-types.view',
            'holidays.view',
            
            // Own reports
            'reports.view-own',
            
            // Own notifications
            'notifications.view-own',
            'notifications.manage-preferences',
        ]);

        // Intern/Temporary Role (Limited access)
        $internRole = Role::firstOrCreate(['name' => 'Intern']);
        $internRole->givePermissionTo([
            // Very limited self-service
            'users.view-profile',
            
            // Basic leave access (might need approval for creation)
            'leave-requests.view-own',
            'leave-requests.create',
            
            // View only reference data
            'leave-types.view',
            'holidays.view',
            
            // Own basic reports
            'reports.view-own',
            
            // Basic notifications
            'notifications.view-own',
            'notifications.manage-preferences',
        ]);
    }
}
