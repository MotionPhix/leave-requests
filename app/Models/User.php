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
    use HasFactory, HasRoles, HasUuid, Notifiable;

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
        'is_system_admin',
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
            'is_system_admin' => 'boolean',
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
     * Workspaces the user belongs to.
     */
    public function workspaces(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot('role')
            ->withTimestamps();
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

    protected static function generateEmployeeId(): string
    {
        // Disable tenant scope when looking for settings during user creation
        $settings = EmployeeIdSetting::disableTenantScope(function () {
            return EmployeeIdSetting::first();
        });

        // If no settings found, create default settings object (not persisted)
        if (! $settings) {
            $settings = new EmployeeIdSetting([
                'prefix' => null,
                'suffix' => null,
                'separator' => '-',
                'include_year' => true,
                'year_format' => 'Y',
                'number_length' => 4,
            ]);
        }

        $parts = [];

        // Add prefix
        if ($settings->prefix) {
            $parts[] = $settings->prefix;
        }

        // Add year if enabled
        if ($settings->include_year) {
            $parts[] = date($settings->year_format);
        }

        // Get the last number used - SQLite compatible approach
        $lastUser = static::latest('id')->first();
        $lastNumber = 0;

        if ($lastUser && $lastUser->employee_id) {
            // Extract the numeric part from the employee_id
            preg_match('/(\d+)(?!.*\d)/', $lastUser->employee_id, $matches);
            $lastNumber = $matches[1] ?? 0;
        }

        $nextNumber = str_pad($lastNumber + 1, $settings->number_length, '0', STR_PAD_LEFT);

        $parts[] = $nextNumber;

        // Add suffix
        if ($settings->suffix) {
            $parts[] = $settings->suffix;
        }

        // Join with separator
        return implode($settings->separator, $parts);
    }

    /**
     * Get the formatted work phone number for display.
     */
    public function formattedWorkPhone(): ?Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatWorkPhone()
        );
    }

    /**
     * Format the work phone number for display.
     */
    protected function formatWorkPhone(): string
    {
        if (! $this->work_phone) {
            return '';
        }

        try {
            $phone = phone($this->work_phone);

            return $phone->formatNational(); // Format as national number for display
        } catch (\Exception $e) {
            return $this->work_phone; // Return original if formatting fails
        }
    }

    /**
     * Get the international format of work phone.
     */
    public function internationalWorkPhone(): ?Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatWorkPhoneInternational()
        );
    }

    /**
     * Format the work phone number in international format.
     */
    protected function formatWorkPhoneInternational(): ?string
    {
        if (! $this->work_phone) {
            return null;
        }

        try {
            $phone = phone($this->work_phone);

            return $phone->formatInternational();
        } catch (\Exception $e) {
            return $this->work_phone; // Return original if formatting fails
        }
    }

    /**
     * Get the E164 format of work phone (for storage/API).
     */
    public function e164WorkPhone(): ?Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatWorkPhoneE164()
        );
    }

    /**
     * Format the work phone number in E164 format.
     */
    protected function formatWorkPhoneE164(): ?string
    {
        if (! $this->work_phone) {
            return null;
        }

        try {
            $phone = phone($this->work_phone);

            return $phone->formatE164();
        } catch (\Exception $e) {
            return $this->work_phone; // Return original if formatting fails
        }
    }

    /**
     * Check if the work phone number is valid.
     */
    public function hasValidWorkPhone(): bool
    {
        if (! $this->work_phone) {
            return false;
        }

        try {
            phone($this->work_phone);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user is a system administrator
     */
    public function isSystemAdmin(): bool
    {
        return $this->is_system_admin;
    }

    /**
     * Check if user can perform system-wide operations
     */
    public function canPerformSystemOperations(): bool
    {
        return $this->is_system_admin;
    }

    /**
     * Check if user has access to all workspaces
     */
    public function hasGlobalAccess(): bool
    {
        return $this->is_system_admin;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->employee_id = static::generateEmployeeId();
        });
    }
}
