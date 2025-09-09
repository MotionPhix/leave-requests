<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class LeaveTypeController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $leaveTypes = LeaveType::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->paginate(10)
            ->through(fn($leaveType) => [
                'id' => $leaveType->id,
                'uuid' => $leaveType->uuid,
                'name' => $leaveType->name,
                'days_allowed' => $leaveType->days_allowed,
                'description' => $leaveType->description,
                'is_active' => $leaveType->is_active,
                'carry_forward' => $leaveType->carry_forward,
                'max_carry_forward_days' => $leaveType->max_carry_forward_days,
            ]);

        return Inertia::render('tenant/leave-types/Index', [
            'leaveTypes' => $leaveTypes,
            'workspace' => $workspace,
        ]);
    }

    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        return Inertia::render('tenant/leave-types/Create', [
            'workspace' => $workspace,
        ]);
    }

    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'days_allowed' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0|max:365',
        ]);

        $validated['workspace_id'] = $workspace->id;

        LeaveType::create($validated);

        return redirect()->route('tenant.leave-types.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave type created successfully');
    }

    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave type belongs to this workspace
        if ($leaveType->workspace_id !== $workspace->id) {
            abort(404);
        }

        return Inertia::render('tenant/leave-types/Edit', [
            'leaveType' => $leaveType,
            'workspace' => $workspace,
        ]);
    }

    public function update(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave type belongs to this workspace
        if ($leaveType->workspace_id !== $workspace->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'days_allowed' => 'required|integer|min:1|max:365',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'nullable|integer|min:0|max:365',
        ]);

        $leaveType->update($validated);

        return redirect()->route('tenant.leave-types.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave type updated successfully');
    }

    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Ensure the leave type belongs to this workspace
        if ($leaveType->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Check if leave type is used in any leave requests
        if ($leaveType->leaveRequests()->count() > 0) {
            return redirect()->route('tenant.leave-types.index', [
                'tenant_slug' => $tenant_slug,
                'tenant_uuid' => $tenant_uuid,
            ])->with('error', 'Cannot delete leave type as it is being used in leave requests');
        }

        $leaveType->delete();

        return redirect()->route('tenant.leave-types.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave type deleted successfully');
    }
}
