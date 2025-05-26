<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveCalendarController extends Controller
{
  public function index(Request $request): \Illuminate\Http\JsonResponse
  {
    $leaves = LeaveRequest::where('status', 'approved')
      ->get(['id', 'start_date', 'end_date', 'type', 'user_id']);

    $events = $leaves->map(function ($leave) {
      return [
        'title' => "{$leave->type} - {$leave->user->name}",
        'start' => $leave->start_date,
        'end' => $leave->end_date,
        'color' => $this->getColorForType($leave->type),
      ];
    });

    return response()->json($events);
  }

  private function getColorForType($type): string
  {
    return match($type) {
      'Annual' => '#1e40af',
      'Sick' => '#dc2626',
      default => '#10b981',
    };
  }
}
