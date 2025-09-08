<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use App\Notifications\LeaveRequestUpdated;
use App\Notifications\LeaveRequestSubmitted;
use App\Traits\HasUuid;
use App\Traits\Tenantable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LeaveRequest extends Model implements HasMedia
{
  /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
  use HasFactory, HasUuid, Tenantable, InteractsWithMedia;

  protected $fillable = [
    'user_id',
    'leave_type_id',
    'start_date',
    'end_date',
    'reason',
    'status',
    'comment',
    'reviewed_by',
    'reviewed_at',
    'cancelled_at',
    'cancellation_reason'
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'reviewed_at' => 'datetime',
    'cancelled_at' => 'datetime',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function leaveType(): BelongsTo
  {
    return $this->belongsTo(LeaveType::class);
  }

  public function reviewer(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reviewed_by');
  }

  public function workspace(): BelongsTo
  {
    return $this->belongsTo(Workspace::class);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('supporting_documents')
      ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
      ->maxFileSize(5 * 1024 * 1024); // 5MB
  }

  public function canBeCancelled(): bool
  {
    return $this->status === LeaveStatus::Pending->value;
  }

  public function cancel(?string $reason = null): void
  {
    if (!$this->canBeCancelled()) {
      throw new \Exception('This leave request cannot be cancelled.');
    }

    $this->update([
      'status' => LeaveStatus::Cancelled->value,
      'cancelled_at' => now(),
      'cancellation_reason' => $reason
    ]);
  }

  public function totalDays(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->start_date->diffInDays($this->end_date) + 1
    ); // Include both start and end dates
  }

  public function isPending(): bool
  {
    return $this->status === LeaveStatus::Pending->value;
  }

  public function isApproved(): bool
  {
    return $this->status === LeaveStatus::Approved->value;
  }

  public function isRejected(): bool
  {
    return $this->status === LeaveStatus::Rejected->value;
  }

  public function isCancelled(): bool
  {
    return $this->status === LeaveStatus::Cancelled->value;
  }

  public function isReviewed(): bool
  {
    return !is_null($this->reviewed_at);
  }

  public function isWithinDateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): bool
  {
    return $this->start_date->lessThanOrEqualTo($endDate) && $this->end_date->greaterThanOrEqualTo($startDate);
  }

  public function isOverlappingWith(LeaveRequest $other): bool
  {
    return $this->start_date->lessThanOrEqualTo($other->end_date) && $this->end_date->greaterThanOrEqualTo($other->start_date);
  }

  public function getStatusLabel(): string
  {
    return LeaveStatus::from($this->status)->label();
  }

  public function getStatusColor(): string
  {
    return LeaveStatus::from($this->status)->color();
  }

  public function getFormattedStartDate(): string
  {
    return $this->start_date->format('Y-m-d');
  }

  public function getFormattedEndDate(): string
  {
    return $this->end_date->format('Y-m-d');
  }

  public function getFormattedTotalDays(): string
  {
    return $this->total_days . ' day' . ($this->total_days > 1 ? 's' : '');
  }

  #[Scope]
  protected function pending(Builder $query): void
  {
    $query->where('status', 'pending');
  }

  #[Scope]
  protected function approved(Builder $query): void
  {
    $query->where('status', 'approved');
  }

  #[Scope]
  protected function rejected(Builder $query): void
  {
    $query->where('status', 'rejected');
  }

  public function approve(User $approver, ?string $notes = null): bool
  {
    $this->update([
      'status' => 'approved',
      'approved_by' => $approver->id,
      'admin_notes' => $notes,
    ]);

    // Send specific approval notification
    $this->user->notify(new LeaveRequestApproved($this));

    return true;
  }

  public function reject(User $rejector, ?string $notes = null): bool
  {
    $this->update([
      'status' => 'rejected',
      'reviewed_by' => $rejector->id,
      'comment' => $notes,
    ]);

    // Send specific rejection notification
    $this->user->notify(new LeaveRequestRejected($this));

    return true;
  }

  protected static function booted()
  {
    static::created(function ($leaveRequest) {
      // Notify admins and HR
      $admins = User::role(['Admin', 'HR'])->get();
      Notification::send($admins, new LeaveRequestSubmitted($leaveRequest));
    });

    static::updated(function ($leaveRequest) {
      if ($leaveRequest->wasChanged('status') &&
        !in_array($leaveRequest->status, ['approved', 'rejected'])) {
        $leaveRequest->user->notify(new LeaveRequestUpdated($leaveRequest));
      }
    });
  }
}
