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

  protected $fillable = [
    'name',
    'description',
    'max_days_per_year',
    'requires_documentation',
    'gender_specific',
    'gender',
    'frequency_years',
    'allow_negative_balance',
    'pay_percentage',
    'minimum_notice_days'
  ];

  protected $casts = [
    'requires_documentation' => 'boolean',
    'gender_specific' => 'boolean',
    'allow_negative_balance' => 'boolean',
    'max_days_per_year' => 'integer',
    'frequency_years' => 'integer',
    'pay_percentage' => 'decimal:2',
    'minimum_notice_days' => 'integer'
  ];

  public function leaveRequests(): HasMany
  {
    return $this->hasMany(LeaveRequest::class);
  }
}
