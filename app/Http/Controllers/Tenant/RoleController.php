<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display the roles management page
     */
    public function index(Request $request): Response
    {
        $workspace = $request->workspace;

        // Get workspace-specific roles
        $roles = Role::whereJsonContains('guard_name', $workspace->uuid)
            ->with('permissions')
            ->get();

        $permissions = Permission::all();

        return Inertia::render('tenant/roles/Index', [
            'workspace' => $workspace,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show the form for creating a new role
     */
    public function create(Request $request): Response
    {
        $workspace = $request->workspace;
        $permissions = Permission::all();

        return Inertia::render('tenant/roles/Create', [
            'workspace' => $workspace,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $workspace = $request->workspace;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $workspace->uuid, // Workspace-specific role
            'description' => $validated['description'] ?? null,
        ]);

        if (! empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('tenant.roles.index', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Role created successfully.');
    }

    /**
     * Show the form for editing a role
     */
    public function edit(Request $request, Role $role): Response
    {
        $workspace = $request->workspace;
        $permissions = Permission::all();

        return Inertia::render('tenant/roles/Edit', [
            'workspace' => $workspace,
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $workspace = $request->workspace;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('tenant.roles.index', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role
     */
    public function destroy(Request $request, Role $role)
    {
        $workspace = $request->workspace;

        // Prevent deletion of system roles
        if (in_array($role->name, ['Owner', 'Manager', 'HR', 'Employee'])) {
            return redirect()->route('tenant.roles.index', [
                'tenant_slug' => $workspace->slug,
                'tenant_uuid' => $workspace->uuid,
            ])->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('tenant.roles.index', [
            'tenant_slug' => $workspace->slug,
            'tenant_uuid' => $workspace->uuid,
        ])->with('success', 'Role deleted successfully.');
    }
}
