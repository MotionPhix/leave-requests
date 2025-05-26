<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
  public function index(Request $request)
  {
    $query = LeaveRequest::with('user');

    if ($request->has('status') && $request->status !== 'all') {
      $query->where('status', $request->status);
    }

    if ($request->has('type') && $request->type !== 'all') {
      $query->where('leave_type_id', $request->type);
    }

    if ($request->has('user_id') && $request->user_id !== 'all') {
      $query->where('user_id', $request->user_id);
    }

    $leaveRequests = $query->get()
      ->map(function ($leave) {
        return [
          'id' => $leave->id,
          'title' => "{$leave->leave_type_id} - {$leave->user->name}",
          'start' => $leave->start_date,
          'end' => $leave->end_date,
          'color' => match ($leave->status) {
            'approved' => '#4CAF50',   // Green
            'rejected' => '#F44336',   // Red
            'pending' => '#FFC107',    // Yellow
          },
          'extendedProps' => [
            'status' => $leave->status,
            'reason' => $leave->reason,
          ],
        ];
      });

    return response()->json($leaveRequests);
  }

  public function update(Request $request, LeaveRequest $leaveRequest): \Illuminate\Http\JsonResponse
  {
    $request->validate([
      'start' => 'required|date',
      'end' => 'required|date|after_or_equal:start',
    ]);

    // Update dates
    $leaveRequest->update([
      'start_date' => $request->start,
      'end_date' => $request->end,
    ]);

    return response()->json(['message' => 'Leave dates updated.']);
  }
}
