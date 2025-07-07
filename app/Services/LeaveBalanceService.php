<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Enums\LeaveStatus;
use Carbon\Carbon;

class LeaveBalanceService
{
  public function getUsedDays(int $userId, int $leaveTypeId, ?int $year = null): int
  {
    $year = $year ?? now()->year;

    return LeaveRequest::query()
      ->where('user_id', $userId)
      ->where('leave_type_id', $leaveTypeId)
      ->whereYear('start_date', $year)
      ->where('status', LeaveStatus::Approved->value)
      ->get()
      ->sum(function ($leave) {
        return $this->calculateWorkingDays($leave->start_date, $leave->end_date);
      });
  }

  public function getRemainingDays(int $userId, int $leaveTypeId): int
  {
    $leaveType = LeaveType::findOrFail($leaveTypeId);
    $usedDays = $this->getUsedDays($userId, $leaveTypeId);

    return max(0, $leaveType->max_days_per_year - $usedDays);
  }

  public function calculateWorkingDays(string $startDate, string $endDate): float
  {
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);

    return $start->diffInDaysFiltered(function (Carbon $date) {
        return !$date->isWeekend() && !$this->isHoliday($date);
      }, $end) + 1;
  }

  public function hasSufficientBalance(int $userId, int $leaveTypeId, int $daysRequested): bool
  {
    return $this->getRemainingDays($userId, $leaveTypeId) >= $daysRequested;
  }

  /**
   * Check if user can request new leave based on their latest leave status
   */
  public function canRequestNewLeave(int $userId): array
  {
    $latestLeave = LeaveRequest::query()
      ->where('user_id', $userId)
      ->latest('created_at')
      ->first();

    // If no previous leave requests, allow new request
    if (!$latestLeave) {
      return [
        'can_request' => true,
        'reason' => null,
        'latest_leave' => null
      ];
    }

    $today = Carbon::today();
    $status = $latestLeave->status;

    // Check if latest leave is in a blocking state
    $blockingStatuses = [
      LeaveStatus::Pending->value,
      LeaveStatus::Reviewed->value,
      LeaveStatus::Rescheduled->value
    ];

    // If latest leave is pending, reviewed, or rescheduled - block new requests
    if (in_array($status, $blockingStatuses)) {
      return [
        'can_request' => false,
        'reason' => $this->getBlockingReason($status),
        'latest_leave' => $latestLeave
      ];
    }

    // If latest leave is approved and still running or in the future - block new requests
    if ($status === LeaveStatus::Approved->value) {
      $leaveEndDate = Carbon::parse($latestLeave->end_date);

      if ($leaveEndDate->greaterThanOrEqualTo($today)) {
        return [
          'can_request' => false,
          'reason' => $leaveEndDate->isToday()
            ? 'You have an approved leave request ending today. Please wait until tomorrow to request new leave.'
            : "You have an approved leave request running until {$leaveEndDate->format('M d, Y')}. You cannot request new leave while on approved leave.",
          'latest_leave' => $latestLeave
        ];
      }
    }

    // If latest leave is rejected or cancelled, or approved but completed - allow new request
    return [
      'can_request' => true,
      'reason' => null,
      'latest_leave' => $latestLeave
    ];
  }

  /**
   * Check if user has any active (running or future) approved leave
   */
  public function hasActiveApprovedLeave(int $userId): bool
  {
    $today = Carbon::today();

    return LeaveRequest::query()
      ->where('user_id', $userId)
      ->where('status', LeaveStatus::Approved->value)
      ->where('end_date', '>=', $today)
      ->exists();
  }

  /**
   * Check if user has any pending or under review leave requests
   */
  public function hasPendingLeaveRequests(int $userId): bool
  {
    $pendingStatuses = [
      LeaveStatus::Pending->value,
      LeaveStatus::Reviewed->value,
      LeaveStatus::Rescheduled->value
    ];

    return LeaveRequest::query()
      ->where('user_id', $userId)
      ->whereIn('status', $pendingStatuses)
      ->exists();
  }

  /**
   * Get all active leave requests for a user (pending, approved running/future)
   */
  public function getActiveLeaveRequests(int $userId)
  {
    $today = Carbon::today();

    return LeaveRequest::query()
      ->where('user_id', $userId)
      ->where(function ($query) use ($today) {
        // Pending, reviewed, or rescheduled requests
        $query->whereIn('status', [
          LeaveStatus::Pending->value,
          LeaveStatus::Reviewed->value,
          LeaveStatus::Rescheduled->value
        ])
          // OR approved requests that are still running or in the future
          ->orWhere(function ($subQuery) use ($today) {
            $subQuery->where('status', LeaveStatus::Approved->value)
              ->where('end_date', '>=', $today);
          });
      })
      ->orderBy('start_date')
      ->get();
  }

  /**
   * Check for overlapping leave requests (excluding rejected and cancelled)
   */
  public function hasOverlappingLeave(int $userId, string $startDate, string $endDate, ?int $excludeRequestId = null): bool
  {
    $query = LeaveRequest::query()
      ->where('user_id', $userId)
      ->whereNotIn('status', [
        LeaveStatus::Rejected->value,
        LeaveStatus::Cancelled->value
      ])
      ->where(function ($query) use ($startDate, $endDate) {
        $query->whereBetween('start_date', [$startDate, $endDate])
          ->orWhereBetween('end_date', [$startDate, $endDate])
          ->orWhere(function ($subQuery) use ($startDate, $endDate) {
            $subQuery->where('start_date', '<=', $startDate)
              ->where('end_date', '>=', $endDate);
          });
      });

    if ($excludeRequestId) {
      $query->where('id', '!=', $excludeRequestId);
    }

    return $query->exists();
  }

  /**
   * Get user's leave summary for dashboard
   */
  public function getUserLeaveSummary(int $userId): array
  {
    $canRequest = $this->canRequestNewLeave($userId);
    $activeRequests = $this->getActiveLeaveRequests($userId);

    return [
      'can_request_new_leave' => $canRequest['can_request'],
      'blocking_reason' => $canRequest['reason'],
      'latest_leave' => $canRequest['latest_leave'],
      'active_requests_count' => $activeRequests->count(),
      'active_requests' => $activeRequests,
      'has_pending_requests' => $this->hasPendingLeaveRequests($userId),
      'has_active_approved_leave' => $this->hasActiveApprovedLeave($userId)
    ];
  }

  protected function getBlockingReason(string $status): string
  {
    return match ($status) {
      LeaveStatus::Pending->value => 'You have a pending leave request. Please wait for it to be reviewed before requesting new leave.',
      LeaveStatus::Reviewed->value => 'You have a leave request under review. Please wait for the review process to complete before requesting new leave.',
      LeaveStatus::Rescheduled->value => 'You have a rescheduled leave request. Please wait for it to be finalized before requesting new leave.',
      default => 'You cannot request new leave at this time due to an existing active leave request.'
    };
  }

  protected function isHoliday(Carbon $date): bool
  {
    return \App\Models\Holiday::where('date', $date->format('Y-m-d'))->exists();

    /*
    static $holidays = null;

        // Cache holidays for the current request
        if ($holidays === null) {
            $holidays = \App\Models\Holiday::pluck('date')
                ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                ->flip()
                ->toArray();
        }

        return isset($holidays[$date->format('Y-m-d')]);
        */
  }
}
