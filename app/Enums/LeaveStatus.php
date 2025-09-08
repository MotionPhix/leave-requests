<?php

namespace App\Enums;

enum LeaveStatus: string
{
  case Pending = 'pending';
  case Approved = 'approved';
  case Rejected = 'rejected';
  case Cancelled = 'cancelled';
  case Rescheduled = 'rescheduled';
  case Reviewed = 'reviewed';

  public function label(): string
  {
    return match ($this) {
      self::Pending => 'Pending',
      self::Approved => 'Approved',
      self::Rejected => 'Rejected',
      self::Cancelled => 'Cancelled',
      self::Rescheduled => 'Rescheduled',
      self::Reviewed => 'Reviewed',
    };
  }

  public function color(): string
  {
    return match ($this) {
      self::Pending => 'warning',
      self::Approved => 'success',
      self::Rejected => 'danger',
      self::Cancelled => 'neutral',
      self::Rescheduled => 'info',
      self::Reviewed => 'primary',
    };
  }
}
