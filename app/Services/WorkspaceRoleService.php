<?php

namespace App\Services;

use App\Models\Workspace;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class WorkspaceRoleService
{
  public function seedCoreRoles(Workspace $workspace): void
  {
    /** @var PermissionRegistrar $registrar */
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($workspace->getKey());

    // Define workspace roles with their permissions
    $rolesWithPermissions = [
        'Owner' => [
            // All permissions except system-wide ones
            'users.view-any', 'users.create', 'users.edit-any', 'users.activate', 'users.deactivate',
            'users.manage-roles', 'users.bulk-actions',
            'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
            'departments.manage-members', 'departments.view-members',
            'leave-requests.view-any', 'leave-requests.approve', 'leave-requests.reject',
            'leave-requests.view-history', 'leave-requests.cancel-any',
            'leave-types.view', 'leave-types.create', 'leave-types.edit', 'leave-types.delete',
            'leave-types.activate', 'leave-types.deactivate',
            'holidays.view', 'holidays.create', 'holidays.edit', 'holidays.delete', 'holidays.publish',
            'reports.view-company', 'reports.export', 'reports.analytics',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete', 'roles.assign',
            'settings.view', 'settings.edit', 'settings.system', 'settings.employee-id',
            'settings.notifications', 'settings.integrations',
            'notifications.send-custom',
        ],
        'Admin' => [
            // Most permissions except critical system ones
            'users.view-any', 'users.create', 'users.edit-any', 'users.activate', 'users.deactivate',
            'users.bulk-actions',
            'departments.view', 'departments.create', 'departments.edit', 'departments.manage-members',
            'departments.view-members',
            'leave-requests.view-any', 'leave-requests.approve', 'leave-requests.reject',
            'leave-requests.view-history',
            'leave-types.view', 'leave-types.create', 'leave-types.edit', 'leave-types.activate',
            'leave-types.deactivate',
            'holidays.view', 'holidays.create', 'holidays.edit', 'holidays.publish',
            'reports.view-company', 'reports.export',
            'roles.view', 'roles.assign',
            'settings.view', 'settings.edit', 'settings.employee-id', 'settings.notifications',
        ],
        'HR' => [
            // HR specific permissions
            'users.view-any', 'users.create', 'users.edit-any', 'users.activate', 'users.deactivate',
            'leave-requests.view-any', 'leave-requests.approve', 'leave-requests.reject',
            'leave-requests.view-history',
            'leave-types.view', 'leave-types.create', 'leave-types.edit', 'leave-types.activate',
            'leave-types.deactivate',
            'departments.view', 'departments.view-members',
            'holidays.view', 'holidays.create', 'holidays.edit', 'holidays.publish',
            'reports.view-company', 'reports.export', 'reports.analytics',
            'settings.view', 'settings.employee-id',
        ],
        'Manager' => [
            // Department/team management permissions
            'users.view-team', 'users.edit-team',
            'leave-requests.view-team', 'leave-requests.approve', 'leave-requests.reject',
            'departments.view-members',
            'holidays.view',
            'reports.view-team', 'reports.view-department',
        ],
        'Employee' => [
            // Basic employee permissions
            'leave-requests.create', 'leave-requests.view-own', 'leave-requests.edit-own',
            'leave-requests.cancel-own',
            'leave-types.view',
            'holidays.view',
            'reports.view-own',
            'settings.view', 'settings.notifications',
        ],
    ];

    foreach ($rolesWithPermissions as $roleName => $permissions) {
        $role = Role::firstOrCreate([
            'name' => $roleName,
            'workspace_id' => $workspace->getKey(),
        ]);

        // Filter permissions to only use ones that actually exist
        $existingPermissions = \Spatie\Permission\Models\Permission::whereIn('name', $permissions)
            ->pluck('name')
            ->toArray();

        // Sync permissions for this role
        $role->syncPermissions($existingPermissions);
    }
  }
}
