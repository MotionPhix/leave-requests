<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeIdSetting extends Model
{
  use HasFactory, HasUuid;

  protected $guarded = [];

  protected $casts = [
    'include_year' => 'boolean',
  ];
}
