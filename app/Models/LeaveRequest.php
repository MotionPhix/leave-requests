<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
  /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
  use HasFactory, HasUuid;

  protected $fillable = [
    'user_id', 'leave_type_id', 'start_date', 'end_date',
    'reason', 'status', 'approved_by', 'comment', 'approved_at'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function leaveType(): BelongsTo
  {
    return $this->belongsTo(LeaveType::class);
  }

  public function approver(): BelongsTo
  {
    return $this->belongsTo(User::class, 'approved_by');
  }

  public function reviewer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }
}
