<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
  use HasFactory, HasUuid;

  protected $fillable = ['name', 'date', 'type', 'description', 'is_recurring'];

  protected $casts = [
    'date' => 'date',
    'is_recurring' => 'boolean',
  ];
}
