<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}

    /**
     * Display roles and permissions management
     */
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        // Check if user can manage roles
        if (!$this->rolePermissionService->canManageRoles($request->user(), $workspace)) {
            abort(403, 'You do not have permission to manage roles and permissions.');
        }

        $roles = $this->rolePermissionService->getWorkspaceRoles($workspace)
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'uuid' => $role->uuid,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users->count(),
                    'created_at' => $role->created_at,
                ];
            });

        $assignableRoles = $this->rolePermissionService->getAssignableRoles($request->user(), $workspace)
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $this->rolePermissionService->getWorkspaceRoleDefinitions()[$role->name]['label'] ?? $role->name,
                ];
            });

        return Inertia::render('tenant/roles-permissions/Index', [
            'workspace' => $workspace,
            'roles' => $roles,
            'assignableRoles' => $assignableRoles,
            'roleDefinitions' => $this->rolePermissionService->getWorkspaceRoleDefinitions(),
            'canManageRoles' => $this->rolePermissionService->canManageRoles($request->user(), $workspace),
            'canCreateRoles' => $request->user()->is_system_admin, // Only system admins can create new roles
        ]);
    }

    /**
     * Show role details
     */
    public function show(Request $request, string $tenant_slug, string $tenant_uuid, Role $role): Response
    {
        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        if (!$this->rolePermissionService->canManageRoles($request->user(), $workspace)) {
            abort(403, 'You do not have permission to view role details.');
        }

        // Ensure role belongs to this workspace
        if ($role->workspace_id !== $workspace->id) {
            abort(404);
        }

        $roleData = [
            'uuid' => $role->uuid,
            'name' => $role->name,
            'permissions' => $role->permissions->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'group' => explode('.', $p->name)[0],
            ]),
            'users' => $role->users->map(fn($u) => [
                'id' => $u->id,
                'uuid' => $u->uuid,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar,
            ]),
            'created_at' => $role->created_at,
        ];

        // Get all workspace members who can be assigned this role (excludes Owners and current role users)
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        
        $currentUserIds = $role->users->pluck('id')->toArray();
        
        // Get Owner role to exclude owner users
        $ownerRole = Role::where('workspace_id', $workspace->id)
            ->where('name', 'Owner')
            ->first();
        
        $ownerUserIds = $ownerRole ? $ownerRole->users->pluck('id')->toArray() : [];
        
        // Get all workspace users except Owners and current role holders
        // This allows reassigning users from other roles to this role
        $assignableUsers = $workspace->users()
            ->whereNotIn('users.id', array_merge($currentUserIds, $ownerUserIds))
            ->with(['roles' => function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }])
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'uuid' => $u->uuid,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar,
                'current_roles' => $u->roles->pluck('name')->toArray(),
            ]);

        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        return Inertia::render('tenant/roles-permissions/Show', [
            'workspace' => $workspace,
            'role' => $roleData,
            'assignableUsers' => $assignableUsers,
            'permissionGroups' => $permissionGroups,
            'canManageRoles' => $this->rolePermissionService->canManageRoles($request->user(), $workspace),
        ]);
    }

    /**
     * Create new role (Super Admin only)
     */
    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        if (!$request->user()->is_system_admin) {
            abort(403, 'Only super admins can create new roles.');
        }

        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        return Inertia::render('tenant/roles-permissions/Create', [
            'workspace' => $workspace,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Store new role (Super Admin only)
     */
    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        if (!$request->user()->is_system_admin) {
            abort(403, 'Only super admins can create new roles.');
        }

        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->rolePermissionService->createOrUpdateRole(
            $workspace,
            $validated['name'],
            $validated['permissions'],
            $validated['description'] ?? null
        );

        return redirect()->route('tenant.management.roles-permissions.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Role created successfully.');
    }

    /**
     * Edit role (Super Admin only)
     */
    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, Role $role): Response
    {
        if (!$request->user()->is_system_admin) {
            abort(403, 'Only super admins can edit roles.');
        }

        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        if ($role->workspace_id !== $workspace->id) {
            abort(404);
        }

        $roleData = [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        return Inertia::render('tenant/roles-permissions/Edit', [
            'workspace' => $workspace,
            'role' => $roleData,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Update role (Super Admin only)
     */
    public function update(Request $request, string $tenant_slug, string $tenant_uuid, Role $role)
    {
        if (!$request->user()->is_system_admin) {
            abort(403, 'Only super admins can edit roles.');
        }

        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        if ($role->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $this->rolePermissionService->createOrUpdateRole(
            $workspace,
            $validated['name'],
            $validated['permissions']
        );

        return redirect()->route('tenant.management.roles-permissions.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Role updated successfully.');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        if (!$this->rolePermissionService->canManageRoles($request->user(), $workspace)) {
            abort(403, 'You do not have permission to assign roles.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_uuid' => 'required|exists:roles,uuid',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::where('uuid', $validated['role_uuid'])->firstOrFail();

        // Verify role belongs to workspace
        if ($role->workspace_id !== $workspace->id) {
            abort(400, 'Invalid role for this workspace.');
        }

        // Prevent assigning Owner role (only system admins can do that)
        if ($role->name === 'Owner' && !$request->user()->is_system_admin) {
            abort(403, 'You cannot assign the Owner role.');
        }

        // Check if user can assign this specific role
        $assignableRoles = $this->rolePermissionService->getAssignableRoles($request->user(), $workspace);
        if (!$assignableRoles->contains('uuid', $role->uuid)) {
            abort(403, 'You cannot assign this role.');
        }

        // Since users can only have one role, remove all existing workspace roles first
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        $existingRoles = $user->roles()->where('workspace_id', $workspace->id)->get();
        foreach ($existingRoles as $existingRole) {
            $user->removeRole($existingRole);
        }

        // Now assign the new role
        $this->rolePermissionService->assignRoleToUser($user, $role, $workspace);

        return back()->with('success', 'Role assigned successfully.');
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::where('slug', $tenant_slug)
            ->where('uuid', $tenant_uuid)
            ->firstOrFail();

        if (!$this->rolePermissionService->canManageRoles($request->user(), $workspace)) {
            abort(403, 'You do not have permission to remove roles.');
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_uuid' => 'required|exists:roles,uuid',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::where('uuid', $validated['role_uuid'])->firstOrFail();

        if ($role->workspace_id !== $workspace->id) {
            abort(400, 'Invalid role for this workspace.');
        }

        $this->rolePermissionService->removeRoleFromUser($user, $role, $workspace);

        return back()->with('success', 'Role removed successfully.');
    }
}