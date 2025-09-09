<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $departments = Department::query()
            ->where('workspace_id', $workspace->id)
            ->withCount('users')
            ->orderBy('name')
            ->paginate(10)
            ->through(fn($department) => [
                'id' => $department->id,
                'uuid' => $department->uuid,
                'name' => $department->name,
                'description' => $department->description,
                'head_user_id' => $department->head_user_id,
                'head_user_name' => $department->headUser?->name,
                'users_count' => $department->users_count,
            ]);

        return Inertia::render('tenant/departments/Index', [
            'departments' => $departments,
            'workspace' => $workspace,
        ]);
    }

    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Get all users in the workspace who could be department heads
        $potentialHeads = $workspace->users()
            ->whereHas('roles', function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                      ->whereIn('name', ['Owner', 'Manager']);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('tenant/departments/Create', [
            'workspace' => $workspace,
            'potentialHeads' => $potentialHeads,
        ]);
    }

    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_user_id' => 'nullable|exists:users,id',
        ]);

        $validated['workspace_id'] = $workspace->id;

        Department::create($validated);

        return redirect()->route('tenant.departments.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Department created successfully');
    }

    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, Department $department): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the department belongs to this workspace
        if ($department->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Get all users in the workspace who could be department heads
        $potentialHeads = $workspace->users()
            ->whereHas('roles', function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                      ->whereIn('name', ['Owner', 'Manager']);
            })
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('tenant/departments/Edit', [
            'department' => $department,
            'workspace' => $workspace,
            'potentialHeads' => $potentialHeads,
        ]);
    }

    public function update(Request $request, string $tenant_slug, string $tenant_uuid, Department $department)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the department belongs to this workspace
        if ($department->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_user_id' => 'nullable|exists:users,id',
        ]);

        $department->update($validated);

        return redirect()->route('tenant.departments.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Department updated successfully');
    }

    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, Department $department)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the department belongs to this workspace
        if ($department->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Check if department has any users
        if ($department->users()->count() > 0) {
            return redirect()->route('tenant.departments.index', [
                'tenant_slug' => $tenant_slug,
                'tenant_uuid' => $tenant_uuid,
            ])->with('error', 'Cannot delete department as it has assigned users');
        }

        $department->delete();

        return redirect()->route('tenant.departments.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Department deleted successfully');
    }
}
