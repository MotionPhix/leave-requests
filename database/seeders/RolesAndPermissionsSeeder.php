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
            // Workspace Management (Super Admin)
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

        // Note: All workspace-specific roles (Owner, Admin, HR, Manager, Employee) 
        // are created per workspace by WorkspaceRoleService with appropriate permissions.
        // This keeps the permission system clean and workspace-scoped.
    }
}
