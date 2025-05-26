<?php

namespace App\Http\Controllers\Employee\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
  public function index()
  {
    $userId = Auth::id();

    // Fetch leave requests with leaveType relationship
    $leaveRequests = LeaveRequest::where('user_id', $userId)
      ->with('leaveType') // eager load leaveType
      ->get(['id', 'leave_type_id', 'comment', 'reason', 'start_date', 'end_date', 'status']);

    // Transform into FullCalendar format
    $events = $leaveRequests->map(function ($leave) {
      return [
        'id' => $leave->id,
        'title' => $leave->leaveType->name ?? 'Leave', // fallback if missing
        'start' => $leave->start_date,
        'end' => $leave->end_date,
        'color' => $leave->status === 'approved' ? '#22c55e' : '#f97316',
        'reason' => $leave->reason,
        'comment' => $leave->comment,
        'extendedProps' => [
          'status' => $leave->status,
          'type' => $leave->leaveType->name ?? 'Leave',
        ],
      ];
    });

    return response()->json($events);
  }
}

