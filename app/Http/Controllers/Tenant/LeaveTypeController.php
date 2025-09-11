<?php

namespace App\Http\Controllers\Tenant;

use App\Events\LeaveTypeCreated;
use App\Events\LeaveTypeDeleted;
use App\Events\LeaveTypeUpdated;
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

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
        }

        $leaveTypes = LeaveType::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($leaveType) => [
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
            ])->toArray();

        return Inertia::render('tenant/leave-types/Index', [
            'leaveTypes' => $leaveTypes,
            'workspace' => $workspace,
            'currentUser' => [
                'uuid' => $request->user()->uuid,
                'role' => $request->user()->roles->first()?->name,
            ],
        ]);
    }

    public function create(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
        }

        return Inertia::render('tenant/leave-types/Create', [
            'workspace' => $workspace,
        ]);
    }

    public function show(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can view leave type details.');
        }

        // Ensure the leave type belongs to this workspace
        if ($leaveType->workspace_id !== $workspace->id) {
            abort(404);
        }

        // Get analytics data
        $analytics = [
            'total_employees_eligible' => $this->getEligibleEmployeesCount($leaveType),
            'total_requests_this_year' => $this->getTotalRequestsThisYear($leaveType),
            'total_days_taken' => $this->getTotalDaysTaken($leaveType),
            'average_days_per_employee' => $this->getAverageDaysPerEmployee($leaveType),
            'pending_requests' => $this->getPendingRequests($leaveType),
            'approved_requests' => $this->getApprovedRequests($leaveType),
            'rejected_requests' => $this->getRejectedRequests($leaveType),
            'most_common_months' => $this->getMostCommonMonths($leaveType),
        ];

        return Inertia::render('tenant/leave-types/Show', [
            'leaveType' => $leaveType,
            'analytics' => $analytics,
            'workspace' => $workspace,
            'canManageLeaveTypes' => true,
        ]);
    }

    public function store(Request $request, string $tenant_slug, string $tenant_uuid)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
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

        // Fire the Verbs event instead of direct database operations
        LeaveTypeCreated::commit(
            workspace_id: (string) $workspace->id,
            name: $validated['name'],
            description: $validated['description'],
            max_days_per_year: $validated['max_days_per_year'],
            requires_documentation: $validated['requires_documentation'] ?? false,
            gender_specific: $validated['gender_specific'] ?? false,
            gender: $validated['gender'],
            frequency_years: $validated['frequency_years'],
            pay_percentage: (float) $validated['pay_percentage'],
            minimum_notice_days: $validated['minimum_notice_days'],
            allow_negative_balance: $validated['allow_negative_balance'] ?? false,
            created_by_id: (string) $request->user()->id
        );

        return redirect()->route('tenant.management.leave-types.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave type created successfully');
    }

    public function edit(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
        }

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

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
        }

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

        // Fire the Verbs event instead of direct database operations
        LeaveTypeUpdated::commit(
            leave_type_uuid: $leaveType->uuid,
            workspace_id: (string) $workspace->id,
            name: $validated['name'],
            description: $validated['description'],
            max_days_per_year: $validated['max_days_per_year'],
            requires_documentation: $validated['requires_documentation'] ?? false,
            gender_specific: $validated['gender_specific'] ?? false,
            gender: $validated['gender'],
            frequency_years: $validated['frequency_years'],
            pay_percentage: (float) $validated['pay_percentage'],
            minimum_notice_days: $validated['minimum_notice_days'],
            allow_negative_balance: $validated['allow_negative_balance'] ?? false,
            updated_by_id: (string) $request->user()->id
        );

        return redirect()->route('tenant.management.leave-types.index', [
            'tenant_slug' => $tenant_slug,
            'tenant_uuid' => $tenant_uuid,
        ])->with('success', 'Leave type updated successfully');
    }

    public function destroy(Request $request, string $tenant_slug, string $tenant_uuid, LeaveType $leaveType)
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Authorization: only Owner/HR can manage leave types
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($workspace->id);
        if (! $request->user()->hasAnyRole(['Owner', 'HR', 'Super Admin'])) {
            abort(403, 'Only workspace owners and HR can manage leave types.');
        }

        try {
            // Fire the Verbs event with validation (will check usage and ownership)
            LeaveTypeDeleted::commit(
                leave_type_uuid: $leaveType->uuid,
                workspace_id: (string) $workspace->id,
                deleted_by_id: (string) $request->user()->id
            );

            return redirect()->route('tenant.management.leave-types.index', [
                'tenant_slug' => $tenant_slug,
                'tenant_uuid' => $tenant_uuid,
            ])->with('success', 'Leave type deleted successfully');

        } catch (\Exception $e) {
            return redirect()->route('tenant.management.leave-types.index', [
                'tenant_slug' => $tenant_slug,
                'tenant_uuid' => $tenant_uuid,
            ])->with('error', $e->getMessage());
        }
    }

    private function getEligibleEmployeesCount(LeaveType $leaveType): int
    {
        // Using User model as employees - filter by workspace membership
        // Exclude owners as they should not be counted as eligible employees for leave
        return \App\Models\User::whereHas('workspaces', function ($query) use ($leaveType) {
                $query->where('workspaces.id', $leaveType->workspace_id);
            })
            ->whereDoesntHave('roles', function ($query) use ($leaveType) {
                $query->where('roles.name', 'Owner')
                      ->where('roles.workspace_id', $leaveType->workspace_id);
            })
            ->when($leaveType->gender_specific && $leaveType->gender !== 'any', function ($query) use ($leaveType) {
                return $query->where('users.gender', $leaveType->gender);
            })
            ->count();
    }

    private function getTotalRequestsThisYear(LeaveType $leaveType): int
    {
        // Check if LeaveRequest model exists and has the relationship
        if (class_exists(\App\Models\LeaveRequest::class)) {
            return \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
                ->whereYear('created_at', now()->year)
                ->count();
        }
        
        return 0; // Return placeholder if model doesn't exist yet
    }

    private function getTotalDaysTaken(LeaveType $leaveType): int
    {
        // Check if LeaveRequest model exists
        if (class_exists(\App\Models\LeaveRequest::class)) {
            return \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->sum(\DB::raw('DATEDIFF(end_date, start_date) + 1')) ?? 0;
        }
        
        return 0; // Return placeholder if model doesn't exist yet
    }

    private function getAverageDaysPerEmployee(LeaveType $leaveType): float
    {
        $totalDays = $this->getTotalDaysTaken($leaveType);
        $eligibleEmployees = $this->getEligibleEmployeesCount($leaveType);
        
        return $eligibleEmployees > 0 ? round($totalDays / $eligibleEmployees, 1) : 0;
    }

    private function getPendingRequests(LeaveType $leaveType): int
    {
        if (class_exists(\App\Models\LeaveRequest::class)) {
            return \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
                ->where('status', 'pending')
                ->whereYear('created_at', now()->year)
                ->count();
        }
        
        return 0;
    }

    private function getApprovedRequests(LeaveType $leaveType): int
    {
        if (class_exists(\App\Models\LeaveRequest::class)) {
            return \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->whereYear('created_at', now()->year)
                ->count();
        }
        
        return 0;
    }

    private function getRejectedRequests(LeaveType $leaveType): int
    {
        if (class_exists(\App\Models\LeaveRequest::class)) {
            return \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
                ->where('status', 'rejected')
                ->whereYear('created_at', now()->year)
                ->count();
        }
        
        return 0;
    }

    private function getMostCommonMonths(LeaveType $leaveType): array
    {
        if (!class_exists(\App\Models\LeaveRequest::class)) {
            return [];
        }

        // Get the most common months for this leave type
        $months = \App\Models\LeaveRequest::where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->get()
            ->groupBy(function($request) {
                return \Carbon\Carbon::parse($request->start_date)->format('F');
            })
            ->sortByDesc(function($group) {
                return $group->count();
            })
            ->take(3)
            ->keys()
            ->toArray();

        return $months;
    }
}
