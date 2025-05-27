<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  public function index(LeaveBalanceService $leaveBalanceService): Response
  {
    $requests = LeaveRequest::whereYear('created_at', now()->year)->get();

    // Get requests by type with percentage
    $byType = LeaveType::withCount('leaveRequests')
      ->get()
      ->map(function ($type) use ($requests) {
        $percentage = $requests->count() > 0
          ? round(($type->leave_requests_count / $requests->count()) * 100, 1)
          : 0;
        return [
          'name' => $type->name,
          'count' => $type->leave_requests_count,
          'percentage' => $percentage
        ];
      });

    // Get monthly stats
    $monthlyStats = $requests->groupBy(function ($request) {
      return $request->created_at->format('m');
    })->map->count();

    // Get top employees with most leaves
    $topEmployees = LeaveRequest::with('user')
      ->whereYear('created_at', now()->year)
      ->get()
      ->groupBy('user_id')
      ->map(function ($items) {
        $user = $items->first()->user;
        return [
          'name' => $user->name,
          'count' => $items->count(),
          'approved' => $items->where('status', 'approved')->count()
        ];
      })
      ->sortByDesc('count')
      ->take(5)
      ->values();

    $chartData = [
      'total' => $requests->count(),
      'approved' => $requests->where('status', 'approved')->count(),
      'rejected' => $requests->where('status', 'rejected')->count(),
      'pending' => $requests->where('status', 'pending')->count(),
      'byType' => $byType,
      'monthly' => $monthlyStats,
      'topEmployees' => $topEmployees
    ];

    return Inertia::render('admin/Dashboard', compact('chartData'));
  }
}
