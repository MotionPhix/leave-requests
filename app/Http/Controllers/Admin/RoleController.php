<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService
    ) {}
    /**
     * Display system-wide role templates for Super Admins
     */
    public function index(): Response
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can manage system roles.');
        }

        $roleDefinitions = $this->rolePermissionService->getWorkspaceRoleDefinitions();
        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        // Get actual roles that exist in the system for reference
        $systemRoles = Role::whereNull('workspace_id')
            ->with(['permissions'])
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions->count(),
                    'workspaces_count' => Role::where('name', $role->name)->whereNotNull('workspace_id')->count(),
                    'created_at' => $role->created_at,
                ];
            });

        return Inertia::render('admin/roles-permissions/Index', [
            'roleDefinitions' => $roleDefinitions,
            'systemRoles' => $systemRoles,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Show form for creating a new role template
     */
    public function create(): Response
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can create role templates.');
        }

        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        return Inertia::render('admin/roles-permissions/Create', [
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Store a new role template (Super Admin only)
     */
    public function store(Request $request)
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can create role templates.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name|regex:/^[a-zA-Z\s]+$/',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'name.regex' => 'Role name can only contain letters and spaces.',
            'label.required' => 'Role label is required.',
            'permissions.required' => 'At least one permission must be selected.',
            'permissions.min' => 'Please select at least one permission.',
        ]);

        // Create system-wide role template (no workspace_id)
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);
        
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role template created successfully. This role can now be used in all workspaces.');
    }

    /**
     * Show form for editing a role template
     */
    public function edit(Role $role): Response
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can edit role templates.');
        }

        // Ensure this is a system-wide role template
        if ($role->workspace_id !== null) {
            abort(404, 'Role template not found.');
        }

        $roleData = [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ];

        $permissionGroups = $this->rolePermissionService->getPermissionGroups();

        return Inertia::render('admin/roles-permissions/Edit', [
            'role' => $roleData,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Update a role template (Super Admin only)
     */
    public function update(Request $request, Role $role)
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can update role templates.');
        }

        // Ensure this is a system-wide role template
        if ($role->workspace_id !== null) {
            abort(404, 'Role template not found.');
        }

        // Prevent updating core system roles
        $protectedRoles = ['Owner', 'Manager', 'HR', 'Employee'];
        if (in_array($role->name, $protectedRoles) && $request->name !== $role->name) {
            return back()->with('error', 'Cannot modify core system role names.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . '|regex:/^[a-zA-Z\s]+$/',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ], [
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'name.regex' => 'Role name can only contain letters and spaces.',
            'permissions.required' => 'At least one permission must be selected.',
            'permissions.min' => 'Please select at least one permission.',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions']);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role template updated successfully. Changes will apply to all workspaces using this role.');
    }

    /**
     * Delete a role template (Super Admin only)
     */
    public function destroy(Role $role)
    {
        if (!auth()->user()->is_system_admin) {
            abort(403, 'Only super admins can delete role templates.');
        }

        // Ensure this is a system-wide role template
        if ($role->workspace_id !== null) {
            abort(404, 'Role template not found.');
        }

        // Prevent deleting core system roles
        $protectedRoles = ['Owner', 'Manager', 'HR', 'Employee'];
        if (in_array($role->name, $protectedRoles)) {
            return back()->with('error', 'Cannot delete core system roles.');
        }

        $roleName = $role->name;
        
        // Delete all workspace instances of this role
        Role::where('name', $roleName)->delete();
        
        return back()->with('success', "Role template '{$roleName}' and all workspace instances have been deleted successfully.");
    }
}
