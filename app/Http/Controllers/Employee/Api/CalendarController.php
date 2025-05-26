<?php

namespace App\Http\Controllers\Employee\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
  public function index()
  {
    $userId = Auth::id();

    $leaveRequests = LeaveRequest::where('user_id', $userId)
      ->with('leaveType')
      ->get(['id', 'leave_type_id', 'comment', 'reason', 'start_date', 'end_date', 'status']);

    // Transform into FullCalendar format
    $events = $leaveRequests->map(function ($leave) {
      return [
        'id' => $leave->id,
        'title' => $leave->leaveType->name ?? 'Leave', // fallback if missing
        'start' => $leave->start_date,
        'end' => $leave->end_date,
        'color' => $leave->leaveType->color,
        'reason' => $leave->reason,
        'comment' => $leave->comment,
        'extendedProps' => [
          'status'  => $leave->status,
          'type'    => $leave->leaveType->name,
          'reason'  => $leave->reason,
          'comment' => $leave->comment,
          'dates' => Carbon::parse($leave->start_date)->format('d M, Y') . " — " . Carbon::parse($leave->end_date)->format('d M, Y'),
        ],
      ];
    });

    return response()->json($events);
  }
}

