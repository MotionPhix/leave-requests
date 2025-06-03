<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
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
      'gender' => Gender::class,
      'employment_status' => EmploymentStatus::class,
      'employment_type' => EmploymentType::class,
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
      get: fn() => LeaveType::query()
        ->when($this->gender !== 'any', function ($query) {
          $query->where(function ($q) {
            $q->where('gender_specific', false)
              ->orWhere('gender', $this->gender);
          });
        })
        ->get()
    );
  }

  protected static function generateEmployeeId(): string
  {
    $settings = EmployeeIdSetting::first() ?? new EmployeeIdSetting();

    $parts = [];

    // Add prefix
    if ($settings->prefix) {
      $parts[] = $settings->prefix;
    }

    // Add year if enabled
    if ($settings->include_year) {
      $parts[] = date($settings->year_format);
    }

    // Get the last number used
    $lastUser = static::orderByRaw('CONVERT(SUBSTRING_INDEX(employee_id, "-", -1), UNSIGNED INTEGER) DESC')
      ->first();

    // Extract the number and increment
    $lastNumber = $lastUser ? (int)preg_replace('/[^0-9]/', '', $lastUser->employee_id) : 0;
    $nextNumber = str_pad($lastNumber + 1, $settings->number_length, '0', STR_PAD_LEFT);

    $parts[] = $nextNumber;

    // Add suffix
    if ($settings->suffix) {
      $parts[] = $settings->suffix;
    }

    // Join with separator
    return implode($settings->separator, $parts);
  }

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($user) {
      $user->employee_id = static::generateEmployeeId();
    });
  }
}
