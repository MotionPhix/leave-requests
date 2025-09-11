<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionService
{
    public function __construct(
        private PermissionRegistrar $permissionRegistrar
    ) {}

    /**
     * Get all available permissions grouped by category
     */
    public function getPermissionGroups(): array
    {
        return [
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
            'leave-types' => [
                'leave-types.view',
                'leave-types.create',
                'leave-types.edit',
                'leave-types.delete',
                'leave-types.activate',
                'leave-types.deactivate',
            ],
            'departments' => [
                'departments.view',
                'departments.create',
                'departments.edit',
                'departments.delete',
                'departments.assign-manager',
                'departments.view-members',
            ],
            'holidays' => [
                'holidays.view',
                'holidays.create',
                'holidays.edit',
                'holidays.delete',
                'holidays.publish',
            ],
            'reports' => [
                'reports.view-own',
                'reports.view-team',
                'reports.view-department',
                'reports.view-company',
                'reports.export',
                'reports.analytics',
            ],
            'roles' => [
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.assign',
            ],
            'settings' => [
                'settings.view',
                'settings.edit',
                'settings.system',
                'settings.employee-id',
                'settings.notifications',
            ],
            'notifications' => [
                'notifications.view-own',
                'notifications.manage-preferences',
                'notifications.send-custom',
            ],
        ];
    }

    /**
     * Get workspace-specific role definitions (excludes system admin roles)
     */
    public function getWorkspaceRoleDefinitions(): array
    {
        return [
            'Owner' => [
                'label' => 'Owner',
                'description' => 'Full access to workspace with ability to manage all settings and users',
                'permissions' => [
                    'users.view-any', 'users.create', 'users.edit-any', 'users.activate', 'users.deactivate',
                    'users.assign-roles',
                    'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                    'departments.assign-manager', 'departments.view-members',
                    'leave-requests.view-any', 'leave-requests.approve', 'leave-requests.reject',
                    'leave-requests.view-history', 'leave-requests.force-approve',
                    'leave-types.view', 'leave-types.create', 'leave-types.edit', 'leave-types.delete',
                    'leave-types.activate', 'leave-types.deactivate',
                    'holidays.view', 'holidays.create', 'holidays.edit', 'holidays.delete', 'holidays.publish',
                    'reports.view-company', 'reports.export', 'reports.analytics',
                    'roles.view', 'roles.assign',
                    'settings.view', 'settings.edit', 'settings.employee-id', 'settings.notifications',
                    'notifications.send-custom',
                ],
                'assignable_by' => [], // Only system admin can assign owner role
            ],
            'HR' => [
                'label' => 'Human Resources',
                'description' => 'HR management with focus on employee and leave management',
                'permissions' => [
                    'users.view-any', 'users.create', 'users.edit-any', 'users.activate', 'users.deactivate',
                    'leave-requests.create', 'leave-requests.view-any', 'leave-requests.approve', 'leave-requests.reject',
                    'leave-requests.view-history', 'leave-requests.view-own', 'leave-requests.edit-own', 'leave-requests.cancel-own',
                    'leave-types.view', 'leave-types.create', 'leave-types.edit', 'leave-types.activate',
                    'leave-types.deactivate',
                    'departments.view', 'departments.view-members',
                    'holidays.view', 'holidays.create', 'holidays.edit', 'holidays.publish',
                    'reports.view-company', 'reports.export', 'reports.analytics',
                    'settings.view', 'settings.employee-id',
                ],
                'assignable_by' => ['Owner', 'Admin'],
            ],
            'Manager' => [
                'label' => 'Manager',
                'description' => 'Team/department management with approval rights for direct reports',
                'permissions' => [
                    'users.view-team', 'users.edit-team',
                    'leave-requests.view-team', 'leave-requests.approve', 'leave-requests.reject',
                    'departments.view-members',
                    'holidays.view',
                    'reports.view-team', 'reports.view-department',
                ],
                'assignable_by' => ['Owner', 'Admin', 'HR'],
            ],
            'Employee' => [
                'label' => 'Employee',
                'description' => 'Basic employee access for managing own leave and viewing company information',
                'permissions' => [
                    'leave-requests.create', 'leave-requests.view-own', 'leave-requests.edit-own',
                    'leave-requests.cancel-own',
                    'leave-types.view',
                    'holidays.view',
                    'reports.view-own',
                    'users.view-profile', 'users.edit-profile',
                    'settings.view', 'settings.notifications',
                    'notifications.view-own', 'notifications.manage-preferences',
                ],
                'assignable_by' => ['Owner', 'Admin', 'HR'],
            ],
        ];
    }

    /**
     * Get roles available for assignment by a specific role
     */
    public function getAssignableRoles(User $user, Workspace $workspace): Collection
    {
        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);
        
        if ($user->is_system_admin) {
            // System admins can assign any role
            return $this->getWorkspaceRoles($workspace);
        }

        $userRoles = $user->getRoleNames()->toArray();
        $roleDefinitions = $this->getWorkspaceRoleDefinitions();
        $assignableRoles = collect();

        foreach ($roleDefinitions as $roleName => $definition) {
            $canAssign = false;
            
            foreach ($userRoles as $userRole) {
                if (in_array($userRole, $definition['assignable_by'] ?? [])) {
                    $canAssign = true;
                    break;
                }
            }

            if ($canAssign) {
                $role = Role::where('name', $roleName)
                    ->where('workspace_id', $workspace->id)
                    ->first();
                if ($role) {
                    $assignableRoles->push($role);
                }
            }
        }

        return $assignableRoles;
    }

    /**
     * Get all roles for a workspace (excludes system admin roles)
     */
    public function getWorkspaceRoles(Workspace $workspace): Collection
    {
        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);
        return Role::where('workspace_id', $workspace->id)
            ->whereNotIn('name', ['Admin']) // Exclude system admin roles
            ->get();
    }

    /**
     * Get permissions available to workspace owners (excludes system admin permissions)
     */
    public function getWorkspacePermissions(): Collection
    {
        $permissionGroups = $this->getPermissionGroups();
        $permissionNames = collect($permissionGroups)->flatten()->toArray();
        
        return Permission::whereIn('name', $permissionNames)->get();
    }

    /**
     * Create or update a workspace role
     */
    public function createOrUpdateRole(Workspace $workspace, string $name, array $permissions, string $description = null): Role
    {
        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);

        $role = Role::updateOrCreate([
            'name' => $name,
            'workspace_id' => $workspace->id,
        ], [
            'guard_name' => 'web',
        ]);

        // Sync permissions
        $role->syncPermissions($permissions);

        return $role;
    }

    /**
     * Assign role to user in workspace
     */
    public function assignRoleToUser(User $user, Role $role, Workspace $workspace): void
    {
        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);
        $user->assignRole($role);
    }

    /**
     * Remove role from user in workspace
     */
    public function removeRoleFromUser(User $user, Role $role, Workspace $workspace): void
    {
        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);
        $user->removeRole($role);
    }

    /**
     * Check if user can manage roles in workspace
     */
    public function canManageRoles(User $user, Workspace $workspace): bool
    {
        if ($user->is_system_admin) {
            return true;
        }

        $this->permissionRegistrar->setPermissionsTeamId($workspace->id);
        return $user->hasPermissionTo('roles.assign');
    }
}