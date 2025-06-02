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

      $documentation = $leaveRequest->getFirstMedia('documentation');

      return Inertia::render('admin/leave-requests/Show', [
        'leaveRequest' => [
          'uuid' => $leaveRequest->uuid,
          'user' => [
            'name' => $leaveRequest->user->name,
            'email' => $leaveRequest->user->email,
            'position' => $leaveRequest->user->position,
            'department' => $leaveRequest->user->department,
          ],
          'leave_type' => [
            'name' => $leaveRequest->leaveType->name,
            'requires_documentation' => $leaveRequest->leaveType->requires_documentation,
          ],
          'start_date' => $leaveRequest->start_date->format('Y-m-d'),
          'end_date' => $leaveRequest->end_date->format('Y-m-d'),
          'reason' => $leaveRequest->reason,
          'status' => $leaveRequest->status,
          'comments' => $leaveRequest->comments,
          'documentation' => $documentation ? [
            'name' => $documentation->name,
            'size' => $documentation->size,
            'type' => $documentation->mime_type,
            'url' => $documentation->getUrl(),
          ] : null,
          'created_at' => $leaveRequest->created_at->format('Y-m-d H:i:s'),
          'updated_at' => $leaveRequest->updated_at->format('Y-m-d H:i:s'),
          'reviewed_by' => $leaveRequest->reviewer?->name,
          'reviewed_at' => $leaveRequest->reviewed_at?->format('Y-m-d H:i:s'),
        ]
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
