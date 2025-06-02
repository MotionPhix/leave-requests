<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasRoles, HasUuid;

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'gender',
    'password',
    'position',
    'department',
    'employee_id',
    'join_date',
    'reporting_to',
    'work_phone',
    'office_location',
    'employment_status',
    'employment_type',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var list<string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'join_date' => 'date',
    ];
  }

  /**
   * Get all leave requests for the user
   */
  public function leaveRequests(): HasMany
  {
    return $this->hasMany(LeaveRequest::class);
  }

  /**
   * Get the user's manager
   */
  public function manager(): BelongsTo
  {
    return $this->belongsTo(User::class, 'reporting_to');
  }

  /**
   * Get all team members reporting to this user
   */
  public function teamMembers(): HasMany
  {
    return $this->hasMany(User::class, 'reporting_to');
  }

  /**
   * Get the department this user belongs to
   */
  public function departmentModel(): BelongsTo
  {
    return $this->belongsTo(Department::class, 'department');
  }


  /**
   * Check if user is a manager
   */
  public function isManager(): bool
  {
    return $this->teamMembers()->exists();
  }

  /**
   * Get pending leave requests that need approval from this user
   */
  public function pendingApprovals(): HasMany
  {
    return $this->teamMembers()
      ->with(['leaveRequests' => function ($query) {
        $query->where('status', 'pending');
      }])
      ->whereHas('leaveRequests', function ($query) {
        $query->where('status', 'pending');
      });
  }

  /**
   * Scope to get active employees
   */
  #[Scope]
  public function active($query)
  {
    return $query->where('employment_status', 'active');
  }

  /**
   * Get all leave types available for the user
   */
  public function availableLeaveTypes(): Attribute
  {
    return Attribute::make(
      get: fn () => LeaveType::query()
      ->when($this->gender !== 'any', function ($query) {
        $query->where(function ($q) {
          $q->where('gender_specific', false)
            ->orWhere('gender', $this->gender);
        });
      })
      ->get()
    );
  }
}
