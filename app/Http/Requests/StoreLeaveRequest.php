<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return auth()->user()->can('create leave');
  }

  public function rules(): array
  {
    return [
      'leave_type_id' => [
        'required',
        'exists:leave_types,id',
        function ($attribute, $value, $fail) {
          $leaveType = LeaveType::find($value);
          $user = auth()->user();

          // Check gender specific rules
          if ($leaveType->gender_specific && $leaveType->gender !== $user->gender) {
            $fail("You are not eligible for this type of leave.");
          }

          // Check frequency (for maternity/paternity)
          if ($leaveType->frequency_years > 1) {
            $lastRequest = $user->leaveRequests()
              ->where('leave_type_id', $value)
              ->where('status', 'approved')
              ->where('created_at', '>=', now()->subYears($leaveType->frequency_years))
              ->exists();

            if ($lastRequest) {
              $fail("You can only take this leave once every {$leaveType->frequency_years} years.");
            }
          }
        }
      ],
      'start_date' => [
        'required',
        'date',
        'after_or_equal:today',
        function ($attribute, $value, $fail) {
          $leaveType = LeaveType::find($this->leave_type_id);

          if ($leaveType->minimum_notice_days > 0) {
            $minDate = Carbon::now()->addDays($leaveType->minimum_notice_days);
            if (Carbon::parse($value)->lt($minDate)) {
              $fail("You must provide at least {$leaveType->minimum_notice_days} days notice.");
            }
          }
        }
      ],
      'end_date' => [
        'required',
        'date',
        'after_or_equal:start_date',
        function ($attribute, $value, $fail) {
          $start = Carbon::parse($this->start_date);
          $end = Carbon::parse($value);

          // Check for overlapping leaves
          $hasOverlap = auth()->user()->leaveRequests()
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($start, $end) {
              $query->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end]);
            })->exists();

          if ($hasOverlap) {
            $fail('You have overlapping leave requests.');
          }

          // Check available balance
          $workingDays = $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
          }, $end);

          $leaveBalanceService = app(LeaveBalanceService::class);
          $available = $leaveBalanceService->getRemainingDays(
            auth()->id(),
            $this->leave_type_id
          );

          if ($workingDays > $available) {
            $fail("Insufficient leave balance. You have {$available} days available.");
          }
        }
      ],
      'reason' => ['nullable', 'string', 'min:10', 'max:1000'],
      'supporting_documents.*' => [
        Rule::requiredIf(function () {
          return LeaveType::find($this->leave_type_id)->requires_documentation;
        }),
        'file',
        'mimes:pdf,jpg,jpeg,png',
        'max:5120'
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'leave_type_id.required' => 'Please select a leave type.',
      'start_date.required' => 'Start date is required.',
      'start_date.after_or_equal' => 'Leave cannot be requested for past dates.',
      'end_date.required' => 'End date is required.',
      'end_date.after_or_equal' => 'End date must be after or equal to start date.',
    ];
  }
}
