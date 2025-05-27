<?php

namespace App\Http\Controllers\Admin;

use App\Events\LeaveRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
  public function index(Request $request): Response
  {
    $query = LeaveRequest::with(['leaveType', 'user'])
      ->when($request->status, function ($query, $status) {
        return $query->where('status', $status);
      })
      ->when($request->search, function ($query, $search) {
        return $query->whereHas('user', function ($q) use ($search) {
          $q->where('name', 'like', "%{$search}%");
        });
      })
      ->when($request->type, function ($query, $type) {
        return $query->where('leave_type_id', $type);
      })
      ->latest();

    $leaveRequests = $query->paginate(10)->withQueryString();
    $leaveTypes = LeaveType::all();

    return Inertia::render('admin/leave-requests/Index', [
      'leaveRequests' => $leaveRequests,
      'leaveTypes' => $leaveTypes,
      'filters' => $request->only(['search', 'status', 'type'])
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
