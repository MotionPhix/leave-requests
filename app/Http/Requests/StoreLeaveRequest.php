<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
  public function authorize(): bool
  {
    return auth()->user()->can('create leave');
  }

  public function rules(): array
  {
    return [
      'leave_type_id' => ['required', 'exists:leave_types,id'],
      'start_date' => ['required', 'date', 'after:today'],
      'end_date' => ['required', 'date', 'after:start_date'],
      'reason' => ['nullable', 'string', 'max:1000'],
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
