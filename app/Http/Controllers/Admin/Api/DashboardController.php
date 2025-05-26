<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
  public function stats(Request $request): \Illuminate\Http\JsonResponse
  {
    // Monthly leave count
    $monthly = LeaveRequest::selectRaw('MONTH(start_date) as month, COUNT(*) as count')
      ->whereYear('start_date', now()->year)
      ->groupBy('month')
      ->pluck('count', 'month');

    // Leave types distribution
    $types = LeaveRequest::selectRaw('leave_type_id, COUNT(*) as count')
      ->groupBy('leave_type_id')
      ->pluck('count', 'leave_type_id');

    // Top users by leave days
    $topUsers = LeaveRequest::selectRaw('user_id, SUM(DATEDIFF(end_date, start_date) + 1) as days')
      ->groupBy('user_id')
      ->orderByDesc('days')
      ->take(5)
      ->with('user:id,name')
      ->get()
      ->map(fn($leave) => [
        'name' => $leave->user->name,
        'days' => $leave->days,
      ]);

    return response()->json([
      'monthly' => $monthly,
      'types' => $types,
      'topUsers' => $topUsers,
    ]);
  }
}
