<?php

namespace App\Http\Controllers\Employee\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
  public function index(Request $request)
  {
    $userId = Auth::id();

    $query = LeaveRequest::with(['leaveType'])->where('user_id', $userId);

    if ($request->has('status') && $request->status !== 'all') {
      $query->where('status', $request->status);
    }

    if ($request->has('type') && $request->type !== 'all') {
      $query->where('leave_type_id', $request->type);
    }

    if ($request->has('date_range') && $request->date_range) {
      $query->where('start_date', '>=', $request->date_range['start'])
        ->where('end_date', '<=', $request->date_range['end']);
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
            'period' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
            'range' => Carbon::parse($leave->start_date)->format('d M') . ' to ' . Carbon::parse($leave->end_date)->format('d M, Y'),
          ],
        ];
      });

    return response()->json($leaveRequests);
  }
}

