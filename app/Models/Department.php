<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
  use HasFactory, HasUuid;

  protected $fillable = [
    'name',
    'code',
    'description',
    'parent_id',
    'manager_id',
    'is_active'
  ];

  protected $casts = [
    'is_active' => 'boolean'
  ];

  /**
   * Get all users in this department
   */
  public function employees(): HasMany
  {
    return $this->hasMany(User::class, 'department');
  }

  /**
   * Get the department manager
   */
  public function manager(): BelongsTo
  {
    return $this->belongsTo(User::class, 'manager_id');
  }

  /**
   * Get the parent department
   */
  public function parent(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'parent_id');
  }

  /**
   * Get sub-departments
   */
  public function children(): HasMany
  {
    return $this->hasMany(Department::class, 'parent_id');
  }

  /**
   * Scope to get only active departments
   */
  #[Scope]
  public function active($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Get all employees count including sub-departments
   */
  public function totalEmployees(): Attribute
  {
    return Attribute::make(
      get: function () {
        $count = $this->employees()->count();

        foreach ($this->children as $child) {
          $count += $child->totalEmployees->value;
        }

        return $count;
      }
    );
  }
}
