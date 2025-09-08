<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
  use HasFactory, HasUuid, Tenantable;

  protected $fillable = ['name', 'date', 'type', 'description', 'is_recurring'];

  protected $casts = [
    'date' => 'date',
    'is_recurring' => 'boolean',
  ];

  public function workspace(): BelongsTo
  {
    return $this->belongsTo(Workspace::class);
  }
}
