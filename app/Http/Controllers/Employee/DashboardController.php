<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Holiday;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  public function index(LeaveBalanceService $leaveBalanceService): Response
  {
    $user = auth()->user();
    $today = now();

    // Get leave balances
    $leaveTypes = LeaveType::all()->map(function ($type) use ($leaveBalanceService, $user) {
      $used = $leaveBalanceService->getUsedDays($user->id, $type->id);
      $remaining = $type->max_days_per_year - $used;

      return [
        'name' => $type->name,
        'used' => $used,
        'remaining' => max(0, $remaining),
        'max' => $type->max_days_per_year,
        'color' => $type->color ?? '#4CAF50',
      ];
    });

    // Get upcoming leaves (next 30 days)
    $upcomingLeaves = LeaveRequest::where('user_id', $user->id)
      ->where('start_date', '>=', $today)
      ->where('start_date', '<=', $today->copy()->addDays(30))
      ->where('status', 'approved')
      ->with('leaveType')
      ->get()
      ->map(function ($leave) {
        return [
          'id' => $leave->id,
          'type' => $leave->leaveType->name,
          'start_date' => $leave->start_date,
          'end_date' => $leave->end_date,
          'days' => Carbon::parse($leave->start_date)
            ->diffInDaysFiltered(fn($date) => !$date->isWeekend(), Carbon::parse($leave->end_date)) + 1
        ];
      });

    // Get pending requests
    $pendingRequests = LeaveRequest::where('user_id', $user->id)
      ->where('status', 'pending')
      ->with('leaveType')
      ->get()
      ->map(function ($leave) {
        return [
          'id' => $leave->id,
          'type' => $leave->leaveType->name,
          'start_date' => $leave->start_date,
          'end_date' => $leave->end_date,
          'created_at' => $leave->created_at->diffForHumans(),
        ];
      });

    // Get upcoming holidays (next 60 days)
    $holidays = Holiday::where('date', '>=', $today)
      ->where('date', '<=', $today->copy()->addDays(60))
      ->get()
      ->map(function ($holiday) {
        return [
          'name' => $holiday->name,
          'date' => $holiday->date,
          'type' => $holiday->type,
        ];
      });

    // Get monthly leave statistics
    $monthlyStats = LeaveRequest::where('user_id', $user->id)
      ->whereYear('created_at', $today->year)
      ->get()
      ->groupBy(fn($leave) => $leave->created_at->format('F'))
      ->map->count();

    $requests = LeaveRequest::where('user_id', $user->id)->get();

    return Inertia::render('employee/Dashboard', [
      'leaveSummary' => $leaveTypes,
      'upcomingLeaves' => $upcomingLeaves,
      'pendingRequests' => $pendingRequests,
      'holidays' => $holidays,
      'monthlyStats' => [
        'labels' => $monthlyStats->keys(),
        'data' => $monthlyStats->values(),
      ],
      'chartData' => [
        'total' => $requests->count(),
        'approved' => $requests->where('status', 'approved')->count(),
        'rejected' => $requests->where('status', 'rejected')->count(),
        'pending' => $requests->where('status', 'pending')->count(),
      ],
    ]);
  }
}
