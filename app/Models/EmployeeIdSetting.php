<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeIdSetting extends Model
{
  use HasFactory, HasUuid, Tenantable;

  protected $guarded = [];

  protected $casts = [
    'include_year' => 'boolean',
  ];

  public function workspace(): BelongsTo
  {
    return $this->belongsTo(Workspace::class);
  }
}
