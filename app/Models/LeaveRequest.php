<?php

namespace App\Models;

use App\Notifications\LeaveRequestStatusUpdated;
use App\Notifications\LeaveRequestSubmitted;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LeaveRequest extends Model implements HasMedia
{
  /** @use HasFactory<\Database\Factories\LeaveRequestFactory> */
  use HasFactory, HasUuid, InteractsWithMedia;

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
  ];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
    'reviewed_at' => 'datetime',
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

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('supporting_documents')
      ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png'])
      ->maxFileSize(5 * 1024 * 1024); // 5MB
  }

  protected static function booted()
  {
    static::created(function ($leaveRequest) {
      // Notify admins and HR
      $admins = User::role(['Admin', 'HR'])->get();
      Notification::send($admins, new LeaveRequestSubmitted($leaveRequest));
    });

    static::updated(function ($leaveRequest) {
      if ($leaveRequest->wasChanged('status')) {
        $leaveRequest->user->notify(new LeaveRequestStatusUpdated($leaveRequest));
      }
    });
  }
}
