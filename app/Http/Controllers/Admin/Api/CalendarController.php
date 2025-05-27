<?php

namespace App\Http\Controllers\Admin\Api;

use App\Events\LeaveRequestUpdated;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CalendarController extends Controller
{
  public function index(Request $request)
  {
    $query = LeaveRequest::with(['user', 'leaveType']);

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
          'title' => "{$leave->leaveType->name} - {$leave->user->name}",
          'start' => $leave->start_date,
          'end' => $leave->end_date,
          'color' => $leave->leaveType->color ?? match ($leave->status) {
            'approved' => '#4CAF50',
            'rejected' => '#F44336',
            'pending' => '#FFC107',
          },
          'extendedProps' => [
            'status' => $leave->status,
            'type' => $leave->leaveType->name,
            'reason' => $leave->reason,
            'comment' => $leave->comment,
            'user' => $leave->user->name,
            'period' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
            'range' => Carbon::parse($leave->start_date)->format('d M') . ' to ' . Carbon::parse($leave->end_date)->format('d M, Y'),
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

    // Check for overlapping approved leaves
    $overlappingLeave = LeaveRequest::where('user_id', $leaveRequest->user_id)
      ->where('id', '!=', $leaveRequest->id)
      ->where('status', 'approved')
      ->where(function ($query) use ($request) {
        $query->whereBetween('start_date', [$request->start, $request->end])
          ->orWhereBetween('end_date', [$request->start, $request->end])
          ->orWhere(function ($q) use ($request) {
            $q->where('start_date', '<=', $request->start)
              ->where('end_date', '>=', $request->end);
          });
      })
      ->first();

    if ($overlappingLeave) {
      throw ValidationException::withMessages([
        'date' => ['The selected dates overlap with an existing approved leave.']
      ]);
    }

    // Update dates
    $leaveRequest->update([
      'start_date' => $request->start,
      'end_date' => $request->end,
      'adjusted_by' => auth()->id(),
      'adjusted_at' => now(),
    ]);

    broadcast(new LeaveRequestUpdated($leaveRequest))->toOthers();

    return response()->json([
      'message' => 'Leave dates updated successfully.',
      'leave' => $leaveRequest->fresh()->load(['user', 'leaveType'])
    ]);
  }
}
