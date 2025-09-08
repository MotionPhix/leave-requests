<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->attributes->get('workspace');
        $user = Auth::user();

        // Get user's role information
        $userRole = $user->getRoleNames()->first();
        $canApproveLeave = $user->can('leave-requests.approve');
        $canViewAllUsers = $user->can('users.view-any');
        $canCreateLeaveRequests = $user->can('leave-requests.create');
        $isOwner = $user->hasRole('Owner');

        // Get user's leave balance summary (if not owner)
        $leaveSummary = !$isOwner ? $this->getUserLeaveSummary($user, $workspace) : null;

        // Get user's recent leave requests (if not owner)
        $myRecentRequests = !$isOwner ? $this->getMyRecentRequests($user, $workspace) : [];

        // Get pending team requests if user has approval permissions
        $teamPendingRequests = $canApproveLeave ? $this->getTeamPendingRequests($user, $workspace) : [];

        // Get upcoming holidays
        $upcomingHolidays = $this->getUpcomingHolidays($workspace);

        // Get dashboard stats based on user's permissions
        $stats = $this->getDashboardStats($user, $workspace);

        return Inertia::render('tenant/Dashboard', [
            'workspace' => [
                'uuid' => $workspace?->uuid,
                'slug' => $workspace?->slug,
                'name' => $workspace?->name,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $userRole,
                'isOwner' => $isOwner,
                'permissions' => [
                    'canApproveLeave' => $canApproveLeave,
                    'canViewAllUsers' => $canViewAllUsers,
                    'canCreateLeaveRequests' => $canCreateLeaveRequests,
                ]
            ],
            'stats' => $stats,
            'myRecentRequests' => $myRecentRequests,
            'teamPendingRequests' => $teamPendingRequests,
            'upcomingHolidays' => $upcomingHolidays,
            'leaveSummary' => $leaveSummary,
        ]);
    }

    private function getUserLeaveSummary($user, $workspace)
    {
        $leaveTypes = LeaveType::where('workspace_id', $workspace->id)
            ->get();

        $summary = [];

        foreach ($leaveTypes as $leaveType) {
            // Calculate used days for this year using PHP calculation
            $approvedRequests = LeaveRequest::where('user_id', $user->id)
                ->where('workspace_id', $workspace->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->whereYear('start_date', now()->year)
                ->get(['start_date', 'end_date']);

            $usedDays = $approvedRequests->sum(function ($request) {
                $startDate = \Carbon\Carbon::parse($request->start_date);
                $endDate = \Carbon\Carbon::parse($request->end_date);
                return $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates
            });

            $summary[] = [
                'type' => $leaveType->name,
                'used' => (int) $usedDays,
                'total' => $leaveType->max_days_per_year,
                'color' => $this->getLeaveTypeColor($leaveType->name),
            ];
        }

        return $summary;
    }

    private function getMyRecentRequests($user, $workspace)
    {
        return LeaveRequest::with(['leaveType'])
            ->where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'type' => $request->leaveType->name,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                ];
            });
    }

    private function getTeamPendingRequests($user, $workspace)
    {
        // Check if user has permission to approve requests
        if (! $user->can('leave-requests.approve')) {
            return collect();
        }

        return LeaveRequest::with(['user', 'leaveType'])
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->where('user_id', '!=', $user->id) // Exclude own requests
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'employee_name' => $request->user->name,
                    'type' => $request->leaveType->name,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                ];
            });
    }

    private function getUpcomingHolidays($workspace)
    {
        return Holiday::where('workspace_id', $workspace->id)
            ->where('date', '>=', now())
            ->orderBy('date')
            ->limit(5)
            ->get()
            ->map(function ($holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->date,
                    'type' => $holiday->type ?? 'Public Holiday',
                ];
            });
    }

    private function getDashboardStats($user, $workspace)
    {
        $stats = [
            'myLeaveBalance' => $this->getMyLeaveBalance($user, $workspace),
            'myPendingRequests' => $this->getMyPendingRequestsCount($user, $workspace),
        ];

        // Add team/company stats if user has permissions
        if ($user->can('leave-requests.view-team')) {
            $stats['teamPendingRequests'] = $this->getTeamPendingRequestsCount($user, $workspace);
        }

        if ($user->can('users.view-any')) {
            $stats['totalEmployees'] = $this->getTotalEmployeesCount($workspace);
        }

        return $stats;
    }

    private function getMyLeaveBalance($user, $workspace)
    {
        // Get total available leave days for current year
        $totalAvailable = LeaveType::where('workspace_id', $workspace->id)
            ->sum('max_days_per_year');

        // Get used leave days for current year using PHP calculation
        $approvedRequests = LeaveRequest::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->where('status', 'approved')
            ->whereYear('start_date', now()->year)
            ->get(['start_date', 'end_date']);

        $usedDays = $approvedRequests->sum(function ($request) {
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $endDate = \Carbon\Carbon::parse($request->end_date);
            return $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates
        });

        return max(0, $totalAvailable - $usedDays);
    }

    private function getMyPendingRequestsCount($user, $workspace)
    {
        return LeaveRequest::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->count();
    }

    private function getTeamPendingRequestsCount($user, $workspace)
    {
        return LeaveRequest::where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->where('user_id', '!=', $user->id)
            ->count();
    }

    private function getTotalEmployeesCount($workspace)
    {
        return User::whereHas('workspaces', function ($query) use ($workspace) {
            $query->where('workspaces.id', $workspace->id);
        })->count();
    }

    private function getLeaveTypeColor($leaveType)
    {
        $colors = [
            'Annual Leave' => 'bg-blue-500',
            'Sick Leave' => 'bg-red-500',
            'Personal Leave' => 'bg-green-500',
            'Maternity Leave' => 'bg-pink-500',
            'Paternity Leave' => 'bg-purple-500',
            'Study Leave' => 'bg-yellow-500',
            'Emergency Leave' => 'bg-orange-500',
        ];

        return $colors[$leaveType] ?? 'bg-gray-500';
    }
}
