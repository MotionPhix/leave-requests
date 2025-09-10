<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        $leaveTypes = LeaveType::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->paginate(10)
            ->through(fn ($leaveType) => [
                'id' => $leaveType->id,
                'uuid' => $leaveType->uuid,
                'name' => $leaveType->name,
                'description' => $leaveType->description,
                'max_days_per_year' => $leaveType->max_days_per_year,
                'requires_documentation' => $leaveType->requires_documentation,
                'gender_specific' => $leaveType->gender_specific,
                'gender' => $leaveType->gender,
                'frequency_years' => $leaveType->frequency_years,
                'pay_percentage' => $leaveType->pay_percentage,
                'minimum_notice_days' => $leaveType->minimum_notice_days,
                'allow_negative_balance' => $leaveType->allow_negative_balance,
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
            'description' => 'nullable|string|max:1000',
            'max_days_per_year' => 'required|integer|min:0|max:365',
            'requires_documentation' => 'boolean',
            'gender_specific' => 'boolean',
            'gender' => 'required|string|in:any,male,female',
            'frequency_years' => 'required|integer|min:1|max:10',
            'pay_percentage' => 'required|numeric|min:0|max:100',
            'minimum_notice_days' => 'required|integer|min:0|max:365',
            'allow_negative_balance' => 'boolean',
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
            'description' => 'nullable|string|max:1000',
            'max_days_per_year' => 'required|integer|min:0|max:365',
            'requires_documentation' => 'boolean',
            'gender_specific' => 'boolean',
            'gender' => 'required|string|in:any,male,female',
            'frequency_years' => 'required|integer|min:1|max:10',
            'pay_percentage' => 'required|numeric|min:0|max:100',
            'minimum_notice_days' => 'required|integer|min:0|max:365',
            'allow_negative_balance' => 'boolean',
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
