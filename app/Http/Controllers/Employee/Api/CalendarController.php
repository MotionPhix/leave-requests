<?php

namespace App\Http\Controllers\Employee\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
  public function index()
  {
    $leaveRequests = LeaveRequest::where('user_id', Auth::id())->get();

    $events = $leaveRequests->map(function ($leave) {
      return [
        'id' => $leave->id,
        'title' => "{$leave->type}",
        'start' => $leave->start_date,
        'end' => $leave->end_date,
        'color' => $leave->status === 'approved' ? '#1e40af' : '#fbbf24',
        'extendedProps' => [
          'status' => $leave->status,
          'reason' => $leave->reason,
        ],
      ];
    });

    return response()->json($events);
  }
}

