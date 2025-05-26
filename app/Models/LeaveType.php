<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
  /** @use HasFactory<\Database\Factories\LeaveTypeFactory> */
  use HasFactory, HasUuid;

  protected $fillable = ['name', 'description', 'max_days_per_year'];

  public function allocations(): HasMany
  {
    return $this->hasMany(LeaveAllocation::class);
  }

  public function leaveRequests(): HasMany
  {
    return $this->hasMany(LeaveRequest::class);
  }

}
