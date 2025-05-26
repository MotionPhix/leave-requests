<?php

namespace App\Http\Controllers\Admin;

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
    $requests = LeaveRequest::all();

    $byType = LeaveType::withCount('leaveRequests')->get()->map(function($type) {
      return ['name' => $type->name, 'count' => $type->leave_requests_count];
    });

    $chartData = [
      'total' => $requests->count(),
      'approved' => $requests->where('status', 'approved')->count(),
      'rejected' => $requests->where('status', 'rejected')->count(),
      'pending' => $requests->where('status', 'pending')->count(),
      'byType' => $byType,
    ];

    return Inertia::render('admin/Dashboard', compact('chartData'));
  }
}
