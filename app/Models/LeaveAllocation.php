<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAllocation extends Model
{
  /** @use HasFactory<\Database\Factories\LeaveAllocationFactory> */
  use HasFactory, HasUuid;

  protected $fillable = ['user_id', 'leave_type_id', 'year', 'allocated_days'];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function leaveType(): BelongsTo
  {
    return $this->belongsTo(LeaveType::class);
  }
}
