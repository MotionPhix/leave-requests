<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  public function index(LeaveBalanceService $leaveBalanceService): Response
  {
    $user = auth()->user();

    $leaveTypes = LeaveType::all()->map(function ($type) use ($leaveBalanceService, $user) {
      $used = $leaveBalanceService->getUsedDays($user->id, $type->id);
      $remaining = $type->max_days_per_year - $used;

      return [
        'name' => $type->name,
        'used' => $used,
        'remaining' => max(0, $remaining),
        'max' => $type->max_days_per_year,
      ];
    });

    $requests = LeaveRequest::where('user_id', $user->id)->get();

    $chartData = [
      'total' => $requests->count(),
      'approved' => $requests->where('status', 'approved')->count(),
      'rejected' => $requests->where('status', 'rejected')->count(),
      'pending' => $requests->where('status', 'pending')->count(),
    ];

    return Inertia::render('Dashboard', [
      'leaveSummary' => $leaveTypes,
      'chartData' => $chartData,
    ]);
  }
}
