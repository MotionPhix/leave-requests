<?php

namespace App\Http\Requests\LeaveType;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('manage leave types');
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255', 'unique:leave_types'],
      'description' => ['nullable', 'string'],
      'max_days_per_year' => ['required', 'integer', 'min:0'],
      'requires_documentation' => ['boolean'],
      'gender_specific' => ['boolean'],
      'gender' => ['required', 'string', 'in:male,female,any'],
      'frequency_years' => ['required', 'integer', 'min:1'],
      'pay_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
      'minimum_notice_days' => ['required', 'integer', 'min:0'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Please provide a name for the leave type',
      'name.unique' => 'This leave type name already exists',
      'max_days_per_year.required' => 'Please specify maximum days per year',
      'max_days_per_year.min' => 'Maximum days cannot be negative',
      'frequency_years.min' => 'Frequency must be at least 1 year',
      'pay_percentage.min' => 'Pay percentage cannot be negative',
      'pay_percentage.max' => 'Pay percentage cannot exceed 100%',
      'minimum_notice_days.min' => 'Minimum notice days cannot be negative',
    ];
  }
}
