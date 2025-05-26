<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LeaveRequestPolicy
{
  public function view(User $user, LeaveRequest $leaveRequest): bool
  {
    return $user->id === $leaveRequest->user_id || $user->can('view leave');
  }

  public function approve(User $user, LeaveRequest $leaveRequest): bool
  {
    return $user->can('approve leave');
  }

  public function reject(User $user, LeaveRequest $leaveRequest): bool
  {
    return $user->can('reject leave');
  }
}
