<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;

class CalendarController extends Controller
{
  public function index()
  {
    $leaveRequests = LeaveRequest::with('user')->get();

    // Map to FullCalendar event format
    $events = $leaveRequests->map(function ($leave) {
      return [
        'id' => $leave->id,
        'title' => "{$leave->type} - {$leave->user->name}",
        'start' => $leave->start_date,
        'end' => $leave->end_date,
        'color' => $leave->status === 'approved' ? '#1e40af' : '#fbbf24', // Example color
        'extendedProps' => [
          'status' => $leave->status,
          'reason' => $leave->reason,
        ],
      ];
    });

    return response()->json($events);
  }
}
