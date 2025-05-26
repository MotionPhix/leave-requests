<?php

namespace App\Http\Controllers\Admin;

use App\Events\LeaveRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
  public function index(): Response
  {
    $leaveRequests = LeaveRequest::with('leaveType', 'user')
      ->latest()
      ->get();

    return Inertia::render('admin/leave-requests/Index', [
      'leaveRequests' => $leaveRequests
    ]);
  }

  public function show(LeaveRequest $leaveRequest): Response|\Illuminate\Http\RedirectResponse
  {
    if (auth()->user()->can('view leave')) {

      return Inertia::render('admin/leave-requests/Show', [
        'leaveRequest' => $leaveRequest->load('leaveType', 'user'),
      ]);

    }

    return back();
  }

  public function approve(Request $request, LeaveRequest $leaveRequest): \Illuminate\Http\RedirectResponse
  {
    if ($request->user()->can('approve leave')) {

      $leaveRequest->update([
        'status' => 'approved',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
        'comment' => $leaveRequest->comment
      ]);

      // $request->user()->notify(new LeaveStatusChanged($request, $request->status));

      broadcast(new LeaveRequestUpdated($leaveRequest))->toOthers();

      return back()->with('success', 'Leave approved.');
    }

    return back()->with('error', 'You are not allowed to approve leaves');
  }

  public function reject(Request $request, LeaveRequest $leaveRequest): \Illuminate\Http\RedirectResponse
  {
    if ($request->user()->can('reject leave')) {

      $leaveRequest->update([
        'status' => 'rejected',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
        'comment' => $leaveRequest->comment
      ]);

      // auth()->user()->notify(new LeaveStatusChanged($request, $request->status));

      broadcast(new LeaveRequestUpdated($leaveRequest))->toOthers();

      return back()->with('success', 'Leave rejected.');
    }

    return back()->with('error', 'You are not allowed to reject leaves');
  }
}
