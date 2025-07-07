<?php

namespace App\Http\Controllers\Employee\Api;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
  public function index(Request $request)
  {
    $userId = Auth::id();

    $query = LeaveRequest::with(['leaveType', 'user'])
      ->where('user_id', $userId);

    if ($request->has('status') && $request->status !== 'all') {
      $query->where('status', $request->status);
    }

    if ($request->has('type') && $request->type !== 'all') {
      $query->where('leave_type_id', $request->type);
    }

    if ($request->has('start_date') && $request->has('end_date')) {
      $query->where(function ($q) {
        $q->whereBetween('start_date', [$request('start_date'), $request('end_date')])
          ->orWhereBetween('end_date', [$dateRange['start'], $dateRange['end']])
          ->orWhere(function ($subQ) use ($dateRange) {
            $subQ->where('start_date', '<=', $dateRange['start'])
              ->where('end_date', '>=', $dateRange['end']);
          });
      });
    }

    // Search filter
    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('reason', 'like', "%{$search}%")
          ->orWhereHas('leaveType', function ($typeQuery) use ($search) {
            $typeQuery->where('name', 'like', "%{$search}%");
          });
      });
    }

    $leaveRequests = $query->orderBy('start_date')->get();

    $events = $leaveRequests->map(function ($leave) {
      $status = $leave->status;
      $color = $leave->leaveType->color ?? $this->getStatusColor($status);

      return [
        'id' => $leave->id,
        'title' => "{$leave->leaveType->name} - {$leave->user->name}",
        'start' => $leave->start_date->format('Y-m-d'),
        'end' => $leave->end_date->addDay()->format('Y-m-d'),
        'color' => $color,
        'textColor' => $this->getTextColor($color),
        'allDay' => true,
        'extendedProps' => [
          'status' => $leave->status,
          'type' => $leave->leaveType->name,
          'reason' => $leave->reason,
          'comment' => $leave->comment,
          'period' => Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1,
          'range' => Carbon::parse($leave->start_date)->format('d M') . ' to ' . Carbon::parse($leave->end_date)->format('d M, Y'),
          'user_name' => $leave->user->name,
          'leave_type_id' => $leave->leave_type_id,
          'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
          'can_cancel' => $leave->canBeCancelled(),
        ],
      ];
    });

    return response()->json($leaveRequests);
  }

  /**
   * Get calendar statistics
   */
  public function statistics(Request $request): JsonResponse
  {
    $userId = Auth::id();
    $currentYear = now()->year;

    $query = LeaveRequest::where('user_id', $userId)
      ->whereYear('start_date', $currentYear);

    $stats = [
      'total_requests' => $query->count(),
      'pending_requests' => $query->clone()->where('status', LeaveStatus::Pending->value)->count(),
      'approved_requests' => $query->clone()->where('status', LeaveStatus::Approved->value)->count(),
      'rejected_requests' => $query->clone()->where('status', LeaveStatus::Rejected->value)->count(),
      'cancelled_requests' => $query->clone()->where('status', LeaveStatus::Cancelled->value)->count(),
      'total_days_taken' => $this->calculateTotalDaysTaken($userId, $currentYear),
      'upcoming_leaves' => $this->getUpcomingLeavesCount($userId),
      'current_month_requests' => $this->getCurrentMonthRequestsCount($userId),
      'leave_balance_summary' => $this->getLeaveBalanceSummary($userId),
    ];

    return response()->json($stats);
  }

  /**
   * Export calendar data in various formats
   */
  public function export(Request $request, string $format)
  {
    $userId = Auth::id();

    $query = LeaveRequest::with(['leaveType', 'user'])
      ->where('user_id', $userId);

    $this->applyFilters($query, $request);

    $leaveRequests = $query->orderBy('start_date')->get();

    switch (strtolower($format)) {
      case 'ics':
        return $this->exportToICS($leaveRequests);
      case 'csv':
        return $this->exportToCSV($leaveRequests);
      case 'pdf':
        return $this->exportToPDF($leaveRequests);
      default:
        return response()->json(['error' => 'Unsupported format'], 400);
    }
  }

  /**
   * Get leave types for filtering
   */
  public function leaveTypes(): JsonResponse
  {
    $leaveTypes = LeaveType::select('id', 'name', 'color')
      ->orderBy('name')
      ->get();

    return response()->json($leaveTypes);
  }

  /**
   * Get upcoming holidays for calendar display
   */
  public function holidays(Request $request): JsonResponse
  {
    $startDate = $request->get('start', now()->startOfMonth());
    $endDate = $request->get('end', now()->endOfMonth()->addMonths(2));

    $holidays = \App\Models\Holiday::whereBetween('date', [$startDate, $endDate])
      ->orderBy('date')
      ->get()
      ->map(function ($holiday) {
        return [
          'id' => 'holiday-' . $holiday->id,
          'title' => $holiday->name,
          'start' => $holiday->date,
          'end' => $holiday->date,
          'allDay' => true,
          'display' => 'background',
          'color' => '#e5e7eb',
          'textColor' => '#374151',
          'extendedProps' => [
            'type' => 'holiday',
            'description' => $holiday->description,
          ]
        ];
      });

    return response()->json($holidays);
  }

  /**
   * Get status-based color
   */
  private function getStatusColor(string $status): string
  {
    return match ($status) {
      LeaveStatus::Approved->value => '#22c55e',
      LeaveStatus::Rejected->value => '#ef4444',
      LeaveStatus::Pending->value => '#f59e0b',
      LeaveStatus::Cancelled->value => '#6b7280',
      LeaveStatus::Reviewed->value => '#8b5cf6',
      LeaveStatus::Rescheduled->value => '#06b6d4',
      default => '#6b7280',
    };
  }

  /**
   * Get appropriate text color for background
   */
  private function getTextColor(string $backgroundColor): string
  {
    // Simple logic - you might want to use a more sophisticated algorithm
    return '#ffffff';
  }

  /**
   * Calculate total days taken in a year
   */
  private function calculateTotalDaysTaken(int $userId, int $year): int
  {
    return LeaveRequest::where('user_id', $userId)
      ->where('status', LeaveStatus::Approved->value)
      ->whereYear('start_date', $year)
      ->get()
      ->sum(function ($leave) {
        return $leave->start_date->diffInDays($leave->end_date) + 1;
      });
  }

  /**
   * Get count of upcoming approved leaves
   */
  private function getUpcomingLeavesCount(int $userId): int
  {
    return LeaveRequest::where('user_id', $userId)
      ->where('status', LeaveStatus::Approved->value)
      ->where('start_date', '>', now())
      ->count();
  }

  /**
   * Get current month requests count
   */
  private function getCurrentMonthRequestsCount(int $userId): int
  {
    return LeaveRequest::where('user_id', $userId)
      ->whereMonth('start_date', now()->month)
      ->whereYear('start_date', now()->year)
      ->count();
  }

  /**
   * Get leave balance summary
   */
  private function getLeaveBalanceSummary(int $userId): array
  {
    $leaveTypes = LeaveType::all();
    $currentYear = now()->year;

    return $leaveTypes->map(function ($leaveType) use ($userId, $currentYear) {
      $usedDays = LeaveRequest::where('user_id', $userId)
        ->where('leave_type_id', $leaveType->id)
        ->where('status', LeaveStatus::Approved->value)
        ->whereYear('start_date', $currentYear)
        ->get()
        ->sum(function ($leave) {
          return $leave->start_date->diffInDays($leave->end_date) + 1;
        });

      return [
        'leave_type' => $leaveType->name,
        'max_days' => $leaveType->max_days_per_year,
        'used_days' => $usedDays,
        'remaining_days' => max(0, $leaveType->max_days_per_year - $usedDays),
        'color' => $leaveType->color,
      ];
    })->toArray();
  }

  /**
   * Export to ICS format
   */
  private function exportToICS($leaveRequests): Response
  {
    $icsContent = "BEGIN:VCALENDAR\r\n";
    $icsContent .= "VERSION:2.0\r\n";
    $icsContent .= "PRODID:-//Your Company//Leave Calendar//EN\r\n";
    $icsContent .= "CALSCALE:GREGORIAN\r\n";

    foreach ($leaveRequests as $leave) {
      $icsContent .= "BEGIN:VEVENT\r\n";
      $icsContent .= "UID:" . $leave->id . "@yourcompany.com\r\n";
      $icsContent .= "DTSTART;VALUE=DATE:" . $leave->start_date->format('Ymd') . "\r\n";
      $icsContent .= "DTEND;VALUE=DATE:" . $leave->end_date->addDay()->format('Ymd') . "\r\n";
      $icsContent .= "SUMMARY:" . $leave->leaveType->name . " - " . ucfirst($leave->status) . "\r\n";
      $icsContent .= "DESCRIPTION:" . ($leave->reason ?: 'No reason provided') . "\r\n";
      $icsContent .= "STATUS:" . strtoupper($leave->status) . "\r\n";
      $icsContent .= "CREATED:" . $leave->created_at->format('Ymd\THis\Z') . "\r\n";
      $icsContent .= "LAST-MODIFIED:" . $leave->updated_at->format('Ymd\THis\Z') . "\r\n";
      $icsContent .= "END:VEVENT\r\n";
    }

    $icsContent .= "END:VCALENDAR\r\n";

    return response($icsContent)
      ->header('Content-Type', 'text/calendar; charset=utf-8')
      ->header('Content-Disposition', 'attachment; filename="leave-calendar.ics"');
  }

  /**
   * Export to CSV format
   */
  private function exportToCSV($leaveRequests): Response
  {
    $csvData = [];
    $csvData[] = [
      'Leave Type',
      'Start Date',
      'End Date',
      'Duration (Days)',
      'Status',
      'Reason',
      'Comments',
      'Created Date'
    ];

    foreach ($leaveRequests as $leave) {
      $csvData[] = [
        $leave->leaveType->name,
        $leave->start_date->format('Y-m-d'),
        $leave->end_date->format('Y-m-d'),
        $leave->start_date->diffInDays($leave->end_date) + 1,
        ucfirst($leave->status),
        $leave->reason ?: 'N/A',
        $leave->comment ?: 'N/A',
        $leave->created_at->format('Y-m-d H:i:s')
      ];
    }

    $output = fopen('php://temp', 'r+');
    foreach ($csvData as $row) {
      fputcsv($output, $row);
    }
    rewind($output);
    $csvContent = stream_get_contents($output);
    fclose($output);

    return response($csvContent)
      ->header('Content-Type', 'text/csv')
      ->header('Content-Disposition', 'attachment; filename="leave-calendar.csv"');
  }

  /**
   * Export to PDF format (requires dompdf or similar)
   */
  private function exportToPDF($leaveRequests): Response
  {
    // This would require a PDF library like dompdf
    // For now, return a simple implementation

    $html = '<h1>Leave Calendar Report</h1>';
    $html .= '<table border="1" style="width: 100%; border-collapse: collapse;">';
    $html .= '<tr><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Duration</th><th>Status</th><th>Reason</th></tr>';

    foreach ($leaveRequests as $leave) {
      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($leave->leaveType->name) . '</td>';
      $html .= '<td>' . $leave->start_date->format('Y-m-d') . '</td>';
      $html .= '<td>' . $leave->end_date->format('Y-m-d') . '</td>';
      $html .= '<td>' . ($leave->start_date->diffInDays($leave->end_date) + 1) . ' days</td>';
      $html .= '<td>' . ucfirst($leave->status) . '</td>';
      $html .= '<td>' . htmlspecialchars($leave->reason ?: 'N/A') . '</td>';
      $html .= '</tr>';
    }

    $html .= '</table>';

    // If you have dompdf installed:
    // $pdf = \PDF::loadHTML($html);
    // return $pdf->download('leave-calendar.pdf');

    // For now, return HTML
    return response($html)
      ->header('Content-Type', 'text/html')
      ->header('Content-Disposition', 'attachment; filename="leave-calendar.html"');
  }

  /**
   * Apply filters to the query
   */
  private function applyFilters($query, Request $request): void
  {
    // Status filter
    if ($request->has('status') && $request->status !== 'all') {
      $query->where('status', $request->status);
    }

    // Leave type filter
    if ($request->has('type') && $request->type !== 'all') {
      $query->where('leave_type_id', $request->type);
    }

    // Date range filter
    if ($request->has('date_range') && is_array($request->date_range)) {
      $dateRange = $request->date_range;
      if (!empty($dateRange['start']) && !empty($dateRange['end'])) {
        $query->where(function ($q) use ($dateRange) {
          $q->whereBetween('start_date', [$dateRange['start'], $dateRange['end']])
            ->orWhereBetween('end_date', [$dateRange['start'], $dateRange['end']])
            ->orWhere(function ($subQ) use ($dateRange) {
              $subQ->where('start_date', '<=', $dateRange['start'])
                ->where('end_date', '>=', $dateRange['end']);
            });
        });
      }
    }

    // Search filter
    if ($request->has('search') && !empty($request->search)) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('reason', 'like', "%{$search}%")
          ->orWhereHas('leaveType', function ($typeQuery) use ($search) {
            $typeQuery->where('name', 'like', "%{$search}%");
          });
      });
    }
  }
}

