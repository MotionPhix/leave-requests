<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'workspace_id',
        'created_by',
        'title',
        'description',
        'type',
        'location',
        'color',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'is_recurring',
        'recurrence_data',
        'attendees',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'all_day' => 'boolean',
            'is_recurring' => 'boolean',
            'is_mandatory' => 'boolean',
            'recurrence_data' => 'array',
            'attendees' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->uuid)) {
                $event->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForWorkspace($query, $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($sq) use ($startDate, $endDate) {
                  $sq->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors & Mutators
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getFormattedDateAttribute(): string
    {
        if ($this->all_day) {
            if ($this->end_date && $this->start_date != $this->end_date) {
                return $this->start_date->format('M j') . ' - ' . $this->end_date->format('M j, Y');
            }
            return $this->start_date->format('M j, Y');
        }

        $startDateTime = $this->start_date->format('M j, Y');
        if ($this->start_time) {
            $startDateTime .= ' at ' . $this->start_time->format('g:i A');
        }
        if ($this->end_time) {
            $startDateTime .= ' - ' . $this->end_time->format('g:i A');
        }

        return $startDateTime;
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'meeting' => '#10b981', // green
            'announcement' => '#3b82f6', // blue
            'training' => '#f59e0b', // amber
            'social' => '#ec4899', // pink
            'other' => '#6b7280', // gray
            default => $this->color ?? '#3b82f6'
        };
    }
}
