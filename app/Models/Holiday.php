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

  protected $fillable = [
    'name', 
    'date', 
    'start_date', 
    'end_date', 
    'type', 
    'description', 
    'is_recurring', 
    'recurrence_pattern',
    'color',
    'is_visible_to_employees'
  ];

  protected $casts = [
    'date' => 'date',
    'start_date' => 'date',
    'end_date' => 'date',
    'is_recurring' => 'boolean',
    'is_visible_to_employees' => 'boolean',
  ];

  public function workspace(): BelongsTo
  {
    return $this->belongsTo(Workspace::class);
  }

  /**
   * Check if holiday spans multiple days
   */
  public function isMultiDay(): bool
  {
    return $this->start_date?->ne($this->end_date) ?? false;
  }

  /**
   * Get duration in days
   */
  public function getDurationInDays(): int
  {
    if (!$this->start_date || !$this->end_date) {
      return 1;
    }
    
    return $this->start_date->diffInDays($this->end_date) + 1;
  }

  /**
   * Scope for visible holidays based on user role
   */
  public function scopeVisibleToRole($query, string $role)
  {
    if ($role === 'employee') {
      return $query->where('is_visible_to_employees', true);
    }
    
    return $query; // Managers, HR, and Owners can see all holidays
  }

  /**
   * Scope for date range filtering
   */
  public function scopeInDateRange($query, $start = null, $end = null)
  {
    return $query->when($start, fn($q) => $q->where('end_date', '>=', $start))
                 ->when($end, fn($q) => $q->where('start_date', '<=', $end));
  }

  /**
   * Get formatted date range for display
   */
  public function getDateRangeAttribute(): string
  {
    if (!$this->start_date) {
      return $this->date?->format('M j, Y') ?? '';
    }

    if ($this->isMultiDay()) {
      return $this->start_date->format('M j') . ' - ' . $this->end_date->format('M j, Y');
    }

    return $this->start_date->format('M j, Y');
  }
}
