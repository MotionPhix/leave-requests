<?php

namespace App\Enums;

enum LeaveStatus: string
{
  case Pending = 'pending';
  case Approved = 'approved';
  case Rejected = 'rejected';
  case Cancelled = 'cancelled';
  case Rescheduled = 'rescheduled';
}
