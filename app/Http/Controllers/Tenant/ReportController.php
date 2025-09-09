<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Get summary statistics for the reports dashboard
        $totalEmployees = $workspace->users()->count();
        $totalLeaveRequests = LeaveRequest::where('workspace_id', $workspace->id)->count();
        $pendingRequests = LeaveRequest::where('workspace_id', $workspace->id)->where('status', 'pending')->count();
        $approvedRequests = LeaveRequest::where('workspace_id', $workspace->id)->where('status', 'approved')->count();

        // Leave usage by month (last 12 months)
        $monthlyUsage = LeaveRequest::where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->where('start_date', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(start_date, "%Y-%m") as month, COUNT(*) as count, SUM(DATEDIFF(end_date, start_date) + 1) as total_days')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top leave requesters
        $topRequesters = User::whereHas('workspaces', function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            ->withCount(['leaveRequests' => function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }])
            ->having('leave_requests_count', '>', 0)
            ->orderByDesc('leave_requests_count')
            ->limit(5)
            ->get()
            ->map(fn($user) => [
                'name' => $user->name,
                'requests_count' => $user->leave_requests_count
            ]);

        return Inertia::render('tenant/reports/Index', [
            'workspace' => $workspace,
            'summary' => [
                'totalEmployees' => $totalEmployees,
                'totalLeaveRequests' => $totalLeaveRequests,
                'pendingRequests' => $pendingRequests,
                'approvedRequests' => $approvedRequests,
            ],
            'monthlyUsage' => $monthlyUsage,
            'topRequesters' => $topRequesters,
        ]);
    }

    public function leaveSummary(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Leave requests by status
        $statusDistribution = LeaveRequest::where('workspace_id', $workspace->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Leave requests by type
        $typeDistribution = LeaveRequest::where('workspace_id', $workspace->id)
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->selectRaw('leave_types.name, COUNT(*) as count')
            ->groupBy('leave_types.name')
            ->get()
            ->pluck('count', 'name');

        // Monthly trend for current year
        $monthlyTrend = LeaveRequest::where('workspace_id', $workspace->id)
            ->whereYear('start_date', now()->year)
            ->selectRaw('MONTH(start_date) as month, COUNT(*) as requests, SUM(DATEDIFF(end_date, start_date) + 1) as total_days')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('tenant/reports/LeaveSummary', [
            'workspace' => $workspace,
            'statusDistribution' => $statusDistribution,
            'typeDistribution' => $typeDistribution,
            'monthlyTrend' => $monthlyTrend,
        ]);
    }

    public function employeeUsage(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Employee leave usage summary
        $employeeUsage = User::whereHas('workspaces', function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            ->with(['leaveRequests' => function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                      ->where('status', 'approved')
                      ->whereYear('start_date', now()->year);
            }])
            ->get()
            ->map(function($user) {
                $totalDays = $user->leaveRequests->sum(function($request) {
                    return $request->start_date->diffInDays($request->end_date) + 1;
                });

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_requests' => $user->leaveRequests->count(),
                    'total_days' => $totalDays,
                    'pending_requests' => $user->leaveRequests->where('status', 'pending')->count(),
                ];
            })
            ->sortByDesc('total_days')
            ->values();

        return Inertia::render('tenant/reports/EmployeeUsage', [
            'workspace' => $workspace,
            'employeeUsage' => $employeeUsage,
        ]);
    }

    public function departmentAnalysis(Request $request, string $tenant_slug, string $tenant_uuid): Response
    {
        $workspace = Workspace::query()->where('slug', $tenant_slug)->where('uuid', $tenant_uuid)->firstOrFail();

        // Department-wise leave analysis
        $departmentAnalysis = Department::where('workspace_id', $workspace->id)
            ->with(['users.leaveRequests' => function($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                      ->where('status', 'approved')
                      ->whereYear('start_date', now()->year);
            }])
            ->get()
            ->map(function($department) {
                $totalRequests = $department->users->sum(function($user) {
                    return $user->leaveRequests->count();
                });

                $totalDays = $department->users->sum(function($user) {
                    return $user->leaveRequests->sum(function($request) {
                        return $request->start_date->diffInDays($request->end_date) + 1;
                    });
                });

                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'employee_count' => $department->users->count(),
                    'total_requests' => $totalRequests,
                    'total_days' => $totalDays,
                    'average_days_per_employee' => $department->users->count() > 0
                        ? round($totalDays / $department->users->count(), 2)
                        : 0,
                ];
            })
            ->sortByDesc('total_days')
            ->values();

        return Inertia::render('tenant/reports/DepartmentAnalysis', [
            'workspace' => $workspace,
            'departmentAnalysis' => $departmentAnalysis,
        ]);
    }
}
