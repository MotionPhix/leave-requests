<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class CalendarController extends Controller
{
    /**
     * Display the management calendar view (Owner/Manager/HR)
     */
    public function index(Request $request): Response
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        // Determine user role for appropriate calendar view
        $userRole = $this->getUserRole($user, $workspace);
        
        // Get team members for management view filtering (exclude owner, managers, and HR)
        $teamMembers = [];
        if (in_array($userRole, ['owner', 'manager', 'hr'])) {
            $teamMembers = User::whereHas('workspaces', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id);
            })
            // Exclude workspace owner
            ->where('id', '!=', $workspace->owner_id)
            // Exclude users with owner role in pivot table
            ->whereDoesntHave('workspaces', function ($query) use ($workspace) {
                $query->where('workspace_id', $workspace->id)
                      ->where('role', 'owner');
            })
            // Exclude users with manager or HR roles
            ->whereDoesntHave('roles', function ($query) use ($workspace) {
                $query->whereIn('name', ['manager', 'hr', 'human resources'])
                      ->where('model_has_roles.workspace_id', $workspace->id);
            })
            ->select(['id', 'uuid', 'name', 'email'])
            ->get();
        }

        return Inertia::render('tenant/calendar/Index', [
            'workspace' => $workspace,
            'userRole' => $userRole,
            'teamMembers' => $teamMembers,
            'canManageHolidays' => $userRole === 'owner',
            'canManageLeave' => in_array($userRole, ['owner', 'manager', 'hr']),
        ]);
    }

    /**
     * Get calendar events based on user role and permissions
     */
    public function events(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        $userRole = $this->getUserRole($user, $workspace);
        
        $start = $request->input('start');
        $end = $request->input('end');
        $teamMemberIds = $request->input('team_members', []);

        $events = collect();

        // Get leave requests based on user role
        $leaveRequestsQuery = LeaveRequest::query()
            ->where('workspace_id', $workspace->id)
            ->inDateRange($start, $end)
            ->with(['user', 'leaveType']);

        // Filter based on user role and permissions
        if ($userRole === 'employee') {
            // Employees see only their own requests and approved requests from others
            $leaveRequestsQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('status', 'approved');
            });
        } else {
            // Management roles can see all requests or filter by team members
            if (!empty($teamMemberIds)) {
                $leaveRequestsQuery->whereIn('user_id', $teamMemberIds);
            }
        }

        $leaveRequests = $leaveRequestsQuery->get()->map(function ($leave) use ($userRole, $user) {
            $isOwnRequest = $leave->user_id === $user->id;
            
            return [
                'id' => $leave->uuid,
                'title' => $this->formatLeaveTitle($leave, $userRole, $isOwnRequest),
                'start' => $leave->start_date->toDateString(),
                'end' => $leave->end_date->addDay()->toDateString(), // FullCalendar exclusive end date
                'backgroundColor' => $this->getLeaveColor($leave, $isOwnRequest),
                'borderColor' => $this->getLeaveBorderColor($leave, $isOwnRequest),
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'leave',
                    'user' => $leave->user->name,
                    'leaveType' => $leave->leaveType->name,
                    'status' => $leave->status,
                    'isOwnRequest' => $isOwnRequest,
                    'canManage' => in_array($userRole, ['owner', 'manager', 'hr']) && !$isOwnRequest,
                    'reason' => $leave->reason,
                    'duration' => $leave->start_date->diffInDays($leave->end_date) + 1,
                ],
            ];
        });

        // Get holidays based on visibility settings
        $holidaysQuery = Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->inDateRange($start, $end)
            ->visibleToRole($userRole);

        $holidays = $holidaysQuery->get()->map(function ($holiday) use ($userRole) {
            return [
                'id' => $holiday->uuid,
                'title' => $holiday->name,
                'start' => $holiday->start_date->toDateString(),
                'end' => $holiday->end_date->addDay()->toDateString(), // FullCalendar exclusive end date
                'backgroundColor' => $holiday->color ?? '#ef4444',
                'borderColor' => $this->darkenColor($holiday->color ?? '#ef4444'),
                'textColor' => '#ffffff',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'holiday',
                    'description' => $holiday->description,
                    'isRecurring' => $holiday->is_recurring,
                    'canManage' => $userRole === 'owner',
                    'duration' => $holiday->getDurationInDays(),
                ],
            ];
        });

        return response()->json($events->concat($holidays)->values());
    }

    /**
     * Get leave request conflicts for a specific date range
     * Used when creating/editing leave requests or holidays
     */
    public function conflicts(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'exclude_id' => 'nullable|string', // Exclude specific request when editing
        ]);

        $workspace = $request->attributes->get('workspace');
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Find conflicting leave requests
        $conflicts = LeaveRequest::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', '!=', 'rejected')
            ->when($request->exclude_id, fn($query) => $query->where('uuid', '!=', $request->exclude_id))
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                          $subQuery->where('start_date', '<=', $startDate)
                                   ->where('end_date', '>=', $endDate);
                      });
            })
            ->with(['user', 'leaveType'])
            ->get()
            ->map(function ($leave) {
                return [
                    'id' => $leave->uuid,
                    'user' => $leave->user->name,
                    'leaveType' => $leave->leaveType->name,
                    'start_date' => $leave->start_date,
                    'end_date' => $leave->end_date,
                    'status' => $leave->status,
                ];
            });

        // Find conflicting holidays
        $holidays = Holiday::query()
            ->where('workspace_id', $workspace->id)
            ->inDateRange($startDate, $endDate)
            ->get()
            ->map(function ($holiday) {
                return [
                    'id' => $holiday->uuid,
                    'name' => $holiday->name,
                    'start_date' => $holiday->start_date,
                    'end_date' => $holiday->end_date,
                    'type' => 'holiday',
                ];
            });

        return response()->json([
            'leave_requests' => $conflicts,
            'holidays' => $holidays,
            'conflict_count' => $conflicts->count() + $holidays->count(),
        ]);
    }

    /**
     * Get team utilization data for management dashboard
     */
    public function utilization(Request $request): JsonResponse
    {
        $workspace = $request->attributes->get('workspace');
        $user = $request->user();
        
        // Only management roles can access utilization data
        if (!in_array($this->getUserRole($user, $workspace), ['owner', 'manager', 'hr'])) {
            abort(403, 'Insufficient permissions to view utilization data');
        }

        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Get team members (exclude owner, managers, and HR)
        $teamMembers = User::whereHas('workspaces', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id);
        })
        // Exclude workspace owner
        ->where('id', '!=', $workspace->owner_id)
        // Exclude users with owner role in pivot table
        ->whereDoesntHave('workspaces', function ($query) use ($workspace) {
            $query->where('workspace_id', $workspace->id)
                  ->where('role', 'owner');
        })
        // Exclude users with manager or HR roles
        ->whereDoesntHave('roles', function ($query) use ($workspace) {
            $query->whereIn('name', ['manager', 'hr', 'human resources'])
                  ->where('model_has_roles.workspace_id', $workspace->id);
        })
        ->get();

        $utilizationData = $teamMembers->map(function ($member) use ($startDate, $endDate, $workspace) {
            $leaveRequests = LeaveRequest::query()
                ->where('workspace_id', $workspace->id)
                ->where('user_id', $member->id)
                ->where('status', 'approved')
                ->inDateRange($startDate, $endDate)
                ->get();

            $totalLeaveDays = $leaveRequests->sum(function ($leave) {
                return $leave->start_date->diffInDays($leave->end_date) + 1;
            });

            return [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'total_leave_days' => $totalLeaveDays,
                'leave_requests' => $leaveRequests->count(),
                'utilization_percentage' => round(($totalLeaveDays / $startDate->daysInMonth) * 100, 2),
            ];
        });

        return response()->json($utilizationData);
    }

    /**
     * Get user role in workspace context
     */
    private function getUserRole(User $user, $workspace): string
    {
        if ($user->isOwner || $user->workspaces()->where('workspace_id', $workspace->id)->wherePivot('role', 'owner')->exists()) {
            return 'owner';
        }

        $roles = $user->roles()->wherePivot('tenant_id', $workspace->id)->pluck('name')->map(fn($role) => strtolower($role));

        if ($roles->contains('manager')) return 'manager';
        if ($roles->contains('hr') || $roles->contains('human resources')) return 'hr';
        
        return 'employee';
    }

    /**
     * Format leave title based on user role and ownership
     */
    private function formatLeaveTitle(LeaveRequest $leave, string $userRole, bool $isOwnRequest): string
    {
        if ($isOwnRequest) {
            return $leave->leaveType->name . ' (You)';
        }

        if ($userRole === 'employee') {
            return $leave->user->name . ' - On Leave';
        }

        return $leave->user->name . ' - ' . $leave->leaveType->name;
    }

    /**
     * Get leave request color based on status and ownership
     */
    private function getLeaveColor(LeaveRequest $leave, bool $isOwnRequest): string
    {
        if ($isOwnRequest) {
            return match ($leave->status) {
                'approved' => '#10b981', // Green
                'pending' => '#f59e0b',   // Amber
                'rejected' => '#ef4444',  // Red
                default => '#6b7280',     // Gray
            };
        }

        return match ($leave->status) {
            'approved' => '#3b82f6',  // Blue
            'pending' => '#8b5cf6',   // Purple
            'rejected' => '#6b7280',  // Gray
            default => '#6b7280',
        };
    }

    /**
     * Get border color (darker version of background color)
     */
    private function getLeaveBorderColor(LeaveRequest $leave, bool $isOwnRequest): string
    {
        return $this->darkenColor($this->getLeaveColor($leave, $isOwnRequest));
    }

    /**
     * Darken a hex color by 20%
     */
    private function darkenColor(string $hex): string
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, $r - ($r * 0.2));
        $g = max(0, $g - ($g * 0.2));
        $b = max(0, $b - ($b * 0.2));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
