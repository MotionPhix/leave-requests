<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
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
      ->where('status', 'approved')
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

  protected function calculateWorkingDays(string $startDate, string $endDate): float
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
