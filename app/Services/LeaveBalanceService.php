<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;

class LeaveBalanceService
{
  public function getUsedDays(int $userId, int $leaveTypeId, int $year = null): int
  {
    $year = $year ?? now()->year;

    return LeaveRequest::query()
      ->where('user_id', $userId)
      ->where('leave_type_id', $leaveTypeId)
      ->whereYear('start_date', $year)
      ->whereIn('status', ['approved'])
      ->get()
      ->sum(function ($leave) {
        return now()
            ->parse($leave->start_date)
            ->diffInDaysFiltered(fn ($date) => !$date->isWeekend(), now()->parse($leave->end_date)) + 1;
      });
  }

  public function getRemainingDays(int $userId, int $leaveTypeId): int
  {
    $leaveType = LeaveType::findOrFail($leaveTypeId);
    $usedDays = $this->getUsedDays($userId, $leaveTypeId);

    return max(0, $leaveType->max_days_per_year - $usedDays);
  }

  public function hasSufficientBalance(int $userId, int $leaveTypeId, int $daysRequested): bool
  {
    return $this->getRemainingDays($userId, $leaveTypeId) >= $daysRequested;
  }
}
